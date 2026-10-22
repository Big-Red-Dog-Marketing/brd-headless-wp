# Changelog

## 1.1.0 (2026-10-22)

- Plugin: editor limits so content stays inside what the public site renders: allowed blocks (paragraph, h2-h3 headings, lists, images, quotes, separators, tables; filter `brd_headless_allowed_blocks`), no code editor or block locking, heading levels (filter `brd_headless_heading_levels`), uploads scaled to a 2880px long edge (filter `brd_headless_max_image_edge`). Moved from the astro-starter's separate editor-constraints file.
- Theme: `theme.json` locks custom colors, gradients, font sizes and families, spacing, borders and shadows; the site's design tokens own those.
- Docs: WP-Cron runs from the host's server cron (every 5 minutes) plus a nightly deploy-hook call, instead of a Cloudflare Worker cron.

## 1.0.1 (2026-10-16)

- Theme: Big Red Dog logo as the theme screenshot.
- Releases publish on a push to `main` whose version has no release yet (no tag push needed).

## 1.0.0 (2026-10-16)

First release, generalized from the Nan's Dance CMS plugin (nans-headless 1.4.0) and theme.

- Plugin: headless redirect, noindex lockdown, hardening, debounced rebuild hook with "Rebuild site now", "View" links to the public site, updates from GitHub releases. Settings under Settings > Reading > Headless site.
- Theme: blank theme with featured images and a preview template.
