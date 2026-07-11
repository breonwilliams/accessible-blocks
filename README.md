# Guardrail Blocks

> Native WordPress blocks where accessibility is **enforced by design** — WCAG-safe color contrast and an unbreakable semantic heading hierarchy, guaranteed at author time *and* render time. No QA cleanup pass.

**Status:** Phase 0 (scaffold). See [`docs/PROJECT_FOUNDATION.md`](docs/PROJECT_FOUNDATION.md) for the full brief and roadmap.

## The two guarantees

1. **WCAG-safe contrast** — color pairings come from the theme's `theme.json` palette and are validated/corrected against WCAG AA. A failing pairing is not a state the blocks can publish.
2. **Unbreakable heading hierarchy** — heading levels are *derived* from document position via the Block Context API, never hand-picked. Reordering and nesting recompute the outline; it can never skip a level.

## Development

```bash
npm install          # JS toolchain (@wordpress/scripts + TypeScript)
composer install     # PHP coding-standards tooling
npm run build        # compile blocks into build/
npm start            # watch mode
```

Lint and checks: `npm run lint:js`, `npm run lint:style`, `npm run check-types`, `composer run lint:php`.

## Requirements

- WordPress 6.9+
- PHP 8.0+

## License

GPL-2.0-or-later.
