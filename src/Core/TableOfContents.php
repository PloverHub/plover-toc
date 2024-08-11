<?php

namespace PloverToc;

use Knp\Menu\ItemInterface;
use TOC\MarkupFixer;
use TOC\TocGenerator;

class TableOfContents
{
    const TOC_SHORTCODE = 'plovertoc';

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
     * This method ensures all header tags have unique IDs that can be linked to from the TOC.
     *
     * @param string $content The post content to be processed.
     *
     * @return string The processed content with `id` attributes added to headers.
     */
    public static function fix_content_by_adding_anchors(string $content): string
    {
        if (has_shortcode($content, self::TOC_SHORTCODE)) {
            // Ensure that all header tags have `id` attributes so they can be used as anchor links
            $fixer = new MarkupFixer();
            $content = $fixer->fix($content);

            unset($fixer);
        }

        return $content;
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
