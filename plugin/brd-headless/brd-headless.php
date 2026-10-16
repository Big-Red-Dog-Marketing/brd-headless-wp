<?php
/**
 * Plugin Name: BRD Headless
 * Plugin URI: https://github.com/Big-Red-Dog-Marketing/brd-headless-wp
 * Description: Runs WordPress as a headless CMS behind a static front end: front-end requests go to the public site, nothing here is indexed, the install is hardened, and content changes trigger a site rebuild. Pair it with the BRD Headless theme and a per-site plugin for the content model.
 * Version: 1.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Big Red Dog Marketing
 * License: GPL-2.0-or-later
 *
 * Settings: Settings > Reading > "Headless site". Setup and release notes: README.md in the repo.
 */

if (!defined('ABSPATH')) {
    exit;
}

const BRD_HEADLESS_VERSION = '1.0.1';
const BRD_HEADLESS_FILE = __FILE__;
const BRD_HEADLESS_REPO = 'https://github.com/Big-Red-Dog-Marketing/brd-headless-wp/';

require __DIR__ . '/includes/settings.php';
require __DIR__ . '/includes/headless.php';
require __DIR__ . '/includes/hardening.php';
require __DIR__ . '/includes/rebuild.php';
require __DIR__ . '/includes/updates.php';

register_activation_hook(__FILE__, fn() => brd_headless_write_all_htaccess());
register_deactivation_hook(__FILE__, function () {
    brd_headless_write_all_htaccess(true);
    wp_clear_scheduled_hook('brd_headless_rebuild');
});
