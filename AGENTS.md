# Emerge Mono WordPress development

This repository contains a WordPress plugin, not the private macOS Development app.
Read the plugin header for the current version; folder names and old ZIP names are not authoritative.
Preserve unrelated functionality and design. Keep credentials and customer/site backups out of Git.

## Verify and package
- PHP syntax: `find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l`
- Build installable ZIP: `python3 scripts/package-plugin.py . --output dist`
- ZIP must contain exactly one plugin folder and its main PHP file.
- Core slug collision smoke test: `php tests/slug-collision-smoke.php` when present.
- Core HTML smoke test requires actual HTML paths; running with no arguments is not a passing test.

## Mobile workflow
Create a branch for requested fixes. Run relevant checks and generate a ZIP + SHA-256.
Use the Test ZIP workflow to provide downloadable build artifacts without publishing a release.
Do not create a public release or tag for tests. Alpha/beta versions must stay in a private repository.
Release workflows publish stable versions only; retain this policy.
A PHP syntax check does not prove a working WordPress plugin. Test activation, admin editing, saving and rollback on a test site before declaring end-to-end verification complete.
Do not update production sites as a side effect of preparing a development environment.
