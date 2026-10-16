# BRD Headless WordPress

The shared WordPress side of BRD's headless sites: WordPress holds the content, a static front end (Astro on Cloudflare) is built from its REST API and serves the public site.

| | What it is |
|---|---|
| `plugin/brd-headless/` | **BRD Headless** plugin. Redirects every front-end request to the public site, keeps the CMS out of search engines, hardens the install, and rebuilds the public site when content changes. Updates itself (and the theme) from this repo's releases. |
| `theme/brd-headless-theme/` | **BRD Headless** theme. WordPress needs one active theme; this one is three files, turns on featured images and renders previews. |
| `docs/setup.md` | Checklist for a new site, from hosting to launch. |
| `docs/hardening.md` | What the plugin hardens, what's left to the host and the site owner, and the sources behind it. |

Each site keeps its own content model (custom post types, taxonomies, ACF field groups) in a small per-site plugin in the site's repo. Nan's Dance (`nans-dance`, `wordpress/nans-headless/`) is the reference.

## What the plugin does

- **Headless:** front-end URLs 301 to the same path on the public site (previews still render here). REST, admin, cron and `robots.txt` stay on the CMS. "View" links in the editor point at the public site; a site whose paths differ filters `brd_headless_view_url`.
- **Never indexed:** `X-Robots-Tag: noindex, nofollow, noimageindex` on pages, REST and every static file, `robots.txt` disallows everything, no sitemaps, no attachment pages.
- **Hardened** (details in `docs/hardening.md`): no unfiltered HTML for anyone, no dashboard file editing, no anonymous user list, `?author=` can't reveal a login, XML-RPC off and blocked, `readme.html`/`license.txt` blocked, no PHP from uploads, security headers on every response, no version number, comments/emojis/oEmbed off, core updates don't add new default themes.
- **Rebuilds:** publishing, updating, unpublishing or trashing content, changing terms, or reordering with Post Types Order queues one rebuild 5 minutes after the last change. "Rebuild site now" in the admin bar builds immediately.

### Settings

Settings > Reading > **Headless site**:

| Setting | wp-config.php constant (locks the field) |
|---|---|
| Public site URL, e.g. `https://www.example.com` | `BRD_HEADLESS_PUBLIC_URL` |
| Site rebuild hook (Deploy Hook URL) | `BRD_HEADLESS_REBUILD_HOOK` |

Until the public URL is set, nothing is redirected and the dashboard shows a warning.

### Filters

| Filter | Use |
|---|---|
| `brd_headless_view_url( $url, $post, $public_base )` | Public URL for a published item's "View" link. Return `null` to keep the CMS link. |
| `brd_headless_rebuild_post_types( $types )` | Post types whose changes rebuild the site. Default: every REST-visible type with an admin screen, except attachments and WordPress internals. |

## Updates

The plugin checks this repo's latest GitHub release (assets `brd-headless.zip` and `brd-headless-theme.zip`) and offers updates on the normal Plugins and Themes screens, so auto-updates work too. While the repo is private, each site needs a read-only token in `wp-config.php`:

```php
define('BRD_HEADLESS_GITHUB_TOKEN', 'github_pat_...');
```

Make it a fine-grained token with access to this repository only and **Contents: Read-only**. If the repo is made public, the token isn't needed.

Library: [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) 5.7 (MIT), vendored in `plugin/brd-headless/vendor/`.

## Releasing

1. Bump the version in three places: `Version:` and `BRD_HEADLESS_VERSION` in `plugin/brd-headless/brd-headless.php`, and `Version:` in `theme/brd-headless-theme/style.css`. Plugin and theme always share a version.
2. Add a `CHANGELOG.md` entry.
3. Push to `main`, then push a tag `vX.Y.Z`. The release workflow checks the versions match the tag, lints the PHP, builds both zips and publishes the release. Sites pick it up within 12 hours (or click "Check for updates" on the Plugins screen).
