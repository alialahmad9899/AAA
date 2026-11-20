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
