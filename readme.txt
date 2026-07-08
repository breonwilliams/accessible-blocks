=== Accessible Blocks ===
Contributors: breonwilliams
Tags: accessibility, blocks, wcag, contrast, headings
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks where accessibility is enforced by design: WCAG-safe color contrast and an unbreakable heading hierarchy — no QA cleanup pass.

== Description ==

Most accessibility failures on WordPress sites are authoring failures: a brand color that doesn't meet WCAG contrast, a heading hierarchy scrambled by reordering, an accordion built from divs with no keyboard support. They look fine in the editor and fail the audit later.

Accessible Blocks takes a different approach: instead of *offering* accessible options, its blocks make the failure impossible to author — and re-verify everything on every page view.

= The guarantees =

* **WCAG-safe color contrast** — Button backgrounds come only from your theme's palette, and the most readable text color is chosen automatically (the live ratio is shown in the sidebar, e.g. "Contrast 8.36:1 — AA ✓"). The pairing is re-validated server-side on every page view against your theme's *current* palette, so switching themes or changing brand colors can never leave a failing button behind.
* **Unbreakable heading hierarchy** — There is no heading level control anywhere. An Accessible Heading derives its level (H2–H6) from how Accessible Sections are nested, and re-derives it when you reorder, nest, or un-nest — the document outline stays valid by construction. Card titles and accordion headers participate too, always one level below their section's heading.
* **Correct interactive semantics** — The Accordion ships the full WAI-ARIA disclosure pattern: real buttons inside correctly-leveled headings, `aria-expanded`/`aria-controls` wiring, Enter/Space toggling, arrow-key navigation between headers, and content that stays reachable without JavaScript.
* **Performance-correct media** — The Media Figure renders through core's responsive-image pipeline with explicit dimensions (no layout shift), `srcset`/`sizes`, a hero toggle for `fetchpriority="high"` (better LCP) vs. lazy loading, and alt text enforcement with editor warnings.

= The blocks =

Accessible Section, Accessible Heading, Accessible Button, Card + Card Grid, Accessible Accordion, Notice (info/success/warning/error with correct ARIA roles), Media Figure, and a Table of Contents that is generated from the page's real outline on every view — it can never go stale.

= Safety nets =

* The **Outline Checker** panel (in the page sidebar) watches the whole document — including core Heading blocks — and flags skipped levels and extra H1s with one-click block selection.
* Everything inherits your theme's colors, spacing, and typography via `theme.json`. Nothing fights your design.
* The plugin makes **zero external requests** and collects no data.

== Frequently Asked Questions ==

= Does this replace core blocks? =

No. It's a focused set that mixes freely with core blocks — use core paragraphs, images, and columns as usual. The Outline Checker even watches headings you author with the core Heading block.

= What happens to an uncolored button? =

It inherits your theme's own button styling (the same `wp-element-button` mechanism core's Button uses), so it always looks like a button. The contrast guarantee applies the moment you pick a palette background; the theme-default state's contrast is the theme's responsibility, exactly as with core buttons.

= My theme defines palette colors as CSS variables — does the contrast check work? =

Yes. Palettes in the `var(--name, #hex)` format used by many popular themes are fully supported. A color whose value genuinely can't be resolved (for example `color-mix(...)`) is not offered for buttons rather than being silently unchecked, and the sidebar explains why.

= Why can't I pick a heading level? =

That's the point: hand-picked levels are how outlines break. The level always derives from section nesting, so reordering can never skip a level or create a second H1. If you need a specific level, nest (or un-nest) the section — the outline follows the structure.

= What happens if I deactivate the plugin? =

Static content (sections, cards, notices' inner content) remains as clean HTML. Dynamic blocks (headings, buttons, accordions, table of contents) stop rendering their markup but your content is preserved and reappears on reactivation — nothing is lost.

= Does it phone home? =

No. Zero external HTTP requests, no tracking, no accounts.

== Screenshots ==

1. The Starter Brochure Page pattern: hero, feature cards, FAQ, and call-to-action with a guaranteed-valid heading outline.
2. Contrast enforcement in the editor: pick a background from the theme palette and the readable text color is chosen automatically, with the live WCAG ratio shown.
3. The Outline Checker panel lists every heading — including core Heading blocks — and flags outline problems.
4. The Accessible Accordion on the front end: real buttons, correct ARIA, full keyboard support.

== Changelog ==

= 0.1.0 =
* Initial release.
* Blocks: Accessible Section, Accessible Heading, Accessible Button, Card + Card Grid, Accessible Accordion, Notice, Media Figure, Table of Contents.
* Contrast engine (editor + server) with support for hex, rgb(), and var()-fallback theme palettes.
* Heading levels derived from section nesting with self-correction on reorder; Outline Checker panel.
* Accordion built on the WordPress Interactivity API with the WAI-ARIA disclosure pattern.
* Five patterns including the Starter Brochure Page.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
