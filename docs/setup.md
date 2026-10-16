# New headless site setup

From an empty host to a CMS the public site builds from. Nan's Dance (`nans-dance`) is the worked example for each front-end step.

## 1. WordPress

1. Install WordPress on its own host name, e.g. `wp.example.com`, over HTTPS. Keep PHP on a version that still gets security fixes.
2. Settings > Permalinks: Post name.
3. Upload and activate the **BRD Headless** plugin (`brd-headless.zip` from the latest release).
4. Add to `wp-config.php` while the repo is private (see README, "Updates"):
   ```php
   define('BRD_HEADLESS_GITHUB_TOKEN', 'github_pat_...');
   ```
5. Upload and activate the **BRD Headless** theme, then delete every other theme. Site Health will suggest keeping a default theme; ignore it.
6. Settings > Reading > Headless site: set the public site URL (`https://www.example.com`). Leave the rebuild hook for step 3.
7. Install only what the content model needs. Usual set: Advanced Custom Fields, Post Types Order (for drag-and-drop order), and the site's own content plugin from its repo. Delete everything else (Akismet, Hello Dolly).
8. Plugins and Themes: turn on auto-updates for everything, core included.

## 2. Accounts

- One login per person; no shared accounts, no username `admin`.
- Client editors get the **Editor** role, not Administrator.
- Two-factor for every account: the [Two Factor](https://wordpress.org/plugins/two-factor/) plugin (add "Two-Factor Provider: WebAuthn" for passkeys).
- Application passwords skip 2FA by design. Create one only for import or sync scripts, on the account that runs them, and revoke it when the job is done.

## 3. Front end

- **Content:** the build reads public REST only (`/wp-json/wp/v2/...`), no credentials. Reference: `src/lib/wp-client.mjs` and `src/lib/wp-loader.ts` in `nans-dance`.
- **Rebuilds:** create a Deploy Hook for the production branch (Cloudflare: in the Worker's Builds settings), paste it into Settings > Reading > Site rebuild hook, and store it as a `DEPLOY_HOOK_URL` secret for the nightly build.
- **WP-Cron:** the 5-minute rebuild timer and scheduled posts run on WP-Cron, which only fires on traffic, and a headless CMS gets almost none. Have the site Worker request `https://wp.example.com/wp-cron.php` every 15 minutes (Cron Trigger `*/15 * * * *`), plus a daily `0 7 * * *` call to the Deploy Hook. Reference: `wrangler.jsonc` and `src/worker/index.ts` (`scheduled`) in `nans-dance`.

## 4. Before launch

- **Login wall:** if the domain's DNS is on Cloudflare, proxy `wp.` and add a Zero Trust Access application on `wp-login.php` and `/wp-admin/` (free up to 50 users, email one-time PIN). Leave `/wp-json/` and `/wp-cron.php` open; the build and the Worker need them. Turn on Cloudflare's free managed WAF rules. If DNS stays with the host, ask what brute-force protection the host applies to `wp-login.php`, and add Wordfence (free) if the answer is "none".
- **Backups:** daily, off the server, several kept; test one restore. After launch WordPress is the only copy of the content.
- **Checks** (logged out): `/wp-json/wp/v2/users` returns 404; `/xmlrpc.php`, `/readme.html` and a `.php` URL under `/wp-content/uploads/` return 403; `/?author=1` goes to the public site; the content endpoints the build uses return 200; `/robots.txt` disallows everything.
