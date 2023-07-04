# Change Log

## 1.3.5 - 2023-07-04

### Changed

- Identifier __callStatic will no longer throw an error if the identifier is not registered.

## 1.3.4 - 2023-06-14

### Added

- Added support for access middlewares to specify a guest user id.

## 1.3.3 - 2023-05-23

### Changed

- Normalized 'Chars' to 'Characters' to match the rest of pyncer.

## 1.3.2 - 2023-04-29

### Fixed

- Fixed issue with CleanRequestMiddleware.
- Addressed some issues brought up by PHPStan.

## 1.3.1 - 2023-04-24

### Added

- Added error response option to debug middleware.

## 1.3.0 - 2023-04-14

### Added

- Added publicPaths parameter to BearerAuthenticationMiddleware.
- Exposed MiddlewareManager onError, onBefore, onAfter to App

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
