# Wesal authentication entry and logo layout design

## Intent
Polish the signed-out entry experience using the existing Wesal logo and the app's established teal, warm-coral, and ivory palette. The logo must remain unchanged and fully visible.

## Approved behavior
- The signed-out app opens with a short cinematic intro: the original logo fades/scales into view on a deep teal gradient, followed by a soft fade/slide transition to the existing welcome screen.
- A user with a valid saved token continues directly to MainHomeScreen and does not wait through the intro.
- The welcome hero uses a responsive logo box with BoxFit.contain so the complete wordmark remains visible on narrow devices.
- Login has exactly one logo, centered inside the teal welcome panel immediately above the centered Arabic welcome heading and centered supporting text. The separate logo in the top navigation row is removed; the back control remains.
- Signup keeps the back and step controls at opposite ends of its header, while the logo, heading, and supporting line are visually centered. Form fields and Arabic RTL behavior remain unchanged.
- Entry transitions from welcome to login/signup use a restrained fade with a small vertical movement.

## Constraints and non-goals
- Use the existing AppColors palette and assets/images/wesal_logo.png; do not redraw, recolor, crop, or replace the source image.
- Do not add packages or modify APIs, authentication logic, notification resources, database, or public website.
- Respect reduced/compact screen sizes and allow the existing scroll views to handle overflow.
- Maintain the current authenticated startup route and all form validation/submission behavior.

## Approach
Use one small reusable route transition and one dedicated intro screen with Flutter's built-in animation APIs. Keep screen-specific layout changes in the existing WelcomeScreen, LoginScreen, and SignupScreen files. Add widget regression tests for logo count, positioning, fit, and signed-out startup routing.