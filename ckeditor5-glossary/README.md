# @littlemissrobot/ckeditor5-glossary

The CKEditor 5 plugin that powers the **Glossary link** button in the Craft
`little-miss-robot/craft-ckeditor-glossary` plugin.

It adds a `glossaryUid` text attribute, converted to/from
`<a class="glossary-link" data-glossary-uid="…">`, plus a toolbar button with a balloon for
searching terms, creating one on the fly, and unlinking.

## Build

The compiled output is written to `../src/web/assets/glossary/build/glossary.js` (an ES module that
imports `ckeditor5` externally — Craft provides the CKEditor runtime).

```bash
npm install
npm run build      # one-off build
npm run watch      # rebuild on change
```

`glossary.js` is committed in the Craft plugin so it works without a Node toolchain; rebuild and
commit it whenever you change the source here.

## Structure

- `src/index.js` — package entry; exports `Glossary` (name must match `$pluginNames`).
- `src/glossary.js` — aggregates the editing + UI plugins.
- `src/glossaryediting.js` — schema + up/down-casts, registers the `glossary` command.
- `src/glossarycommand.js` — applies/removes the `glossaryUid` attribute.
- `src/glossaryui.js` — toolbar button, balloon, autocomplete, create-on-the-fly, unlink.
- `src/ui/glossaryformview.js` — the balloon form view.
