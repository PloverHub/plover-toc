<?php
/**
 * @param string $level
 * @param string $message
 * @param array $context
 */
function plovertoc_log(string $level, string $message, array $context = []): void
{
    error_log(
        PHP_EOL . gmdate('F j, Y, g:i a e O') . ' | ' . mb_strtoupper($level) . ': ' . $message . ' ' . (!empty($context) ? print_r($context, true) : ''),
        3,
        PLOVER_TOC_PLUGIN_ERROR_LOG_FILE
    );
}

/**
 * Generate the inner <li> HTML for a TOC list from already-rendered post content.
 *
 * The content must have already passed through the_content filters (so headings carry
 * id attributes added by MarkupFixer). Call this after apply_filters('the_content', ...).
 *
 * Returns empty string when the plugin is not loaded or no H2–H4 headings are found.
 *
 * @param string $rendered_content Post content after the_content filter chain.
 * @return string Inner HTML for <ul class="toc-list">, or '' if nothing to show.
 */
function plovertoc_generate_toc_items_html(string $rendered_content): string
{
    return \PloverToc\TableOfContents::build_toc_from_rendered_content($rendered_content);
}

function plovertoc_clear_log(string $log_file_path): bool
{
    global $wp_filesystem;

    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if (!file_exists($log_file_path)) {
        return false;
    }

    if (!$wp_filesystem->is_writable($log_file_path)) {
        plovertoc_log('warning', 'Could not clear the log, it is not writable. Please chmod to 0777');

        return false;
    }

    if (file_exists($log_file_path)) {
        wp_delete_file($log_file_path);
        plovertoc_log('info', 'The log was successfully cleared');

        return true;
    }
}
