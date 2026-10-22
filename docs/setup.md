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
- **WP-Cron (server cron every 5 minutes):** saves don't build straight away. Each publish, edit, reorder or term change queues one rebuild 5 minutes out (the next change restarts the timer), and the queued rebuild, like scheduled posts, is a WP-Cron event. WP-Cron only runs when someone loads the CMS, and a headless install gets almost no traffic, so run it from the host's server cron every 5 minutes ([Tigertech's WordPress cron setup](https://support.tigertech.net/wordpress-cron)). The interval is how long a publish waits to start building, not how often the site builds: a run with nothing due does nothing, so the site builds once per editing session plus once a night. On Tigertech, once per site over SSH:
  1. Get the login from [support.tigertech.net/shell](https://support.tigertech.net/shell) (enter the domain in the yellow box): the exact `ssh` command and which password (the hosting account's, not a WordPress login). The first connection shows the server's key fingerprint; type `yes`.
  2. Check: `ls ~/html/wp-config.php` (WordPress's directory; adjust every line below if it's elsewhere), `cd ~/html && /usr/local/bin/wp cron event list`, `which curl`, and `time /usr/local/bin/wp cron event run --due-now`, whose `user` + `sys` must be well under 5 seconds (Tigertech allows one CPU second per minute of interval).
  3. `crontab -e` (nano), add these at the bottom, one line each, then Ctrl+O, Enter, Ctrl+X:
     ```
     */5 * * * *  cd ~/html && /usr/local/bin/wp cron event run --due-now > /dev/null
     5 0 * * *    curl -fsS -X POST "<Deploy Hook URL>" > /dev/null
     ```
     Normal output is discarded; errors are emailed to the account's admin contact. Without curl, use `/usr/bin/wget -q -O /dev/null --post-data='' "<Deploy Hook URL>"`.
  4. Then add `define('DISABLE_WP_CRON', true);` to `wp-config.php`, above "That's all, stop editing!", so WordPress stops also running cron on page loads.
  5. Test: publish a post (the admin bar shows "Rebuild site now (queued, 5 min)") and see a build start within about 10 minutes; schedule a post a few minutes out, log out, and see it go live.

  The nightly line rebuilds just after midnight so date-based content rolls over. "Rebuild site now" builds at once without cron, and is how media library, menu and settings changes (which queue nothing) reach the site before the nightly build. No Cloudflare cron is needed.

## 4. Before launch

- **Login wall:** if the domain's DNS is on Cloudflare, proxy `wp.` and add a Zero Trust Access application on `wp-login.php` and `/wp-admin/` (free up to 50 users, email one-time PIN). Leave `/wp-json/` open; the build needs it. Turn on Cloudflare's free managed WAF rules. If DNS stays with the host, ask what brute-force protection the host applies to `wp-login.php`, and add Wordfence (free) if the answer is "none".
- **Backups:** daily, off the server, several kept; test one restore. After launch WordPress is the only copy of the content.
- **Checks** (logged out): `/wp-json/wp/v2/users` returns 404; `/xmlrpc.php`, `/readme.html` and a `.php` URL under `/wp-content/uploads/` return 403; `/?author=1` goes to the public site; the content endpoints the build uses return 200; `/robots.txt` disallows everything.
