<?php
/**
 * Editor limits: content stays inside what the public site renders.
 *
 * The static front end renders post bodies through an allowed element set
 * (paragraphs, h2-h3, lists, images, quotes, separators, tables) styled by its
 * design tokens, and fails the build on anything else. These limits stop editors
 * producing markup the site can't render or that fights the design. Colors,
 * fonts and spacing are locked in the BRD Headless theme's theme.json.
 *
 * Per site: filter `brd_headless_allowed_blocks` to add a site's own blocks, and
 * `brd_headless_heading_levels` to change the heading levels.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Blocks editors can insert. */
function brd_headless_allowed_blocks(): array
{
    return array_values(apply_filters('brd_headless_allowed_blocks', [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/image',
        'core/quote',
        'core/separator',
        'core/table',
    ]));
}
add_filter('allowed_block_types_all', fn() => brd_headless_allowed_blocks(), 10, 2);

// No raw HTML editing and no block locking: what's saved is what the site renders.
add_filter('block_editor_settings_all', function ($settings) {
    $settings['codeEditingEnabled'] = false;
    $settings['canLockBlocks'] = false;
    return $settings;
});

// Headings: h2 and h3 by default (the page's h1 is the title).
add_filter('register_block_type_args', function ($args, $name) {
    if ($name === 'core/heading') {
        $levels = array_values(apply_filters('brd_headless_heading_levels', [2, 3]));
        $args['attributes']['levelOptions'] = ['type' => 'array', 'default' => $levels];
    }
    return $args;
}, 10, 2);

// Uploads are scaled down to this long edge (WordPress keeps the original
// aside), so a 6000px phone photo doesn't become the source the site resizes.
add_filter('big_image_size_threshold', fn() => (int) apply_filters('brd_headless_max_image_edge', 2880));
