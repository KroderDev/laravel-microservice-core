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

## OIDC Integration (Keycloak-ready)

- Tokens issued by any OpenID Connect provider can be validated via JWKS by setting `OIDC_ENABLED=true` and `OIDC_JWKS_URL` to the JWKS endpoint (for Keycloak: `/realms/{realm}/protocol/openid-connect/certs`). When JWKS is configured, `JWT_PUBLIC_KEY_PATH` becomes optional.
- Map the authenticated user's identifier with `JWT_USER_IDENTIFIER_CLAIM` (defaults to `id`; set to `sub` when mirroring Keycloak) so permission lookups use the desired claim.
- Use `OIDC_CLIENT_ID` to limit permission extraction to a specific client application. Override claim paths with `OIDC_CLIENT_ROLES_CLAIM`, `OIDC_PRIMARY_ROLES_CLAIM`, `JWT_ROLES_CLAIM`, or `JWT_PERMISSIONS_CLAIM` when the token payload is customized.
- Disable redundant gateway lookups when roles and permissions are already embedded in the token by leaving `OIDC_PREFER_GATEWAY_PERMISSIONS=false`; set it to `true` if the gateway remains the authority.
- Always run `composer test` after updating authentication flows—new coverage exists for the JWT middleware and JWKS resolver.
