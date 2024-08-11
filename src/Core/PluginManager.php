<?php

namespace PloverToc;

class PluginManager
{
    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     *
     * @static
     */
    public static function plugin_activation(): void
    {
        //plant a seed to help us detect the state "on activation"
        add_option(Enum::PLUGIN_KEY, true);
    }

    public static function plugin_deactivation(): void
    {
        self::doCleanup();
    }

    /**
     * triggered when a user has deactivated the plugin
     */
    public static function plugin_uninstall(): void
    {
        self::doCleanup();
    }

    public static function doCleanup(): void
    {
        plovertoc_clear_log(PLOVER_TOC_PLUGIN_ERROR_LOG_FILE);
    }

    /**
     * To call any logic that needs to run on the frontend or in background
     */
    public static function run(): void
    {
        add_action('init', function () {
            // Register the shortcode
            add_shortcode(TableOfContents::TOC_SHORTCODE, [TableOfContents::class, 'load_toc']);

            // Now Add id anchor attributes to the <Hx> html tags
            add_filter('the_content', [TableOfContents::class, 'fix_content_by_adding_anchors'], 5);
        }, 5);
    }
}
