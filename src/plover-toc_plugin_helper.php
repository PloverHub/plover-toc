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
