# Change Log

## 1.3.0 - 2023-04-013

### Added

- Added publicPaths parameter to BearerAuthenticationMiddleware.

## 1.2.1 - 2023-04-04

### Fixed

- Fixed headers added in previous middlewares not getting sent with challenege response.

## 1.2.0 - 2023-03-07

### Added

- Added ability to register identifiers.
- Added INSTALL, MAPPER\_ADAPTOR, SOURCE\_MAP, and SNYPPET identifiers.
- Added snyppet and install middlewares.

### Changed

- Removed unused PAGE identifier.
- Added default values to some existing middleware constructors.

## 1.1.0 - 2023-01-15

### Added

- HSTS response middleware.
- Slash redirect middleware.
- UID routing path to router middlewares.
- PHPStan static analysis.

## 1.0.0 - 2022-11-29

Initial release.
