<?php
/**
 * Rebuild the public site when content changes.
 *
 * Saves don't call the hook straight away: each one restarts a 5-minute timer,
 * so an editing session ends in one build. "Rebuild site now" in the admin bar
 * builds now. The timer and scheduled posts run on WP-Cron, so the host or the
 * front end should request /wp-cron.php regularly (docs/setup.md).
 */

if (!defined('ABSPATH')) {
    exit;
}

const BRD_HEADLESS_REBUILD_DELAY = 5 * MINUTE_IN_SECONDS;

/**
 * Post types whose changes rebuild the site: every REST-visible type with an
 * admin screen, minus WordPress's internal ones. Filter to narrow or widen.
 */
function brd_headless_rebuild_post_types(): array
{
    $types = array_filter(
        get_post_types(['show_in_rest' => true, 'show_ui' => true]),
        fn($type) => $type !== 'attachment' && !str_starts_with($type, 'wp_')
    );
    return array_values(apply_filters('brd_headless_rebuild_post_types', $types));
}

function brd_headless_trigger_rebuild(string $reason): void
{
    static $sent = false;
    $url = brd_headless_rebuild_hook();
    if ($sent || !$url) {
        return;
    }
    $sent = true;
    wp_remote_post($url, ['blocking' => false, 'timeout' => 5, 'body' => ['reason' => $reason]]);
}

function brd_headless_queue_rebuild(string $reason): void
{
    wp_clear_scheduled_hook('brd_headless_rebuild');
    wp_schedule_single_event(time() + BRD_HEADLESS_REBUILD_DELAY, 'brd_headless_rebuild');
    update_option('brd_headless_rebuild_reason', $reason, false);
}
add_action('brd_headless_rebuild', fn() => brd_headless_trigger_rebuild((string) get_option('brd_headless_rebuild_reason', 'queued edits')));

add_action('transition_post_status', function ($new, $old, $post) {
    if (!in_array($post->post_type, brd_headless_rebuild_post_types(), true) || wp_is_post_revision($post) || wp_is_post_autosave($post)) {
        return;
    }
    // Publish, update a published item, unpublish or trash.
    if ($new === 'publish' || $old === 'publish') {
        brd_headless_queue_rebuild("{$post->post_type} {$post->ID} {$old}->{$new}");
    }
}, 10, 3);

// Term changes (categories, custom taxonomies) show on the public site too.
foreach (['created_term', 'edited_term', 'delete_term'] as $hook) {
    add_action($hook, function ($term_id, $tt_id, $taxonomy) {
        $tax = get_taxonomy($taxonomy);
        if ($taxonomy !== 'nav_menu' && !str_starts_with($taxonomy, 'wp_') && (!$tax || $tax->show_in_rest)) {
            brd_headless_queue_rebuild("{$taxonomy} {$term_id} changed");
        }
    }, 10, 3);
}

// Drag-and-drop ordering (Post Types Order) writes menu_order straight to the
// database, so no post is saved and the hooks above never run.
add_action('PTO/order_update_complete', fn() => brd_headless_queue_rebuild('order changed'));

// Admin bar: "Rebuild site now", with the time left when a rebuild is queued.
add_action('admin_bar_menu', function (WP_Admin_Bar $bar) {
    if (!current_user_can('edit_posts') || !brd_headless_rebuild_hook()) {
        return;
    }
    $next = wp_next_scheduled('brd_headless_rebuild');
    $title = $next ? sprintf('Rebuild site now (queued, %d min)', max(1, (int) ceil(($next - time()) / 60))) : 'Rebuild site now';
    $bar->add_node([
        'id' => 'brd-headless-rebuild',
        'title' => esc_html($title),
        'href' => wp_nonce_url(admin_url('admin-post.php?action=brd_headless_rebuild_now'), 'brd_headless_rebuild_now'),
    ]);
}, 100);

add_action('admin_post_brd_headless_rebuild_now', function () {
    if (!current_user_can('edit_posts')) {
        wp_die('Not allowed.', 403);
    }
    check_admin_referer('brd_headless_rebuild_now');
    wp_clear_scheduled_hook('brd_headless_rebuild');
    brd_headless_trigger_rebuild('rebuild button');
    wp_safe_redirect(add_query_arg('brd_headless_rebuilt', '1', wp_get_referer() ?: admin_url()));
    exit;
});

add_action('admin_notices', function () {
    if (!empty($_GET['brd_headless_rebuilt'])) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>The site is rebuilding. Changes show up on %s in a few minutes.</p></div>',
            esc_html(brd_headless_public_host())
        );
    }
});
