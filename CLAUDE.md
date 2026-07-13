# Guardrail Blocks — AI Build Guardrails

**Read `docs/PROJECT_FOUNDATION.md` first.** That is the full brief. This file is the short list of standing rules for any session building this plugin.

## Status: LIVE on WordPress.org (approved 2026-07-13)

- **Public page**: https://wordpress.org/plugins/guardrail-blocks
- **SVN URL**: https://plugins.svn.wordpress.org/guardrail-blocks
- **Local SVN checkout**: `/Users/breonwilliams/Local Sites/gutenburg/app/public/wp-content/plugins/guardrail-blocks-svn`
- **SVN username**: BreonWilliams (case-sensitive; SVN password is separate from the WordPress.org one — profiles.wordpress.org → Account & Security)
- **GitHub (canonical dev repo)**: https://github.com/breonwilliams/guardrail-blocks

### Release workflow (SVN is a release system, not git)
1. Bump the version in **all three** places: `Version:` header in `guardrail-blocks.php`, `GUARDRAIL_BLOCKS_VERSION` constant, `Stable tag` in readme.txt (+ changelog entry).
2. `npm run build`, run the full check suite, then `npm run plugin-zip`.
3. Sync the **zip contents** (not the dev tree) into `trunk/`: `guardrail-blocks.php`, `readme.txt`, `includes/`, `build/`, `src/`, `package.json`, `tsconfig.json`. The bundled `src/` is a WordPress.org source-access requirement — never drop it from releases.
4. `svn cp trunk tags/X.Y.Z` — **every** release needs a tag matching `Stable tag`; never point `Stable tag` at trunk.
5. `svn add --force .`, review `svn status`/`svn diff`, then `svn commit --username BreonWilliams`.
6. Screenshots/banners/icons live in SVN `assets/` (repo root, not trunk): `screenshot-1.png`… match the numbered captions in readme.txt.

## What this is
A native WordPress Gutenberg block library whose reason to exist is **accessibility enforced by design**: (A) WCAG-safe color contrast and (B) unbreakable semantic heading hierarchy — "no QA cleanup," the Promptless philosophy ported to native blocks. Portfolio-grade and intended for WordPress.org.

## Non-negotiables
1. **Native-first. No custom content schema.** Registered blocks, `block.json`, core Patterns API, `theme.json`, Block Context API, dynamic (server-rendered) blocks. This project's value is that it is the *opposite* architecture from Promptless. If you find yourself building a custom editing app or a bespoke schema, stop — that's out of scope.
2. **The two guarantees are sacred** (Foundation §4). Every block honors them. Heading level is *derived from block context*, never hand-picked. Colors are validated/corrected against `theme.json`, never hardcoded.
3. **Enforce, don't just offer.** Prefer constraints that make the failure impossible; use author-time warnings only as a safety net.
4. **Zero external HTTP calls** at runtime. No phone-home, no runtime CDN deps.
5. **WordPress.org-clean at every step** (Foundation §11): GPLv2+, unique prefix (`guardrail-blocks` / `GuardrailBlocks\` / `guardrail_blocks_`), sanitize/escape/nonce, i18n, no "WordPress"/"Gutenberg" in the name or slug, **Plugin Check must pass**.
6. **Model what you preach** — the plugin's own admin/editor UI and output must meet WCAG and the WordPress accessibility coding standards.
7. **Ship with tests + CI.** Jest (utils), Playwright (editor E2E), PHPUnit (dynamic render), GitHub Actions. Coverage is part of the deliverable, not optional — it's core to the "real engineer" positioning.

8. **Media = performance-correct, not optimized.** Render images through core's responsive path (`wp_get_attachment_image()` with accurate `sizes`); always emit width/height (CLS), `fetchpriority="high"` on the hero (LCP), `loading="lazy"` below the fold, enforced non-empty alt. Do **not** build compression/format conversion — that's core/host/plugin territory.
9. **Spacing = theme-driven.** Consume `theme.json` spacing/typography presets via block `supports`; ship good defaults but let the theme override. Do **not** hardcode a golden-ratio/pixel-perfect scale (it fights arbitrary themes — the very thing we're against).
10. **Per-phase Definition of Done.** No phase advances until: smoke tests + edge-case tests green, manual verification in the "gutenburg" Local site (keyboard + axe + Lighthouse spot-check), and lint + Plugin Check clean. Iterate per phase; don't defer QA to the end.

## Scope reminder
Focused accessibility-first library that **coexists with core blocks** — not a universal page builder. Performance scope = Core Web Vitals *markup correctness* (CLS/LCP/INP), not image optimization.

## Stack
`@wordpress/scripts` + TypeScript + SCSS; React 18; PHP 8.0+ namespaced; canonical `register_block_type` from `block.json`. Dynamic blocks via `render.php` reading `$block->context`.

## Build order
Follow the roadmap in Foundation §14 (Phase 0 → 7). Do not skip ahead of the two guarantee systems (Phases 1–2); everything else depends on them.

## Before you start coding
~~Confirm the open decisions in Foundation §16 with Breon.~~ **Done (2026-07-08), locked in §16:** name/slug "Guardrail Blocks" / `guardrail-blocks` (final availability check at submission); min **WP 6.9** / **PHP 8.0+**; **Tabs deferred to v1.1** (v1 ships the other 8 blocks); **Interactivity API** for Accordion.

## Out of scope for v1 (do not build yet)
FSE full theme, a "pro" tier, and the **WordPress Abilities API / MCP Adapter** registration. The MCP angle is a deliberate **v2** add (Foundation §13) — architect so it's possible, but building it now would dilute the native-block story that this project exists to prove.
