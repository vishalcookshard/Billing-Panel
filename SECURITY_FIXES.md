# Security fixes applied

- XSS sanitization: ensured `Page` and `ServiceCategory` models sanitize HTML on set using HTML Purifier (or a strip_tags fallback).
- Blade outputs that render sanitized HTML remain as raw outputs (`{!! ... !!}`) but are safe because of model sanitization.
- `.env.example` updated to enforce safer defaults:
  - `DB_PASSWORD` set to empty to force manual secure setup
  - `LOG_LEVEL` set to `info` for production safety
- Rate limiting for API endpoints, webhooks and healthchecks were already present and verified.
- Added unit tests to `tests/Unit/SanitizationTest.php` to assert sanitization behavior for pages and icons.

If you'd like, I can open a PR with these changes and add CI to run the new tests. 🎯