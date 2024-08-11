<?php
/**
 * PloverToc
 *
 * @author Wasseem Khayrattee
 * @copyright 2024 Wasseem Khayrattee
 * @license GPL-3.0-only
 *
 * @wordpress-plugin
 * Plugin Name: PloverToc
 * Plugin URI: https://github.com/PloverHub/plover-toc
 * Description: A plugin to automatically generate a TOC for posts, pages and custom post types by parsing headers. It adds ID anchor attributes for proper in-page links.
 * Version: 0.7.0
 * Requires at least: 6.4.0
 * Author: Wasseem Khayrattee
 * Author URI: https://github.com/wkhayrattee
 * License: GPL-3.0-only
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: plover-toc
 * Domain Path: /languages
 *
 *
 * reference: https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version GPL-3.0-only.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Make sure we don't expose any info if called directly
 */

use PloverToc\PluginManager;

if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
 * Some global constants for our use-case
 */
define('PLOVER_TOC_PLUGIN_VERSION', '0.7.0');
define('PLOVER_TOC_PLUGIN_MINIMUM_WP_VERSION', '6.4.0');
define('PLOVER_TOC_PLUGIN_DIR_URL', plugin_dir_url(__FILE__)); //has trailing slash at end
define('PLOVER_TOC_PLUGIN_DIR', plugin_dir_path(__FILE__)); //has trailing slash at end
define('PLOVER_TOC_PLUGIN_BASENAME', plugin_basename(PLOVER_TOC_PLUGIN_DIR));
define('PLOVER_TOC_PLUGIN_TEMPLATES', PLOVER_TOC_PLUGIN_DIR . 'templates' . DIRECTORY_SEPARATOR);
define('PLOVER_TOC_PLUGIN_ERROR_LOG_FILE', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plover-toc_message.log');

/**
 * composer autoloading
 */
require_once PLOVER_TOC_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'includes/vendor/autoload.php';

/**
 * Register main Hooks
 */
register_activation_hook(__FILE__, ['PloverToc\\PluginManager', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['PloverToc\\PluginManager', 'plugin_deactivation']);

/**
 * Load general frontend logic & background processes
 */
PluginManager::run();
