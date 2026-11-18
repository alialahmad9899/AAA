# Wesal Auth Entry and Branding Implementation Plan

> **For agentic workers:** Execute inline with superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Correct logo clipping/duplication and alignment across the signed-out entry, login, and signup screens, and add a branded animated intro.

**Architecture:** Add one lightweight intro route and one reusable fade/slide route helper using Flutter SDK animations only. Keep the existing screens and authentication flow; make targeted layout changes in their original files.

**Tech Stack:** Flutter 3.47.9, Dart, flutter_test; no new dependencies.

**Spec:** docs/superpowers/specs/2026-11-19-wesal-auth-entry-design.md

## Global Constraints
- Use AppColors.primaryTeal, secondaryTeal, accentWarm, and backgroundColor as already defined; do not add a new palette.
- Use the unchanged asset assets/images/wesal_logo.png and BoxFit.contain.
- Keep logged-in startup routed directly to MainHomeScreen.
- Preserve RTL, login/signup validation, API calls, and form behavior.
- Do not modify public website, Laravel, notification delivery, or database.

## Review Focus
- Narrow phone widths: the logo must remain fully visible without horizontal clipping.
- Short-height devices: welcome/login/signup remain scrollable and do not overflow.
- Logged-in users: the new intro must never delay or replace MainHomeScreen.
- Timer disposal/navigation: leaving or disposing the intro must not trigger a late navigation.
- Arabic RTL: back/step controls stay at their intended ends while logo and hero text remain horizontally centered.

---

### Task 1: Lock down auth and intro presentation behavior with widget tests

**Files:**
- Modify: `فلاتر/test/widget_test.dart`
- Create: `فلاتر/test/wesal_auth_entry_test.dart`

**Interfaces:**
- Consumes: current LoginScreen, SignupScreen, WelcomeScreen, MyApp and WesalLogo APIs.
- Produces: regression coverage for intro routing, exactly one centered login logo, centered signup branding, and responsive contain sizing on welcome.

- [ ] **Step 1: Write failing widget tests**
  - Assert a signed-out MyApp contains a widget whose runtime type is BrandIntroScreen.
  - Assert LoginScreen contains exactly one WesalLogo, centered on the viewport, and its welcome heading uses TextAlign.center.
  - Assert SignupScreen's logo is centered independently of the back/step controls and its hero heading is centered.
  - Assert WelcomeScreen's logo uses BoxFit.contain with a wide responsive render box (at least 220 logical px wide and no more than 145 logical px high).
- [ ] **Step 2: Run tests to verify the existing layout fails**
  - Run: `cd فلاتر && flutter test test/wesal_auth_entry_test.dart test/widget_test.dart`
  - Expected: current login test finds two logos; signup centering and signed-out intro route assertions fail.

### Task 2: Add the cinematic intro and restrained route transition

**Files:**
- Create: `فلاتر/lib/screens/brand_intro_screen.dart`
- Create: `فلاتر/lib/utils/wesal_page_route.dart`
- Modify: `فلاتر/lib/main.dart`

**Interfaces:**
- Produces: `BrandIntroScreen({String currentLanguage = 'ar'})` and `WesalPageRoute<T>({required Widget child})`.
- Consumes: `AppColors`, `WesalLogo`, and the existing `WelcomeScreen`.

- [ ] **Step 1: Implement the animated intro**
  - Animate the unchanged logo with a subtle scale (0.94 to 1.0) and fade-in on the existing teal gradient palette.
  - Reveal a short supporting line after the logo; do not crop or tint the image.
  - After 2.6 seconds, replace the intro with WelcomeScreen using a 520 ms fade and slight vertical movement.
  - Cancel the timer and dispose the animation controller safely.
- [ ] **Step 2: Implement the reusable page transition**
  - Use a 520 ms fade plus a small 0.02 vertical slide; reverse duration 360 ms.
- [ ] **Step 3: Route signed-out startup through BrandIntroScreen**
  - Keep signed-in startup on MainHomeScreen without the intro.

### Task 3: Correct welcome, login, and signup brand alignment

**Files:**
- Modify: `فلاتر/lib/screens/welcome_screen.dart`
- Modify: `فلاتر/lib/screens/login_screen.dart`
- Modify: `فلاتر/lib/screens/signup_screen.dart`

**Interfaces:**
- Consumes: `WesalPageRoute<T>` from Task 2 and the existing `WesalLogo` API.
- Produces: centered single-logo login hero, centered signup brand header, and responsive welcome logo sizing.

- [ ] **Step 1: Fix welcome logo sizing**
  - Use a 250 × 135 logical-pixel contain box for the logo inside the welcome hero; preserve scroll behavior.
- [ ] **Step 2: Fix login duplication and alignment**
  - Remove the separate 72 × 72 logo from the top navigation row.
  - Keep the back button in that row.
  - Show exactly one logo, centered inside the teal panel above the centered Arabic/English heading and description.
- [ ] **Step 3: Center signup branding without moving controls**
  - Keep back and step controls at opposite ends of the top row.
  - Move the logo below that row, centered above centered title/subtitle; leave all fields and form state unchanged.
- [ ] **Step 4: Apply the fade/slide transition to welcome → login/signup and login → signup navigation**

### Task 4: Verify and deliver

**Files:**
- Update: relevant widget tests and PROJECT_NOTES.md append-only entry.

- [ ] **Step 1: Run targeted tests**
  - Run: `cd فلاتر && flutter test test/wesal_auth_entry_test.dart test/widget_test.dart test/wesal_branding_test.dart`
- [ ] **Step 2: Run the complete suite and analyzer**
  - Run: `cd فلاتر && flutter test`
  - Run: `cd فلاتر && dart analyze --fatal-infos`
- [ ] **Step 3: Review all changed paths and commit the implementation to main**
- [ ] **Step 4: Verify the main commit, CI results, and package only the files changed in this session**

## Execution choice
The user explicitly delegated visual choices and asked that the work be carried through. Execute inline on main, which is the repository's authorized default branch.