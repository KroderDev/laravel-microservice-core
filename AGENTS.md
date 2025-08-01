# Repository Guidelines for AI Contributors

## Overview

This repository contains the core package for building microservices using Laravel. Code follows PSR-12 standards and is tested via PHPUnit. All pull requests must maintain test coverage and documentation.

## Local Setup

1. Install dependencies with `composer install`.
2. Run the test suite with `composer test`.
3. Check code style using `composer run print-test` and fix issues with `composer run print`.

## Commit Messages

- Use short, present-tense summaries (max 72 characters).
- Include a blank line followed by a more detailed explanation when needed.
- Reference related issues in the body (`Fixes #123`).

## Pull Request Requirements

1. Ensure the test suite passes (`composer test`).
2. Ensure PSR-12 style (`composer run print-test`).
3. Document notable changes in `CHANGELOG.md` when releasing a new version.
4. Update relevant sections of `README.md` if behavior or public APIs change.
5. Provide a clear PR description summarizing what changed and why.

## Code Style

- PSR-12 is enforced using Laravel Pint.
- Use typed properties and return types when possible.
- Keep methods small and focused.
- Avoid introducing new global helpers or facades.

## Directory Structure

- `src/` contains the package source code.
- `tests/` contains PHPUnit tests.
- `vendor/` should not be committed.

## Additional Notes

- Keep pull requests small and focused to ease review.
- Prefer expressive naming and add comments where logic is complex.
- Any new feature should include corresponding tests.