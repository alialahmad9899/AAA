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
