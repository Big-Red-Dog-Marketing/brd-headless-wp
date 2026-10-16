<?php
/**
 * WordPress always needs an active theme; this is the smallest one that keeps
 * the editor working. Security and headless behaviour live in the BRD Headless
 * plugin, so switching themes can't switch them off.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Featured images need theme support, which block themes
// like Twenty Twenty-Five turn on by default.
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', ['caption', 'gallery', 'script', 'style']);
});
