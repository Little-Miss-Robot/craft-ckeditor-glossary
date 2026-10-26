# Release Notes for CKEditor Glossary

## 1.0.0

### Added
- Glossary term element type with a per-site definition and an admin-configurable field layout (add rich content and optional detail pages).
- CKEditor 5 “Glossary link” toolbar button with term autocomplete (language badge) and create-a-term-on-the-fly.
- Accessible front-end tooltips that fetch the current definition from a cached JSON endpoint.
- Multi-site support: definitions and links resolve per site.
- `Administer glossary terms` and `Link to glossary terms` permissions.
- `craft.ckeditorGlossary` Twig variable (`registerTooltipAssets()`, `terms`, `resolve()`).
- Pluggable glossary source abstraction (`GlossarySourceInterface`), shipping the element-backed source.
