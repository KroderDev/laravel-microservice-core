# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
