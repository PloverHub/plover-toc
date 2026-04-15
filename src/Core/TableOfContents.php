<?php

namespace PloverToc;

use Knp\Menu\ItemInterface;
use TOC\MarkupFixer;
use TOC\TocGenerator;

class TableOfContents
{
    const TOC_SHORTCODE = 'plovertoc';

    /**
     * SVG chain/link icon for heading anchor links.
     * Sized with `1em` so it scales with the heading's font size.
     */
    private const ANCHOR_LINK_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';

    /**
     * Build the table of contents and output it, using the WordPress load_template() method.
     * This method processes the content to generate the TOC, including fixing header tags for anchors
     * and optionally generating JSON-LD for the TOC.
     *
     * @param array $attributes Shortcode attributes.
     * @param string|null $content Content inside the shortcode (if any).
     * @param string $shortcode_tag Shortcode tag (name).
     *
     * @return string The table of contents HTML.
     */
    public static function load_toc(array $attributes, ?string $content, string $shortcode_tag): string
    {
        if (empty($content)) {
            $content = get_the_content();
        }

        if (empty($content)) {
            return '';
        }

        // Set default values for top_level, depth, and schema
        $top_level = isset($attributes['top_level']) ? (int) $attributes['top_level'] : 1;
        $depth = isset($attributes['depth']) ? (int) $attributes['depth'] : 6;
        $schema_enabled = isset($attributes['schema']) ? filter_var($attributes['schema'], FILTER_VALIDATE_BOOLEAN) : true;

        // Ensure that all header tags have `id` attributes so they can be used as anchor links
        $content = self::fix_content_by_adding_anchors($content);

        $generator = new TocGenerator();
        $toc_html = $generator->getHtmlMenu($content, $top_level, $depth);
        $toc_menu = $generator->getMenu($content, $top_level, $depth);

        $tpl_args = ['toc' => $toc_html];
        if ($schema_enabled) {
            $tpl_args['json_ld'] = self::generate_json_ld($toc_menu);
        }

        ob_start();
        load_template(PLOVER_TOC_PLUGIN_DIR . 'templates/toc.tpl.php', false, $tpl_args);

        return ob_get_clean();
    }

    /**
     * Fix the content by adding `id` attributes to header tags for use as anchor links.
     * Runs on all content (not just shortcode posts) so headings are always linkable.
     *
     * Hooked to `the_content` at priority 20 — after block rendering (do_blocks runs at 9).
     *
     * @param string $content The post content to be processed.
     *
     * @return string The processed content with `id` attributes added to headers.
     */
    public static function fix_content_by_adding_anchors(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        $fixer = new MarkupFixer();
        $content = $fixer->fix($content);

        unset($fixer);

        // Optionally inject a permalink icon into each heading.
        // Themes/plugins opt in via: add_filter('plovertoc_anchor_links', '__return_true');
        if (apply_filters('plovertoc_anchor_links', false)) {
            $content = self::inject_anchor_links($content);
        }

        return $content;
    }

    /**
     * Inject a permalink anchor link inside every H2–H4 heading that has an id attribute.
     *
     * The link is appended after the heading text as a visually hidden icon — themes control
     * visibility (typically show on :hover via CSS). JS can enhance with copy-to-clipboard.
     *
     * Themes opt in with: add_filter('plovertoc_anchor_links', '__return_true');
     * The CSS class `plovertoc-anchor-link` is the styling hook.
     *
     * @param string $content Content that has already been through MarkupFixer (headings have ids).
     * @return string Content with anchor links injected into headings.
     */
    private static function inject_anchor_links(string $content): string
    {
        return preg_replace_callback(
            '/<h([2-4])(\b[^>]*\bid="([^"]+)"[^>]*)>(.*?)<\/h[2-4]>/si',
            function ($m) {
                $level = $m[1];
                $attrs = $m[2];
                $id    = $m[3];
                $inner = $m[4];

                // Skip if already injected (guards against double-filtering).
                if (false !== strpos($inner, 'plovertoc-anchor-link')) {
                    return $m[0];
                }

                $label = esc_attr__('Link to this section', 'plover-toc');
                $link  = '<a class="plovertoc-anchor-link" href="#' . esc_attr($id) . '" aria-label="' . $label . '">'
                       . self::ANCHOR_LINK_SVG
                       . '</a>';

                return '<h' . $level . $attrs . '>' . $inner . $link . '</h' . $level . '>';
            },
            $content
        );
    }

    /**
     * Parse H2–H4 headings from already-rendered content and return the inner <li> HTML
     * for the theme's TOC widget.
     *
     * The content passed here must already have id attributes on headings (i.e. it has
     * already been through fix_content_by_adding_anchors / the the_content filter).
     * This method does NOT run MarkupFixer again.
     *
     * @param string $rendered_content Fully rendered post content (after the_content filters).
     *
     * @return string HTML string of <li> elements, ready to drop into a <ul class="toc-list">.
     *                Returns empty string when no H2–H4 headings with id attributes are found.
     */
    public static function build_toc_from_rendered_content(string $rendered_content): string
    {
        if (empty($rendered_content)) {
            return '';
        }

        // Match H2–H4 tags that carry an id attribute.
        preg_match_all(
            '/<h([2-4])\b[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h[2-4]>/si',
            $rendered_content,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            return '';
        }

        $items = array_map(function ($m) {
            return [
                'level' => (int) $m[1],
                'id'    => $m[2],
                'text'  => wp_strip_all_tags($m[3]),
            ];
        }, $matches);

        $tree = self::build_toc_tree($items);

        return self::render_toc_tree($tree);
    }

    /**
     * Organise a flat list of heading items into a nested H2 → H3 → H4 tree.
     *
     * H3 nodes without a preceding H2, and H4 nodes without a preceding H3, are silently
     * skipped — matching the JS fallback behaviour.
     *
     * @param array<array{level: int, id: string, text: string}> $items
     * @return array Nested tree ready for render_toc_tree().
     */
    private static function build_toc_tree(array $items): array
    {
        $tree   = [];
        $h2_idx = -1;
        $h3_idx = -1;

        foreach ($items as $item) {
            $node = [
                'level'    => $item['level'],
                'id'       => $item['id'],
                'text'     => $item['text'],
                'children' => [],
            ];

            if (2 === $item['level']) {
                $tree[]  = $node;
                $h2_idx  = count($tree) - 1;
                $h3_idx  = -1;
            } elseif (3 === $item['level'] && $h2_idx >= 0) {
                $tree[$h2_idx]['children'][] = $node;
                $h3_idx = count($tree[$h2_idx]['children']) - 1;
            } elseif (4 === $item['level'] && $h2_idx >= 0 && $h3_idx >= 0) {
                $tree[$h2_idx]['children'][$h3_idx]['children'][] = $node;
            }
        }

        return $tree;
    }

    /**
     * Render the nested heading tree to numbered <li> HTML.
     *
     * Numbering mirrors the JS fallback: H2 → "01.", H3 → "1.1", H4 → "1.1.1".
     *
     * @param array $tree Output of build_toc_tree().
     * @return string Inner HTML for <ul class="toc-list">.
     */
    private static function render_toc_tree(array $tree): string
    {
        $html     = '';
        $h2_count = 0;

        foreach ($tree as $h2) {
            $h2_count++;
            $h2_num = str_pad((string) $h2_count, 2, '0', STR_PAD_LEFT) . '.';

            $html .= '<li>';
            $html .= '<a href="#' . esc_attr($h2['id']) . '">';
            $html .= '<span class="toc-number">' . esc_html($h2_num) . '</span>';
            $html .= '<span>' . esc_html($h2['text']) . '</span>';
            $html .= '</a>';

            if (!empty($h2['children'])) {
                $html     .= '<ul class="toc-list-h3">';
                $h3_count  = 0;

                foreach ($h2['children'] as $h3) {
                    $h3_count++;
                    $h3_num = $h2_count . '.' . $h3_count;

                    $html .= '<li>';
                    $html .= '<a href="#' . esc_attr($h3['id']) . '">';
                    $html .= '<span class="toc-number">' . esc_html($h3_num) . '</span>';
                    $html .= '<span>' . esc_html($h3['text']) . '</span>';
                    $html .= '</a>';

                    if (!empty($h3['children'])) {
                        $html     .= '<ul class="toc-list-h4">';
                        $h4_count  = 0;

                        foreach ($h3['children'] as $h4) {
                            $h4_count++;
                            $h4_num = $h2_count . '.' . $h3_count . '.' . $h4_count;

                            $html .= '<li>';
                            $html .= '<a href="#' . esc_attr($h4['id']) . '">';
                            $html .= '<span class="toc-number">' . esc_html($h4_num) . '</span>';
                            $html .= '<span>' . esc_html($h4['text']) . '</span>';
                            $html .= '</a>';
                            $html .= '</li>';
                        }

                        $html .= '</ul>';
                    }

                    $html .= '</li>';
                }

                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Generate JSON-LD structured data for the TOC.
     * This method creates the JSON-LD representation of the TOC for SEO purposes.
     *
     * @param ItemInterface $menu The TOC menu object.
     *
     * @return string The JSON-LD script tag.
     */
    private static function generate_json_ld(ItemInterface $menu): string
    {
        $items = self::generate_json_ld_items($menu);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'SiteNavigationElement',
            'name' => 'Table of Contents',
            'url' => get_permalink(),
            'hasPart' => $items,
        ];

        return wp_json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Recursively generate JSON-LD items for each TOC entry.
     * This method handles the nested structure of the TOC, generating items for each level.
     *
     * @param ItemInterface $menu The TOC menu object.
     *
     * @return array An array of JSON-LD items.
     */
    private static function generate_json_ld_items(ItemInterface $menu): array
    {
        $items = [];
        foreach ($menu->getChildren() as $child) {
            $item = [
                '@type' => 'SiteNavigationElement',
                'name' => $child->getLabel(),
                'url' => get_permalink() . '#' . $child->getName(),
            ];
            if ($child->hasChildren()) {
                $item['hasPart'] = self::generate_json_ld_items($child);
            }
            $items[] = $item;
        }

        return $items;
    }
}
