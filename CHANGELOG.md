# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `QueueEventBus` now accepts an explicit `$mode` parameter (`MODE_STRICT` default, `MODE_PERMISSIVE` opt-in) and optional PSR-3 `LoggerInterface`. STRICT preserves legacy throw-on-null-resolver behavior. PERMISSIVE is the safe opt-in for outbox-only projects (VP-2 / Phase 24).
