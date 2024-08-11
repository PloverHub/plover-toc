# PloverToc - A Table of Contents WordPress Plugin #

**Contributors:** [Wasseem Khayrattee](https://profiles.wordpress.org/wkhayrattee/)  
**Tags:** table of contents, toc 
**Requires at least:** 6.4.0  
**Tested up to:** 6.6.1  
**Stable tag:** 0.7.0  
**License:** GPL-3.0-only  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html  

A plugin to automatically generate a TOC for posts, pages and custom post types by parsing headers. It adds ID anchor attributes for proper in-page links.

## Description ##

### About ###

PloverToc is a simple and efficient WordPress plugin that automatically generates a table of contents (TOC) for your posts, pages and custom post types. By using a shortcode, you can easily insert a TOC that helps your readers navigate through your content by providing direct links to different sections. You can also customize the heading levels included in the TOC.

It's focused on providing a sound TOC interface with no "bells and whistles". While it does come with some helpful features for customizing the included heading levels via shortcode attributes, the esthetic design and styling of the TOC is left to the user to implement using their own CSS.

Additionally, it does add SiteNavigationElement schema to enhance your site's SEO.


### Features ###

- Automatically generate a table of contents for your posts and pages.
- Customizable heading levels to include in the TOC (e.g., H1, H2, H3).
- Collapse TOC on page upload, without any JavaScript.
- This plugin is 100% javascript-free.
- Simple shortcode to insert the TOC anywhere in your content.
- Compatible with the latest version of WordPress with Gutenberg.
- Compatible with the Classic Editor as well.
- Users are responsible for styling the TOC using their own CSS.
- Custom post types are supported, as long as their content is output with the the_content() template tag.
- Will generate SiteNavigationElement schema to enhance SEO, this can be turned off via shortcode attribute.

### Requirements ###

- PHP >= 8.0
- WordPress >= 6.4.0

### Installation ###

1) Through the WordPress plugins screen, Add New Plugin
    - Alternatively, you can download the plugin from the WordPress Plugin Directory and upload it to your site.
2) Search for "plover toc"
3) Install and Activate
4) Use the shortcode `[plovertoc]` in your content
    - simply add the shortcode `[plovertoc]` to your post or page where you want the table of contents to appear.
5) (Optional) Customize  the top_level, depth, and schema attributes in the shortcode to specify which heading levels to include.
    - You can customize the heading levels included in the TOC using the `top_level` and `depth` attributes in the shortcode. For example: `[plovertoc top_level="2" depth="3"]`
    - You can also turn off the SiteNavigationElement schema using the `schema` attribute in the shortcode. For example: `[plovertoc schema="false"]`

### Shortcode Attributes ###

`[plovertoc top_level="2" depth="4"]` would mean generating a TOC that includes only H2, H3, H4 and H5 headings.
`[plovertoc top_level="1" depth="3"]` would mean generating a TOC that includes only H1, H2, and H3 headings.
`[plovertoc top_level="1" depth="2"]` would mean generating a TOC that includes only H1, and H2 headings.

### Styling ###

You can target the css class "plover-toc-wrapper" and "plover-toc-items". See below the actual HTML structure to guide you.

```html
<div class="plover-toc-wrapper">
    <details open="">
        <summary>Open Table of contents</summary>
        <div class="plover-toc-items">
            <ul>
              <li class="first last">
                <a href="#{anchor_id}}">{anchor_text}}</a>
                <ul class="menu_level_2">
                  <li class="first last">
                    <a href="#{anchor_id}}">{anchor_text}}</a>
                  </li>
                </ul>
              </li>
            </ul>
        </div>
    </details>
</div>
```

### Localization ###

PloverToc supports localization. To translate the plugin into your language, add the appropriate translation files in the `languages` directory. The text domain for this plugin is `plover-toc`.

You can also customize the summary text of the table of contents by using the `plover_toc_summary_text` filter as shown below. The default summary text is localizable.


### Changing the header (summary) text of the TOC ###

To customize the summary text of the table of contents, use the `plover_toc_summary_text` filter.

Example to change summary text:
```php
function custom_toc_summary_text(string $text)
{
    return __('My Custom Table of Contents', 'plover-toc');
}
add_filter('plover_toc_summary_text', 'custom_toc_summary_text');
```

## Contributing ##

The best way to contribute to the development of this plugin is by participating on the GitHub project:

[https://github.com/PloverHub/plover-toc](https://github.com/PloverHub/plover-toc)

There are many ways you can contribute:

* Raise an issue if you found one
* Create/send us a Pull Request with your bug fixes and/or new features
* Provide us with your feedback and/or suggestions for any improvement or enhancement
* Testing - unit testing by writing & submitting test cases
* Translation - this is an area we are yet to do

## Changelog ##

See our [Changelog](CHANGELOG.md)
