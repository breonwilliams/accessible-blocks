# Guardrail Blocks — Project Foundation

> **Working name:** "Guardrail Blocks" · **Working slug/text-domain:** `guardrail-blocks` · **Block namespace:** `guardrail-blocks/*`
> **Status:** Foundation / pre-scaffold. No code written yet — this document is the brief.
> **Author of foundation:** Prepared with Breon Williams (founder, Promptless WP) for a portfolio-grade, WordPress.org-approvable Gutenberg block plugin.
> **Last updated:** 2026-07-08

---

## 0. How the next session should use this document

This is a **handoff brief**. A fresh Claude Cowork (or Claude Code) session should:

1. Read this entire document first, then read `../CLAUDE.md` for standing guardrails.
2. Confirm the **open decisions** in §16 with Breon (final name/slug availability, min WP version, v1 block cut).
3. Start at **Phase 0** in the roadmap (§14) and build in the listed order.
4. Treat the two **accessibility guarantees** (§4) as the product's reason to exist — every block must honor them.

Local dev site: **"gutenburg"** (Local by Flywheel). This plugin lives at
`wp-content/plugins/guardrail-blocks/`. The site name is just the local environment; it is **not** the plugin name and "Gutenberg" must **not** appear in the plugin name or slug (WordPress.org trademark rule — see §11).

---

## 1. The problem (why this exists)

WordPress content teams ship accessibility failures constantly, and they surface as expensive QA cleanup or, worse, ADA/WCAG complaints after launch. The three most common, most preventable failures:

1. **Color contrast** — authors pick brand colors that fail WCAG 4.5:1, in the editor, with no guardrail. It looks fine to them and fails an audit.
2. **Broken heading hierarchy** — authors hardcode heading levels (H1, then H4, then H2), and every reorder or copy-paste makes it worse. Screen-reader users get a scrambled document outline.
3. **Missing semantics / keyboard support** — accordions and tabs built as `<div>`s with no ARIA or keyboard handling; images with empty alt; CTAs with poor focus states and sub-minimum tap targets.

These are evergreen (accessibility law is only tightening), universal (every content site has them), and **underserved natively** — the block ecosystem offers thousands of blocks that give you *options* but almost none that give you *guarantees*. Fixing this is normally a manual QA pass at the end of every build.

## 2. Product thesis

**Native Gutenberg blocks where accessibility is enforced by design, not left to the author.** The bet is the same one that makes Promptless powerful: *constrain the inputs so broken output isn't a path the tool will take you down.* No QA cleanup at the end, because the blocks can't produce the failure in the first place.

This plugin ports two Promptless behaviors to the **native block editor**:

- **Smart contrast enforcement** — color pairings are validated/corrected to WCAG at author time and at render time.
- **Unbreakable semantic heading order** — heading levels are *derived from document position*, not hand-picked, so reordering never breaks the outline.

> **Architectural note (important):** Unlike Promptless — which uses a custom content schema and its own React editing app — this project is **native-first**: registered Gutenberg blocks, the core Patterns API, `theme.json`, block context, and dynamic (server-rendered) blocks. That contrast is intentional and is itself a strong interview talking point ("Promptless is a custom deployment layer; Guardrail Blocks is native block engineering; I can speak to the tradeoffs of both").

## 3. Who it's for

- Content authors and marketers on WordPress who don't think about a11y but are accountable for it.
- Agencies and in-house marketing/enterprise teams that eat accessibility QA on every launch.
- Higher-ed, government, healthcare, and enterprise sites with hard ADA/WCAG obligations.

## 4. The two accessibility guarantees (the moat)

Everything else is a normal block; these two systems are the differentiator and must be reusable across all blocks.

### Guarantee A — WCAG-safe color contrast
- Colors come from the active theme's `theme.json` palette (theme-agnostic, no hardcoded brand colors).
- In the editor, when an author picks a text/background pairing, compute the WCAG contrast ratio (reuse the **Image-to-Design-Tokens** contrast logic Breon already shipped) and either **block** the invalid pairing or **auto-select** an accessible foreground from the palette.
- At render, output only the validated pairing (never trust the editor state alone).
- Surface the state in `InspectorControls` (e.g., "Contrast: AA ✓ / fails — adjusted").

### Guarantee B — Unbreakable semantic heading hierarchy
- A **Section** container block (`InnerBlocks`) provides a heading-level value via the **Block Context API** (`providesContext`). A top-level Section provides level `2`; a nested Section consumes its parent's level and provides `parent + 1` (capped at 6).
- An **Accessible Heading** block **consumes** that context (`usesContext`) and **derives** its level from position in the tree. The author writes the text; the plugin owns the level.
- The block is **dynamic (server-rendered)**: `render_callback` reads `$block->context['guardrail-blocks/headingLevel']` and emits the correct `<h2>`…`<h6>`. Reordering/re-nesting recomputes the context → the outline auto-corrects and can never skip a level.
- **Outline Checker (safety net):** a document panel that walks the full block tree via `@wordpress/data` (`select('core/block-editor').getBlocks()`), including core Heading blocks, and warns on skipped levels or multiple `<h1>`s. This protects content authored with core blocks too.

### Supporting semantic/keyboard guarantees (apply per relevant block)
- Accordion = ARIA disclosure pattern + keyboard (Enter/Space, focus management).
- Tabs = ARIA `tablist`/`tab`/`tabpanel` + arrow-key navigation.
- Notice/Callout = appropriate `role` (`note`/`status`/`alert`).
- Media/Figure = enforce non-empty `alt` (warn/block on empty) + `<figure>/<figcaption>`.
- Buttons/CTAs = visible focus, minimum 24×24 (ideally 44×44) target size, contrast-safe.

### Guarantee C — performance-correct media (Core Web Vitals)
Images are the biggest Core Web Vitals lever, so the **Media / Figure** block treats correct image *markup* as a guarantee — the same "correct by default" ethos as contrast and headings. We do **not** build an image optimizer; compression/format conversion belongs to core, the host, or a dedicated plugin, and building it here would be over-engineering and a review liability. What we guarantee:
- Renders through core's responsive-image path (server-side dynamic render via `wp_get_attachment_image()` with an accurate `sizes`), so `srcset`/`sizes` are correct → smaller downloads, higher PageSpeed scores.
- Always emits explicit `width`/`height` (or `aspect-ratio`) → **prevents layout shift (CLS)**.
- Sets loading strategy intelligently: an above-the-fold/hero image gets `fetchpriority="high"` + eager loading (**better LCP**); below-the-fold images get `loading="lazy"`.
- Enforces non-empty, meaningful `alt` (already an a11y guarantee) — accessibility and SEO in one.

Scoped to *markup correctness*, which we own; byte-level optimization is intentionally out of scope.

## 4.5 Performance & Core Web Vitals — what we own vs. defer
On-thesis (the JD explicitly wants performance/CWV; the plugin promises "production-ready out of the box"). We own:
- **CLS:** width/height on media, reserved space for interactive blocks, no layout-shifting JS.
- **LCP:** `fetchpriority` on the hero image, minimal critical CSS, no render-blocking third-party assets.
- **INP / JS weight:** ship as little front-end JS as possible; prefer the **Interactivity API** (SSR-friendly) for accordion/tabs.
- **Payload:** per-block styles load only when the block is present (`block.json` `style`), no global CSS dump.

We explicitly **defer**: image compression / AVIF-WebP conversion, CDN, caching, DB work — host/plugin concerns, outside a block library's job.

## 4.6 Spacing & visual polish — theme-driven, not hardcoded
Goal: polished out of the box **without** fighting the active theme.
- Spacing/typography come from the theme's **`theme.json` presets** via block `supports`; the theme's rhythm wins.
- We ship **sensible defaults** (a consistent, tasteful spacing scale) so a bare theme still looks good, but any `theme.json` value overrides them.
- **Honest note on "golden ratio / pixel-perfect":** we deliberately do **not** hardcode a fixed modular/golden-ratio pixel scale into the blocks. On arbitrary themes that recreates the "page-builder override that fights the theme" problem this plugin is philosophically against. Target = "consistent, theme-respecting spacing with good defaults," not enforced pixel-perfection. (Pixel-perfect comps belong inside a *single* design system like Promptless; a cross-theme block library should adapt, not impose.)

## 4.7 Scope calibration — what this is (and isn't)
A **focused, accessibility-first library that coexists with core blocks**, not a universal page builder. The v1 set (§7) covers common **marketing / brochure / content** page structures and is meant to be mixed with core blocks (paragraphs, columns, images) — the wedge is the *enforcement*, not owning every layout. It intentionally does **not** try to handle every dev use case (e-commerce, complex data, app-like UIs). "Blocks for everything" would turn it back into a generic pack — the thing we're differentiating against.

## 5. Design principles
- **Constrain, don't configure.** Prefer guarantees over options. Where a hard guarantee isn't possible, warn loudly at author time.
- **Native-first.** No custom content schema. Registered blocks, core Patterns API, `theme.json`, block context, dynamic blocks. Content must survive plugin deactivation as clean core-ish markup wherever feasible (dynamic blocks degrade to their saved fallback).
- **Theme-agnostic.** Read colors/spacing/type from `theme.json`; never hardcode brand values.
- **Zero external calls.** No phone-home, no CDN dependencies at runtime (privacy + a clean WordPress.org review).
- **Accessible by default, not by toggle.** The a11y behavior is on; you can't switch it off into a broken state.

## 6. v1 scope (MVP) — in / out

**In:**
- The two guarantee systems (contrast util + heading-context system) as shared, reusable code.
- Core block set (§7): Section, Accessible Heading, Button/CTA, Card + Card Grid, Accordion, Tabs, Notice/Callout, Media/Figure, Table of Contents (dynamic).
- Outline Checker document panel.
- A small set of **patterns** + one **starter brochure-page pattern** composed from these blocks.
- `theme.json`-aware color/spacing usage.
- Tests (Jest + Playwright + PHPUnit) and GitHub Actions CI.
- `readme.txt` + Plugin Check-clean for WordPress.org submission.

**Out (defer to v1.x+):**
- FSE full block theme (v1 ships blocks + patterns that work in any block theme).
- A "pro" tier / Promptless integration / MCP Abilities registration (great v2 — see §13, keep the door open, don't build now).
- Import/export, block-locking UI beyond defaults, multilingual patterns.

## 7. Block inventory (v1)

| Block | Type | Key native APIs | The guarantee it embodies |
|---|---|---|---|
| **Section** | static container | `InnerBlocks`, `providesContext` (heading level + landmark), block `supports` (spacing/color) | Provides heading-level context; optional landmark `role`/`aria-label` |
| **Accessible Heading** | **dynamic** | `usesContext`, `render_callback`, `RichText` | Level derived from context; correct `<hN>` guaranteed |
| **Button / CTA** | **dynamic** *(changed from static, 2026-07-08: render.php re-validates contrast against the live palette so Guarantee A's render-time layer holds even if the theme changes after publish)* | `InspectorControls`, `render.php` + shared PHP `Contrast` class | Contrast-safe (all 3 layers); focus-visible; min target size |
| **Card** + **Card Grid** | static (grid uses `InnerBlocks`) | `InnerBlocks`, `supports`, variations | Semantic structure; contrast-safe; keyboard-reachable links |
| **Accordion** (+ Item) | static, interactive | `InnerBlocks`, view script | ARIA disclosure + keyboard |
| **Tabs** (+ Tab) | static, interactive | `InnerBlocks`, `providesContext`, view script | ARIA tablist + arrow-key nav |
| **Notice / Callout** | static | variations (info/success/warning/error), `supports` | Correct `role`; contrast-safe |
| **Media / Figure** | static | `MediaPlaceholder`, attribute validation | Enforced non-empty alt; `<figure>/<figcaption>` |
| **Table of Contents** | **dynamic** | `render_callback` reading the heading outline | Server-rendered from the real outline; stays correct |

**Patterns (v1):** accessible hero, feature/card grid, FAQ-ish section, CTA band, and one **"Starter Brochure Page"** pattern that assembles Section → Heading → content → CTA into a complete, accessible page skeleton. (Ties back to Breon's original "spin up a brochure site" idea, delivered natively.)

## 8. Technical architecture

- **Registration:** each block is a folder with `block.json` (canonical metadata registration via `register_block_type( __DIR__ . '/build/<block>' )`, driven by a manifest or a loop over `build/*`).
- **Build tooling:** `@wordpress/scripts` (`wp-scripts`) with **TypeScript** and SCSS. `block.json` `editorScript`/`script`/`viewScript`/`style` wiring.
- **Shared packages (the moat, reused everywhere):**
  - `src/utils/contrast.ts` — WCAG ratio + accessible-pairing selection (port from Image-to-Design-Tokens).
  - `src/utils/outline.ts` — block-tree walk + heading-level derivation + skipped-level detection.
- **Dynamic blocks:** `render.php` per dynamic block; read `$block->context` for heading level; escape all output (`esc_html`, `wp_kses_post` where markup is allowed).
- **Editor integrations:** the Outline Checker registered via `registerPlugin` + `PluginDocumentSettingPanel`, reading `core/block-editor` store.
- **Interactivity:** prefer the **Interactivity API** (`@wordpress/interactivity`) for accordion/tabs view logic (modern, SSR-friendly) — or a small vanilla `viewScript` if simpler for v1. Decide in Phase 3.
- **i18n:** text domain `guardrail-blocks`; all strings translatable.
- **PHP:** 8.0+ target, namespaced, prefixed (`GuardrailBlocks\`), no global leakage.

## 9. The "no QA cleanup" enforcement layers

Every guarantee is enforced at up to three layers so nothing slips:

1. **Constrained input** — the editor UI won't let you create the failure (level is derived; colors limited to safe pairings).
2. **Editor-time check** — warnings/auto-fix surfaced in Inspector + the Outline Checker for anything authored outside our blocks.
3. **Render-time guarantee** — dynamic blocks emit the correct semantics/colors regardless of stored editor state.

## 9.5 Testing discipline & per-phase Definition of Done
"No QA cleanup" is a *process*, not a hope. **No phase is complete until it meets this Definition of Done:**
- Unit/**smoke tests** for the phase's code (Jest for utils/JS, PHPUnit for dynamic render) written and green.
- **Edge-case tests** for that phase pass (checklist below).
- **Manual verification in the "gutenburg" Local site:** insert the block(s), exercise the behavior, check the rendered front end, run a quick a11y pass (keyboard + an axe-style checker) and a Lighthouse/PSI spot-check.
- Lint (JS/CSS/PHPCS incl. accessibility ruleset) and **Plugin Check** clean.

Only then move on. This catches issues per-iteration instead of in a big cleanup at the end.

### Edge-case checklist (exercise as blocks are built)
- Deeply nested Sections → heading level caps at `<h6>`, never skips.
- Reorder / duplicate / copy-paste → outline stays correct.
- Our blocks **mixed with core Heading blocks** → Outline Checker still catches skips.
- Dark / low-contrast `theme.json` palettes → contrast system still yields AA pairs.
- RTL languages; long/overflowing content.
- **Keyboard-only** navigation + screen-reader labels for accordion/tabs.
- **No-JS fallback** for interactive blocks (progressive enhancement — content still reachable).
- **Plugin deactivated** → dynamic blocks degrade to a sensible saved fallback; no fatal, no raw markup soup.
- Empty states (no image/alt, empty section) handled gracefully.

## 10. Tech stack & tooling summary
- `@wordpress/scripts`, `block.json`, React 18, **TypeScript**, SCSS.
- PHP 8.0+, namespaced/prefixed; `register_block_type` from metadata.
- Testing: **Jest** (JS units — contrast/outline utils), **Playwright** (`@wordpress/e2e-test-utils-playwright` — editor E2E: level auto-derivation, contrast enforcement), **PHPUnit** (dynamic render output).
- **GitHub Actions CI** — lint (`wp-scripts lint-js`/`lint-style`/PHPCS with WordPress + accessibility rulesets), unit, E2E, and **Plugin Check**.
- Local: the "gutenburg" Local site for manual testing; `wp-env` optional for CI parity.

## 11. WordPress.org compliance checklist
Breon has shipped two approved .org plugins (Promptless Forms, Image To Design Tokens), so the pipeline is known. For this plugin specifically:
- [ ] GPLv2-or-later; all bundled code GPL-compatible.
- [ ] Name/slug **must not** contain "WordPress" or "Gutenberg" (trademark). "Guardrail Blocks" is fine pending slug availability check.
- [ ] Unique prefix everywhere (`guardrail-blocks` / `GuardrailBlocks\` / `guardrail_blocks_`).
- [ ] Sanitize inputs, escape all output, verify nonces/capabilities on any server actions.
- [ ] i18n with the correct text domain; no hardcoded user-facing strings.
- [ ] **No external HTTP calls** without explicit consent (there are none by design).
- [ ] No obfuscated/minified-only code without source; readable build.
- [ ] `readme.txt` with `Stable tag`, tested-up-to, clear description, screenshots.
- [ ] **Plugin Check (PCP)** passes clean.
- [ ] Follows the WordPress **accessibility coding standards** (the plugin should model what it preaches).

## 12. Scalability plan
- Block registry pattern so new blocks are added by dropping a folder + registering — no core rewrites.
- Shared `contrast`/`outline` utils are the reusable engine; new blocks consume them.
- Extensibility: expose filters (e.g., `guardrail_blocks_min_contrast`, `guardrail_blocks_heading_base_level`) so other developers/themes can tune behavior — good open-source citizenship and a senior signal.
- Block-theme/FSE compatible from day one (reads `theme.json`); a matching starter block theme is a clean v1.x add.

## 13. v2+ optionality (do NOT build in v1, but architect so it's possible)
- **WordPress Abilities API / MCP Adapter registration** — register a couple of Abilities (e.g., "insert the Starter Brochure pattern," "scaffold an accessible section") so the *official* WP 7.0 MCP Adapter lets Claude assemble accessible pages from these blocks through conversation. This is the on-brand, current, non-reinventing-the-wheel way to add the AI angle later. A few hours, high signal — but explicitly **out of v1** so it doesn't dilute the native-block story.
- Optional "pro" patterns or a Promptless tie-in.

## 14. Phased roadmap / milestones

| Phase | Deliverable | Notes / where Claude accelerates |
|---|---|---|
| **0** | Scaffold: plugin header, `wp-scripts` + TS + SCSS, `block.json` loader, CI skeleton, `readme.txt` stub | Fast with Claude; establishes the canonical structure |
| **1** | `contrast.ts` util + first contrast-enforced block (Button/CTA) + Jest tests | Port I2DT logic; prove Guarantee A end-to-end |
| **2** | `outline.ts` + **Section** (provides context) + **Accessible Heading** (dynamic, consumes context) + PHPUnit render test | The centerpiece; prove Guarantee B including reorder |
| **3** | Interactive blocks: Accordion + Tabs (ARIA + keyboard), decide Interactivity API vs viewScript | a11y patterns; Playwright keyboard tests |
| **4** | **Table of Contents** dynamic block + **Outline Checker** document panel | Reads the real outline; safety net for core blocks |
| **5** | Card/Card Grid, Notice/Callout, Media/Figure + variations + **patterns** incl. Starter Brochure | Rounds out the set; demo-able page |
| **6** | Full test matrix (Jest/Playwright/PHPUnit) green in **GitHub Actions** | The "real engineer, not site builder" proof |
| **7** | `readme.txt`, screenshots, **Plugin Check** clean, submit to WordPress.org | Reuses Breon's prior .org experience |

> **Every phase above must meet the per-phase Definition of Done in §9.5** (smoke + edge-case tests + manual Local verification + Plugin Check) before moving on.

## 15. Portfolio & positioning (why we're building it this way)
- **Ship it public:** GitHub repo with readable, incremental commits and visible tests; a live demo page; a case-study README (problem → architecture → the two guarantees → results). Market data says hiring engineers value live links + GitHub with tests over PDFs.
- **Resume line (add once shipped):** *"Guardrail Blocks — a native Gutenberg block library (block.json, TypeScript React edit/save, dynamic/server-rendered blocks, Block Context API, patterns, theme.json) that enforces WCAG contrast and unbreakable heading hierarchy by design; Jest/Playwright/PHPUnit + GitHub Actions CI; published on WordPress.org."*
- **Interview talking points:** Block Context API for derived heading levels; dynamic blocks + `render_callback`; the three-layer enforcement architecture; the deliberate native-vs-Promptless architectural contrast; WCAG/ARIA specifics.
- **Brand tie-in:** the build is also social content (the same "no QA cleanup / accessibility by design" narrative as Promptless), so it feeds Breon's X/LinkedIn plan.

## 16. Open decisions — **LOCKED with Breon, 2026-07-08**
1. **Final name + slug** — **"Guardrail Blocks" / `guardrail-blocks`.** Directory search on 2026-07-08 found no existing plugin at that slug (nearest: "Block Accessibility Checks," "WP Accessibility"). Caveat: pending submissions can reserve slugs invisibly — final confirmation happens at Phase 7 submission; have a fallback name ready ("A11y Guard Blocks" or "Guardrail Blocks").
2. **Minimum WP version** — **WP 6.9** (current 7.0 − 1). **PHP 8.0+** floor confirmed. Note: 6.9 floor means `wp_register_block_metadata_collection()` (6.7+) and the Interactivity API are always available.
3. **v1 block cut** — **defer Tabs to v1.1.** Ship 8 blocks: Section, Accessible Heading, Button/CTA, Card + Card Grid, Accordion, Notice/Callout, Media/Figure, Table of Contents. Tabs is the heaviest ARIA/keyboard pattern and Accordion proves the same interactive-a11y story.
4. **Interactivity approach** — **Interactivity API** (`@wordpress/interactivity`) for Accordion (and Tabs when it lands in v1.1).
5. **License/readme specifics**, screenshots, demo host — still open; decide at Phase 7.

## 17. How to continue (handoff)
New session: read this doc + `../CLAUDE.md` → confirm §16 with Breon → start **Phase 0** → build in roadmap order → keep the two guarantees (§4) sacred → keep it native (no custom schema) → keep it Plugin-Check clean the whole way.
