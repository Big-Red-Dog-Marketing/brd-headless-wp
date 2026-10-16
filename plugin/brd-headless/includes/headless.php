<?php
/**
 * Headless behaviour: front-end requests go to the public site, nothing on
 * this host is indexed, and "View" links point at the public site.
 */

if (!defined('ABSPATH')) {
    exit;
}

const BRD_HEADLESS_ROBOTS = 'noindex, nofollow, noimageindex';

// Front-end requests go to the same path on the public site. Priority 1 runs
// before core's canonical redirect, which would turn ?author=1 into the
// author's login slug. Previews still render here (with the BRD Headless theme).
add_action('template_redirect', function () {
    $public = brd_headless_public_url();
    if (!$public || is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || is_preview() || is_robots()) {
        return;
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    wp_redirect($public . $path, 301, 'BRD Headless');
    exit;
}, 1);

// Nothing on this host is for search engines: pages, REST, feeds, and (through
// the .htaccess block in hardening.php) every file Apache serves directly.
add_action('send_headers', fn() => header('X-Robots-Tag: ' . BRD_HEADLESS_ROBOTS, true));
add_filter('rest_post_dispatch', function ($response) {
    $response->header('X-Robots-Tag', BRD_HEADLESS_ROBOTS);
    return $response;
});
add_filter('wp_robots', fn() => ['noindex' => true, 'nofollow' => true, 'noimageindex' => true]);
add_filter('pre_option_blog_public', fn() => '0');
add_filter('robots_txt', fn() => "User-agent: *\nDisallow: /\n", 99);
add_filter('wp_sitemaps_enabled', '__return_false');
// Attachment pages off: their URLs go to the file itself, which carries the header.
add_filter('pre_option_wp_attachment_pages_enabled', fn() => '0');

/**
 * "View" links in the editor. By default a published item's permalink moves to
 * the public host with the same path; a site whose front end uses other paths
 * filters `brd_headless_view_url` (return null to keep the CMS link).
 */
function brd_headless_view_url(string $url, $post): string
{
    $public = brd_headless_public_url();
    if (!$public || !$post || get_post_status($post) !== 'publish') {
        return $url;
    }
    $default = $public . (parse_url($url, PHP_URL_PATH) ?: '/');
    return apply_filters('brd_headless_view_url', $default, get_post($post), $public) ?? $url;
}
add_filter('post_link', 'brd_headless_view_url', 10, 2);
add_filter('page_link', 'brd_headless_view_url', 10, 2);
add_filter('post_type_link', 'brd_headless_view_url', 10, 2);
