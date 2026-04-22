# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.9.0] — 2026-04-22

### Added

- **OBX2-DP**: New optional `bool $directPublishEnabled = true` constructor parameter on
  `QueueEventBus`. Appended as the **6th** parameter (after `$mode` and `$logger`) to preserve
  positional compatibility with all 0.7.x / 0.8.x callers. When set to `false`, both direct
  publish branches (`ShouldQueueDuplicate` and `ShouldQueue`) are skipped. Use this when the
  transactional outbox is the sole external-delivery path and pre-COMMIT publishes should be
  disabled.
  Unit tests added: `tests/unit/Application/EventHandling/QueueEventBusTest.php` — three new
  scenarios (`publishesDirectlyWhenFlagOmitted`, `skipsDirectPublishWhenFlagFalse`,
  `shouldQueueFallsThroughToProjectorsWhenDirectDisabled`).

- `QueueEventBus` now accepts an explicit `$mode` parameter (`MODE_STRICT` default, `MODE_PERMISSIVE` opt-in) and optional PSR-3 `LoggerInterface`. STRICT preserves legacy throw-on-null-resolver behavior. PERMISSIVE is the safe opt-in for outbox-only projects (VP-2 / Phase 24).

### Changed (behaviour note — read before upgrading)

- **OBX2-DP**: When `$directPublishEnabled === false`, both `ShouldQueueDuplicate` and
  `ShouldQueue` events fall through to `$nonQueuedEvents[]` and are published via
  `$simpleEventBus`. Previously (for `ShouldQueue` specifically) the inner `continue` statement
  suppressed that fall-through. **Projectors now see `ShouldQueue` events in this mode.** If a
  consumer relies on `ShouldQueue` NOT reaching projectors, the consumer must gate on the event
  type itself — do NOT set `$directPublishEnabled` to `false`.

### Backwards Compatibility

- All existing constructor shapes compile and behave identically. Default
  `$directPublishEnabled = true` restores 0.7.x / 0.8.x semantics for both direct-publish
  branches; the `continue` on `ShouldQueue` remains in effect.

### Fixed

- **Test hygiene**: Marked `EventFactoryDataProvider::getMakeEventReturnsAllowedEventData`,
  `getMakeEventThrowsExceptionIfNotAllowedEventData`, and `EventQueueDataProvider::getData` as
  `static`. PHPUnit 11 hard-errors non-static `#[DataProvider]` methods (previously deprecated);
  these four test cases were silently failing to load with `Data Provider method … is not static`.
  No behaviour change — the data bodies were already side-effect-free.
- **Test hygiene**: Fixed typo in `QueueEventBusTest::queueableEventPublishedToEventBusTest`
  — assertion against `$traces[0]['body']['serialize']['data']` corrected to
  `$traces[0]['body']['serialized']['data']`. The producer uses
  `QueueEventProducer::FIELD_SERIALIZED = 'serialized'`; the test was checking a key that never
  existed. Bug was dormant because the non-static data provider prevented the test from reaching
  its assertions.
