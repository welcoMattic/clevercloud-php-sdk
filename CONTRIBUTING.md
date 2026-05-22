# Contributing to the Clever Cloud PHP SDK

Thanks for taking the time to contribute! This SDK is community-maintained
— issues, pull requests, and feedback are all welcome.

## Quick links

- 🐞 [Report a bug](https://github.com/welcoMattic/clevercloud-php-sdk/issues/new?template=bug_report.yml)
- 💡 [Suggest a feature](https://github.com/welcoMattic/clevercloud-php-sdk/issues/new?template=feature_request.yml)
- 📚 [Documentation](https://welcomattic.github.io/clevercloud-php-sdk/)

## Reporting bugs

Before opening a bug report, please:

1. Make sure you are running the latest stable release.
2. Search [existing issues](https://github.com/welcoMattic/clevercloud-php-sdk/issues?q=is%3Aissue)
   — your problem may already be reported.
3. Try to reproduce the bug with a minimal example.

Then open an issue using the **Bug Report** template and include:

- SDK version and PHP version
- The smallest possible code sample that reproduces the issue
- The full exception class, message, and stack trace (redact tokens)
- What you expected vs. what happened

> Please redact API tokens, OAuth secrets, and any personal data before
> pasting logs or payloads.

## Suggesting features

Open an issue using the **Feature Request** template. Describe:

- The use case — what are you trying to do?
- Why the current API can't do it (or makes it awkward)
- A proposed shape for the new API, if you have one in mind

Discussion before implementation saves everyone time, especially for
changes that touch the public surface.

## Submitting a pull request

1. **Fork** the repository and create a topic branch from `main`:
   ```bash
   git checkout -b fix/short-description
   ```
2. **Code** your change. Keep PRs focused — one logical change per PR.
3. **Test** locally — see [Running checks](#running-checks-locally) below.
4. **Document** any public API change (PHPDoc + relevant `docs/` page).
5. **Push** and open a PR against `main`. Fill in the PR template.

### Branch naming

Loose convention, not enforced — anything readable works. Common prefixes:

- `feat/...` — new feature or API addition
- `fix/...` — bug fix
- `docs/...` — documentation-only change
- `chore/...` — tooling, CI, dependencies, formatting

### Commit messages

Follow a [Conventional Commits](https://www.conventionalcommits.org/)
style — it keeps the changelog readable and machine-parseable.

```
type(scope): short summary in present tense

Optional longer description explaining the why.
```

Examples:

- `feat(applications): add restart() helper`
- `fix(http): retain Authorization header on 307 redirect`
- `docs(authentication): clarify api-bridge host rewrite`

## Coding standards

- **PHP 8.5** minimum. Lean on modern features — property hooks,
  asymmetric visibility, readonly classes, enums.
- **Strict types** — every file starts with `declare(strict_types=1);`.
- **PSR-12** code style, enforced by PHP-CS-Fixer.
- **PHPStan level max** with strict-rules — no baseline.
- **One class per file**, namespaces mirror directory layout (PSR-4).

## Running checks locally

The project ships a `composer check` script that runs the full gate:

```bash
composer check
```

That's a shortcut for the three individual scripts:

```bash
composer cs        # php-cs-fixer --dry-run (style check)
composer cs-fix    # php-cs-fixer (apply fixes)
composer phpstan   # PHPStan static analysis
composer test      # PHPUnit
```

CI runs the same commands. If `composer check` is green locally, your
PR should pass CI.

## Testing

- **Unit tests** under `tests/Unit/` — fast, isolated, no network. Use
  the Symfony `MockHttpClient` to feed scripted responses.
- **Integration tests** under `tests/Integration/` — guarded by env
  vars (`CC_API_TOKEN` or `CC_OAUTH_*`). Skipped by default.

New public methods should land with unit tests. Bug fixes should land
with a regression test that fails before the fix.

## Documentation

The reference docs live under `docs/` and are published to
GitHub Pages via MkDocs Material. If your PR changes a public API:

- Update the relevant page under `docs/resources/` or `docs/`.
- Verify any code sample compiles against the new signature.
- Build locally to catch broken links:
  ```bash
  python -m venv .venv-docs && .venv-docs/bin/pip install -r requirements-docs.txt
  .venv-docs/bin/mkdocs build --strict
  ```

## Contact

For anything that doesn't fit a public issue — private feedback,
maintainer questions, etc. — reach out to:

**msantostefano@proton.me**

For **security vulnerabilities**, please use the
[GitHub Security Advisory form](https://github.com/welcoMattic/clevercloud-php-sdk/security/advisories/new)
rather than email or a public issue, so the report stays confidential
until a fix is available.

## License

By submitting a contribution, you agree that your contribution is
licensed under the project's [MIT license](LICENSE).
