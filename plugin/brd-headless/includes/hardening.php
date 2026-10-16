<?php
/**
 * Hardening for a CMS that has no public pages. Sources and reasoning:
 * docs/hardening.md in the repo.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Nobody edits plugin or theme code from the dashboard, and nobody, admins
// included, can save <script> or other unfiltered HTML into content: the
// public site renders content as it comes from REST.
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}
if (!defined('DISALLOW_UNFILTERED_HTML')) {
    define('DISALLOW_UNFILTERED_HTML', true);
}
// Major core updates install each new default theme unless told not to; the
// BRD Headless theme is the only one these installs need.
if (!defined('CORE_UPGRADE_SKIP_NEW_BUNDLED')) {
    define('CORE_UPGRADE_SKIP_NEW_BUNDLED', true);
}

// No WordPress version in markup or feeds.
add_filter('the_generator', '__return_empty_string');

// No user list for anonymous REST requests: it gives away login names. Static
// builds read content, not users; scripts that need users authenticate.
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    foreach (array_keys($endpoints) as $route) {
        if (str_starts_with($route, '/oembed/')) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
});

// XML-RPC off (also blocked at the web server below).
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');

// Comments, trackbacks, emojis and oEmbed off: a headless CMS serves none of them.
add_filter('comments_open', '__return_false', 20);
add_filter('pings_open', '__return_false', 20);
add_filter('comments_array', '__return_empty_array', 10);
add_filter('embed_oembed_discover', '__return_false');
add_action('admin_menu', fn() => remove_menu_page('edit-comments.php'));
add_action('init', function () {
    foreach (get_post_types_by_support('comments') as $type) {
        remove_post_type_support($type, 'comments');
        remove_post_type_support($type, 'trackbacks');
    }
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
}, 100);

/*
 * Apache rules, in "BRD Headless" marker blocks. Uploads and other static files
 * never reach PHP, so their headers and the file blocks live in .htaccess.
 * Written on activation, re-checked on admin page loads (a permalink save
 * rewrites .htaccess but keeps other marker blocks), removed on deactivation.
 */
function brd_headless_htaccess_rules(): array
{
    return [
        '<IfModule mod_headers.c>',
        '    Header set X-Robots-Tag "' . BRD_HEADLESS_ROBOTS . '"',
        '    Header always set X-Content-Type-Options "nosniff"',
        '    Header always set X-Frame-Options "SAMEORIGIN"',
        '    Header always set Referrer-Policy "strict-origin-when-cross-origin"',
        '    Header always set Strict-Transport-Security "max-age=31536000"',
        '</IfModule>',
        '<IfModule mod_authz_core.c>',
        '    <FilesMatch "^(xmlrpc\.php|readme\.html|license\.txt)$">',
        '        Require all denied',
        '    </FilesMatch>',
        '</IfModule>',
    ];
}

// Uploads are images and documents: nothing in wp-content/uploads runs as PHP.
function brd_headless_uploads_htaccess_rules(): array
{
    return [
        '<IfModule mod_authz_core.c>',
        '    <FilesMatch "\.(php|phtml|phar|php\d)$">',
        '        Require all denied',
        '    </FilesMatch>',
        '</IfModule>',
    ];
}

/** .htaccess path => rules. */
function brd_headless_htaccess_files(): array
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    return [
        get_home_path() . '.htaccess' => brd_headless_htaccess_rules(),
        wp_get_upload_dir()['basedir'] . '/.htaccess' => brd_headless_uploads_htaccess_rules(),
    ];
}

function brd_headless_write_all_htaccess(bool $remove = false): void
{
    foreach (brd_headless_htaccess_files() as $file => $rules) {
        $rules = $remove ? [] : $rules;
        $current = file_exists($file) ? extract_from_markers($file, 'BRD Headless') : [];
        if ($current === $rules) {
            continue;
        }
        if ((file_exists($file) && is_writable($file)) || (!file_exists($file) && is_writable(dirname($file)))) {
            insert_with_markers($file, 'BRD Headless', $rules);
        }
    }
}
add_action('admin_init', fn() => brd_headless_write_all_htaccess());
