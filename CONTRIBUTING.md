# Contributing to CreceWeb Lumen Lite

Thank you for your interest in improving CreceWeb Lumen Lite.

This repository tracks the public stable source of the WordPress.org plugin. Contributions should remain compatible with the Lumen ecosystem architecture and WordPress.org requirements.

## Before opening a pull request

Please:

1. Search existing issues and pull requests.
2. Test against the latest stable WordPress release.
3. Test with the latest stable CreceWeb Lumen Theme release.
4. Keep the change focused on one problem.
5. Avoid unrelated refactors.
6. Follow WordPress coding standards.
7. Preserve accessibility and responsive behavior.
8. Avoid unnecessary frontend assets and dependencies.
9. Preserve the separation between Lite and Pro responsibilities.
10. Do not include private project files, credentials, build archives, translation working files or internal QA documentation.

## Development principles

Lumen Lite favors:

- practical features with clear user value;
- lightweight frontend behavior;
- native WordPress APIs;
- Gutenberg-friendly workflows;
- progressive enhancement;
- accessibility;
- privacy-conscious defaults;
- backward-safe settings;
- clear separation between Theme, Lite and Pro responsibilities.

Lite should remain independently useful and should not become an artificial gate for Pro functionality.

## Pull requests

A pull request should include:

- a concise summary;
- the problem being solved;
- files changed;
- reproduction or testing steps;
- screenshots for visible UI changes;
- accessibility considerations when relevant;
- performance considerations when frontend assets change.

A contribution may be declined when it:

- conflicts with the project architecture;
- duplicates functionality assigned to Lumen Pro;
- introduces unnecessary complexity or third-party dependencies;
- adds avoidable frontend cost;
- conflicts with WordPress.org policies;
- does not fit the public roadmap.

## Bug reports

Use the repository bug-report form for reproducible problems.

Security vulnerabilities must be reported privately according to [SECURITY.md](SECURITY.md).

## License

By contributing code to this repository, you agree that your contribution may be distributed under the same GPL-compatible terms as CreceWeb Lumen Lite.
