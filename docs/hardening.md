# Hardening

What the BRD Headless plugin does to secure a headless WordPress, what it leaves to the host and the site owner, and why. Researched 2026-10-16 against the sources at the end.

## The risk on a headless CMS

The CMS has no public pages, so the real risks are:

1. **Someone logging in**, with a guessed or stolen password or a leaked application password. Anything saved in WordPress is published on the public site at the next build, so a hijacked account can deface the public site.
2. **A vulnerable plugin.** Patchstack's 2026 report puts 91% of 2025's 11,334 new WordPress vulnerabilities in plugins (core had 6); about half of the high-impact ones were exploited within 24 hours, and 46% had no fix at disclosure. The fewer plugins, the smaller the target.

## In the plugin

| Measure | Why |
|---|---|
| `DISALLOW_UNFILTERED_HTML` | By default Administrators and Editors can save raw script into content. The public site renders content as REST serves it, so this closes the path from a hijacked account to script on the public site. Content otherwise passes WordPress's normal allowed-HTML rules. |
| `DISALLOW_FILE_EDIT` | No plugin or theme code editing from the dashboard (WordPress hardening guide). Zip uploads still work. |
| No user list for anonymous REST | `/wp-json/wp/v2/users` gives away login names. Builds don't read users; authenticated scripts still can. |
| Front-end redirect at priority 1 | Runs before core's canonical redirect, which would turn `?author=1` into the author's login slug. |
| XML-RPC off and `xmlrpc.php` blocked (403) | Brute-force and amplification target; nothing headless uses it. |
| `readme.html`, `license.txt` blocked; no generator tag | They reveal the WordPress version. |
| No PHP from `wp-content/uploads` | A common foothold after an upload bug. |
| Headers on every response, REST and static files included | HSTS (1 year), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Robots-Tag: noindex`. |
| Comments, trackbacks, emojis, oEmbed off | Unused surface. |
| `CORE_UPGRADE_SKIP_NEW_BUNDLED` | Core updates don't install each new default theme. |

Apache rules live in "BRD Headless" marker blocks in the root and uploads `.htaccess`, rewritten on admin page loads if a permalink save or anything else drops them, and emptied on deactivation. On a non-Apache host (nginx), the file blocks and static-file headers need the host's equivalent.

Because the measures live in the plugin, switching themes can't switch them off.

## Left to the site owner and host

See `setup.md` steps 2 and 4: two-factor for every account (Two Factor plugin; the standalone Wordfence Login Security plugin is reportedly being discontinued), Editor role for clients, application passwords only while needed, auto-updates on, minimal plugins, a login wall (Cloudflare Access) or host brute-force protection, daily off-site backups. From the WordPress guide, for the host: HTTPS admin, files 644 and folders 755, `wp-config.php` 400 or 440, a non-default table prefix.

## Not needed

Front-end hardening aimed at public pages (CSP, page caching or security plugins), Akismet (no comments), renaming the login URL (it only hides it; Cloudflare Access does the job properly).

## Sources (read 2026-10-16)

- [Hardening WordPress](https://developer.wordpress.org/advanced-administration/security/hardening/), WordPress Advanced Administration Handbook, updated 2026-10-14
- [State of WordPress Security in 2026](https://www.patchstack.com/whitepaper/state-of-wordpress-security-in-2026/), Patchstack (a security vendor)
- [Two Factor](https://wordpress.org/plugins/two-factor/), WordPress.org plugin page (0.17.0)
- [Best WordPress two-factor plugins in 2026](https://www.fs-code.com/blog/best-wordpress-two-factor-authentication-plugins), fs-code, June 2026
- [WordPress Security Hardening Checklist for 2026](https://mecanik.dev/en/posts/wordpress-security-hardening-checklist-2026/), Mecanik
- [Protecting WordPress login pages with Cloudflare Zero Trust](https://runcloud.io/blog/wordpress-login-pages-cloudflare-zero-trust), RunCloud
- [Disabling download of new default themes](https://wp-kama.com/2156/disabling-update-download-of-new-versions-of-default-themes-and-plugins) (`CORE_UPGRADE_SKIP_NEW_BUNDLED`), wp-kama
