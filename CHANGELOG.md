# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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

[0.4.1]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/KroderDev/laravel-microservice-core/compare/v0.3.1...v0.4.0