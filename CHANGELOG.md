# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-11-26
### Fixed
- **Critical:** Fixed `ArgumentCountError` in `InstallCommand::createControllers()` method call. The method now correctly receives all 3 required parameters ($model, $guard, $stack) instead of just 2. This bug was preventing the install command from completing successfully.

## [1.0.0] - 2025-11-26
### Added
- Initial release of the Multi-Auth package.
- Basic structure and service provider.
- Install command scaffold with multi-guard support.
- Laravel Breeze integration with automatic detection and installation.
- Support for Blade stack (traditional server-side rendering).
- Support for React stack (Inertia.js).
- Support for Vue stack (Inertia.js).
- Automatic stack detection based on project dependencies.
- Model, migration, controller, route, and view scaffolding.
- Separate stub files for each stack (Blade, React, Vue).
- Interactive prompts for Breeze installation if not present.
- Version constant in ServiceProvider for programmatic access.
