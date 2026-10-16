<?php
/**
 * Settings > Reading > "Headless site": the public site's URL and the rebuild
 * hook. Either can be pinned in wp-config.php instead (BRD_HEADLESS_PUBLIC_URL,
 * BRD_HEADLESS_REBUILD_HOOK), which locks the field.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** The public site, e.g. https://www.example.com, without a trailing slash. Empty until set. */
function brd_headless_public_url(): string
{
    $url = defined('BRD_HEADLESS_PUBLIC_URL') ? BRD_HEADLESS_PUBLIC_URL : get_option('brd_headless_public_url', '');
    return untrailingslashit((string) $url);
}

/** The Deploy Hook URL that rebuilds the public site. Empty until set. */
function brd_headless_rebuild_hook(): string
{
    return (string) (defined('BRD_HEADLESS_REBUILD_HOOK') ? BRD_HEADLESS_REBUILD_HOOK : get_option('brd_headless_rebuild_hook', ''));
}

/** The public site's host name, for messages. */
function brd_headless_public_host(): string
{
    return (string) (parse_url(brd_headless_public_url(), PHP_URL_HOST) ?: 'the public site');
}

add_action('admin_init', function () {
    add_settings_section('brd_headless', 'Headless site', function () {
        echo '<p>This WordPress only holds content. The public site is built from it and served elsewhere.</p>';
    }, 'reading');

    $fields = [
        'brd_headless_public_url' => [
            'Public site URL',
            'BRD_HEADLESS_PUBLIC_URL',
            'Where visitors read the site, e.g. https://www.example.com. Front-end requests here are redirected to the same path there.',
        ],
        'brd_headless_rebuild_hook' => [
            'Site rebuild hook',
            'BRD_HEADLESS_REBUILD_HOOK',
            'Deploy Hook URL (for example Cloudflare Workers Builds). Publishing, updating, unpublishing, trashing or reordering content rebuilds the public site 5 minutes after the last change. "Rebuild site now" in the admin bar builds straight away.',
        ],
    ];
    foreach ($fields as $option => [$label, $constant, $help]) {
        register_setting('reading', $option, ['type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '']);
        add_settings_field($option, $label, function () use ($option, $constant, $help) {
            $locked = defined($constant);
            $value = $locked ? constant($constant) : get_option($option, '');
            printf(
                '<input type="url" class="regular-text code" name="%s" value="%s" autocomplete="off"%s>',
                esc_attr($option),
                esc_attr((string) $value),
                $locked ? ' disabled' : ''
            );
            echo '<p class="description">' . esc_html($help) . ($locked ? ' ' . esc_html("Set by {$constant} in wp-config.php.") : '') . '</p>';
        }, 'reading', 'brd_headless');
    }
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_options') || brd_headless_public_url()) {
        return;
    }
    printf(
        '<div class="notice notice-warning"><p>BRD Headless: set the public site URL in <a href="%s">Settings &gt; Reading</a>. Until then, front-end requests are not redirected.</p></div>',
        esc_url(admin_url('options-reading.php#brd_headless_public_url'))
    );
});
