# CKEditor Glossary for Craft CMS

Link words in your [CKEditor](https://plugins.craftcms.com/ckeditor) content to glossary terms and
show their definitions in accessible front-end tooltips — a Craft CMS take on Drupal’s
[CKEditor Taxonomy Glossary](https://www.drupal.org/project/ckeditor_taxonomy_glossary) module.

Editors select text, pick a term from an autocomplete (with a language badge for multi-site), and
visitors get the definition in a tooltip on hover or keyboard focus. Definitions are fetched live,
so edits stay in sync.

## Requirements

- Craft CMS 5.9.0 or later
- PHP 8.2 or later
- [CKEditor](https://plugins.craftcms.com/ckeditor) (`craftcms/ckeditor`) 5.0.0 or later

## Installation

```bash
composer require little-miss-robot/craft-ckeditor-glossary
./craft plugin/install ckeditor-glossary
```

Or install it from the in-app Plugin Store.

## Setup

1. **Add the button.** Edit a CKEditor field (Settings → Fields) and drag the **Glossary link**
   button into the toolbar.
2. **Grant permissions.** Under a user group’s permissions → *CKEditor Glossary*:
   - **Administer glossary terms** — create/edit/delete terms (and create them from the editor).
   - **Link to glossary terms** — search and insert glossary links.
3. **Add terms.** Go to **Glossary → Terms** and add a term (its title) and a definition. You can
   also create terms on the fly from the editor.
4. **Show tooltips on the front end** — see below.

## Front-end tooltips

Include the tooltip CSS/JS where glossary content renders:

```twig
{% do craft.ckeditorGlossary.registerTooltipAssets() %}
```

Or enable **Autoload tooltip assets** in the plugin settings to include them on every front-end
page automatically.

Glossary links are saved as:

```html
<a class="glossary-link" data-glossary-uid="…">your text</a>
```

They remain readable without JavaScript (progressive enhancement). The tooltip is injected as
`.glossary-tooltip` and wired up with `aria-describedby`; dismiss with `Esc`.

### Theming

Override the defaults with the `.glossary-link` and `.glossary-tooltip` selectors in your own CSS.

## How terms are stored

Terms are a dedicated **Glossary Term** element:

- A per-site, plain-text **Definition** feeds the tooltip by default.
- The term’s **field layout is configurable** (Glossary → Settings) — add rich-text, Matrix, or any
  field. Point **Tooltip field handle** at a CKEditor/rich-text field for rich tooltips.
- Set a **URI format + template** per site to give terms their own **detail pages**.

References are stored as the element **UID**, independent of the backing storage, so the source can
evolve (e.g. additional category/entry sources) without breaking existing content.

## Multi-site

Terms and their definitions are site-aware. Autocomplete shows a language badge, and the front-end
API returns the definition for the requested site.

## Configuration

Copy `vendor/little-miss-robot/craft-ckeditor-glossary/src/config.php` to
`config/ckeditor-glossary.php` to override settings per environment (tooltip field handle, cache
duration, autoload, per-site detail pages).

## Templating

```twig
{# All terms #}
{% for term in craft.ckeditorGlossary.terms.all() %}
    {{ term.title }} — {{ term.definition }}
{% endfor %}

{# Resolve a single term by UID #}
{% set term = craft.ckeditorGlossary.resolve(uid) %}
```

## Building the CKEditor plugin

The compiled CKEditor package (`src/web/assets/glossary/build/glossary.js`) is committed. To rebuild
it after changing the editor plugin source in `ckeditor5-glossary/`:

```bash
cd ckeditor5-glossary
npm install
npm run build
```

## License

MIT. See [LICENSE.md](LICENSE.md).
