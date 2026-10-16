<?php
/**
 * Updates for this plugin and the BRD Headless theme come from the repo's
 * GitHub releases (assets brd-headless.zip and brd-headless-theme.zip) and show
 * on the normal Plugins and Themes screens. While the repo is private, each
 * site needs a read-only token in wp-config.php:
 *
 *   define('BRD_HEADLESS_GITHUB_TOKEN', 'github_pat_...');
 *
 * Library: Plugin Update Checker 5.7 (MIT), vendor/plugin-update-checker.
 */

if (!defined('ABSPATH')) {
    exit;
}

require dirname(__DIR__) . '/vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_action('after_setup_theme', function () {
    $checkers = [
        [BRD_HEADLESS_FILE, 'brd-headless', '/^brd-headless\.zip$/'],
    ];
    $theme = wp_get_theme('brd-headless-theme');
    if ($theme->exists()) {
        $checkers[] = [$theme->get_stylesheet_directory() . '/functions.php', 'brd-headless-theme', '/^brd-headless-theme\.zip$/'];
    }
    foreach ($checkers as [$file, $slug, $asset]) {
        $checker = PucFactory::buildUpdateChecker(BRD_HEADLESS_REPO, $file, $slug);
        $checker->getVcsApi()->enableReleaseAssets($asset);
        if (defined('BRD_HEADLESS_GITHUB_TOKEN') && BRD_HEADLESS_GITHUB_TOKEN) {
            $checker->setAuthentication(BRD_HEADLESS_GITHUB_TOKEN);
        }
    }
});
