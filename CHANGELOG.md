# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.6.0](https://github.com/KroderDev/laravel-microservice-core/compare/v0.5.1...v0.6.0) (2026-07-22)


### Features

* **resilience:** add circuit breaker, exponential backoff, and retry strategy ([0474b73](https://github.com/KroderDev/laravel-microservice-core/commit/0474b73ed6bd980420edbcef0fb7ff9a3077a10d))


### Bug Fixes

* **labeler:** update config format for actions/labeler@v6 ([7e79652](https://github.com/KroderDev/laravel-microservice-core/commit/7e79652270f0fb62d8b2c9969446ea7c7fae9c81))
* **labeler:** use correct v6 syntax without changed-files wrapper ([dbf3986](https://github.com/KroderDev/laravel-microservice-core/commit/dbf39865cc502049a34f4cc7bf8c8d9dab1c8f92))


### Documentation

* update README and add ROADMAP ([e9d09b1](https://github.com/KroderDev/laravel-microservice-core/commit/e9d09b1c6c6a30d02dc36c77b4fb7ce5d0e348db))


### Code Refactoring

* clean up service provider for distributed architecture ([ae5416a](https://github.com/KroderDev/laravel-microservice-core/commit/ae5416a415f9aef83e5267f0aefae4f746c23845))
* distributed architecture toolkit scope reduction ([6a38d28](https://github.com/KroderDev/laravel-microservice-core/commit/6a38d28c37e65fe039d0f9301191867537ae98e9))
* multi-service HTTP client ([1e0f249](https://github.com/KroderDev/laravel-microservice-core/commit/1e0f24963f90c8d1d347ccb93d27e24fefae1d28))
* restructure config for distributed services ([4ad8bdf](https://github.com/KroderDev/laravel-microservice-core/commit/4ad8bdfe14d9e7c66b95e4b37fbbf82317c14a6f))


### Tests

* remove and update tests for new architecture ([6bfe8f9](https://github.com/KroderDev/laravel-microservice-core/commit/6bfe8f971d3431ef6513e458e36e905a87c02820))


### Continuous Integration

* add GitHub Actions workflow for labeling PRs ([fac7cef](https://github.com/KroderDev/laravel-microservice-core/commit/fac7ceff37a5525020873c756ae27fd86f4bbb5e))
* add GitHub Actions workflow to summarize new issues ([93616c1](https://github.com/KroderDev/laravel-microservice-core/commit/93616c1ce715fe82618b431d3556b80c4f1c4bb7))
* add labeler configuration for PR auto-labeling ([3634553](https://github.com/KroderDev/laravel-microservice-core/commit/3634553a0edf07281ffc4d4ecd4f943ee976c955))
* add publish workflow and improve release-please with validation ([9def293](https://github.com/KroderDev/laravel-microservice-core/commit/9def2932547914db4cc910ba283f999521368760))
* configure release-please with manifest and expanded changelog sections ([8192679](https://github.com/KroderDev/laravel-microservice-core/commit/81926798095a60e563f8663181970733614dab98))
* **deps:** bump actions/ai-inference from 1 to 2 ([2c743b7](https://github.com/KroderDev/laravel-microservice-core/commit/2c743b76fd885d96793bc6619cf97720821d6d28))
* **deps:** bump actions/ai-inference from 1 to 2 ([50c16b4](https://github.com/KroderDev/laravel-microservice-core/commit/50c16b426cc787e45d8d07ac7b6cd3afc5187497))
* **deps:** bump actions/cache from 4 to 6 ([ece9869](https://github.com/KroderDev/laravel-microservice-core/commit/ece98693572d652deafe20457bba48f422cc8f29))
* **deps:** bump actions/cache from 4 to 6 ([9e1bd3c](https://github.com/KroderDev/laravel-microservice-core/commit/9e1bd3c09e967bf9cdbce0482e0a26f56a7751e7))
* **deps:** bump actions/checkout from 4 to 7 ([62ab886](https://github.com/KroderDev/laravel-microservice-core/commit/62ab88646bc82aafc3134d07ca964598a018cd7c))
* **deps:** bump actions/checkout from 4 to 7 ([3007d65](https://github.com/KroderDev/laravel-microservice-core/commit/3007d659b7f42ec6b9ed66f6be0b4b88641690c6))
* **deps:** bump actions/labeler from 4 to 6 ([e612726](https://github.com/KroderDev/laravel-microservice-core/commit/e61272602e19660c18032e892fc66ec95e4079cb))
* **deps:** bump actions/labeler from 4 to 6 ([bfd3148](https://github.com/KroderDev/laravel-microservice-core/commit/bfd31481b8304b6d50cf1bdbe76672dc48a0543b))
* **deps:** bump googleapis/release-please-action from 4 to 5 ([cda67b7](https://github.com/KroderDev/laravel-microservice-core/commit/cda67b7130ad47c8bf152e447079f19a3a93a1e4))
* **deps:** bump googleapis/release-please-action from 4 to 5 ([3209fb4](https://github.com/KroderDev/laravel-microservice-core/commit/3209fb434b00fa7cde091db2f83f7b5bbb1fbd3b))
* **deps:** bump softprops/action-gh-release from 2 to 3 ([45461b0](https://github.com/KroderDev/laravel-microservice-core/commit/45461b00d863669cc899bc0ada22600c69e5c63d))
* **deps:** bump softprops/action-gh-release from 2 to 3 ([2d7f39c](https://github.com/KroderDev/laravel-microservice-core/commit/2d7f39cb0d25bf5063f138bcf446d83ae46bff4d))


### Miscellaneous Chores

* update package description to reflect current features ([c14e3e7](https://github.com/KroderDev/laravel-microservice-core/commit/c14e3e794a473381de7b7093a4d11ba1048d6b70))


### Dependencies

* **deps:** bump guzzlehttp/guzzle from 7.13.1 to 7.15.1 ([a701f63](https://github.com/KroderDev/laravel-microservice-core/commit/a701f632b565204f215c96dc9d45d12e145af596))

## [0.5.1](https://github.com/KroderDev/laravel-microservice-core/compare/v0.5.0...v0.5.1) (2026-06-30)


### Bug Fixes

* drop PHP 8.2 from matrix, update lock for 8.3 compat, fix test ([d711dfd](https://github.com/KroderDev/laravel-microservice-core/commit/d711dfda233541170f3f481229be567684edde3b))
* handle confirm prompt in make:model test for Laravel 12 compatibility ([d1111a1](https://github.com/KroderDev/laravel-microservice-core/commit/d1111a1aa176a15578b32bb574e2dfeb18cfd8ff))


### Miscellaneous Chores

* add Makefile and docker-compose.yml for local dev environment ([862bfea](https://github.com/KroderDev/laravel-microservice-core/commit/862bfea334585dea99b33717b164701ee15a0e73))
* **deps:** update composer dependencies ([b8a5721](https://github.com/KroderDev/laravel-microservice-core/commit/b8a572106b72b251089c7f610a711f2642b6e779))
* **deps:** update PHP dependencies ([744fbd8](https://github.com/KroderDev/laravel-microservice-core/commit/744fbd8598bc32ab900294df4044500aa99c6874))

## [0.5.0] - 2025-09-22

### Added
- JWKS support and configurable claim mapping for OpenID Connect-issued JWTs (Keycloak-ready).
- New configuration toggles to align `ExternalUser` identifiers and reuse token roles/permissions without gateway calls.
- PHPUnit coverage for the JWT middleware and JWKS validator.

### Changed
- Added type hints to gateway utilities for stronger typing.
- Sanitized JWT key logging in `GatewayGuard` to avoid exposing sensitive data.
- Default health uri from `/api/heath` -> `/health`

### Fixed
- Ensure API responses are checked before marking models as successful.
- Correlation ID middleware to generate request identifiers with the config lenght.
- Prevent external redirects by validating `redirect` parameters against an allow list.

## [0.4.3] - 2025-08-13
### Fixed
- `paginate()` returns an empty paginator with a 200 status when the gateway responds with 404.
- `paginate()` fixed the division by zero error when perPage is empty.

## [0.4.2] - 2025-07-21
### Added
- Query builder with `where()->get()` support for remote models.
- Smart error handling based on the request's expected format.
- Error propagation improvements with tests for all status codes.
- Introduced `ParsesApiResponse` trait to centralize API response parsing logic.
- Configurable HTTP methods for model updates and deletions.
- `updateById`, instance `update`, `updateOrFail`, and `findOrFail` for remote models.

### Changed
- Refactored `QueryBuilder` and `ApiModel` to use the new `ParsesApiResponse` trait, removing duplicate `parseResponse` implementations.
- Improved README with installation details, key features, and basic-usage example.

### Fixed
- Remote models hydrated from API responses are marked as existing to prevent local database queries.

## [0.4.1] - 2025-07-10
### Added
- Base `Controller` class with `apiResponse` method.
- Helper methods for common JSON responses.
- `$apiRelations` mapping in `ApiModelTrait` for nested relations.
- Custom `make:model` command supporting a `--remote` option.

### Changed
- `ApiGatewayClient` and `PermissionsClient` bindings are now scoped.
- `GatewayGuard` registers a `CookieJar` to avoid logout issues.

## [0.4.0] - 2025-07-01
### Added
- Gateway authentication controllers with comprehensive tests.
- `RedirectsIfRequested` trait and redirect support in auth controllers.
- Configurable `gateway_auth.default_redirect` setting.
- `token()` method in `GatewayGuard`.
- Logging for JWT key loading and decoding.
- PHPDoc comments across `GatewayGuard` methods.
- Laravel Pint integration and PSR-12 code style.

### Changed
- Removed `__get` magic method from `ExternalUser`.
- Enhanced redirect logic and session handling in `GatewayGuard`.
- Documentation reorganized with a new features list.
- Commented out session migration due to CSRF conflicts.

[0.5.0]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.4.3...v0.5.0
[0.4.3]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.4.2...v0.4.3
[0.4.2]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.3.1...v0.4.0
