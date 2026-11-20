# Wesal website follow-up — 2026-11-20

## Homepage branding
- Replaced the text-only homepage wordmark at the top and footer with the repository's existing transparent asset `public/assets/img/wesal-legal-logo.png`.
- Kept the logo linked to the homepage and sized it with responsive `object-fit: contain` styling.
- Removed the old text wordmark from the homepage header and footer.

## Language persistence and rendering
- Added a dedicated `wesal_website_locale` cookie (one-year preference) and `website_locale` session key for manual Arabic/English selection.
- Kept the legacy `locale` session value synchronized for existing translation helpers and migrated existing `ar`/`en` session values.
- Added locale-aware private cache headers so a shared page cache cannot return Arabic HTML to an English visitor (or vice versa).
- Fixed an English-only download section paragraph that previously contained Arabic text when store links were configured.

## Validation
- Expanded `BilingualWebsiteContractTest` for the dedicated locale cookie/session, homepage logo placement, and fully translated English store copy.
- Full Laravel runtime tests and live Hostinger deployment must be run on the deployed application; no production database or hosting changes were made in this patch.

## Live-browser reproduction and follow-up — 2026-11-20
- A read-only browser simulation against the deployed Hostinger site clicked English on the homepage, support page, terms page and privacy page. All four remained Arabic and kept Arabic marked active.
- The published pages also lacked root `html[lang]`/`dir` attributes, unlike the current repository's updated Blade layouts, confirming the deployed website is not serving the current bilingual application code.
- Changed the public language selector to a GET preference endpoint (retaining the POST route for backwards compatibility) so switching does not depend on a stale CSRF form token.
- The support page now skips the global navigation and footer, including their login links and legacy footer logo. It has its own compact language selector and uses the transparent Wesal logo asset.
- Full live verification remains dependent on deploying this PR to Hostinger; no production configuration or data was changed during the simulation.
- Final regression assertions now verify the request-cookie source, GET form method, standalone support layout and branded support-page controls.

## Public website account access removal — 2026-11-20
- Removed desktop and mobile homepage links for website login and account registration.
- Disabled public website `/login`, `/register`, `/users/login`, web password-reset entry points and website registration-verification endpoints with explicit 404 responses.
- Disabled the default Laravel UI registration and password-reset route registration; retained route names for compatibility where the URL is now intentionally blocked.
- Disabled browser-based social sign-in and its account-creation callback. The existing mobile OAuth callback path is retained only when the `mobile_app` hand-off marker is present.
- Kept the separate admin login route at `/admin/login` and the mobile API authentication endpoints `/api/signup`, `/api/signin`, `/api/auth/google`, and `/api/social-login` unchanged.
- The public website is now intentionally informational/contact/legal only; users cannot create or sign into member accounts through website pages.
- Added contract tests for disabled website account access, English document files, and preservation of the separate admin and mobile API auth routes.
