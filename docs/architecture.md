# Architecture

Santa Shuffle is a Laravel application with an Inertia/React interface and a database-backed queue. The organization follows the action/request/controller separation used by Summit Collector's Laravel API, adapted to event ownership, private participant links and a small self-hosted application.

## Backend responsibilities

| Layer | Responsibility | Examples |
| --- | --- | --- |
| HTTP controllers | Translate requests into action calls, explicit Inertia props and redirects | `ExchangeController`, `ParticipantController` |
| Form requests | Validate untrusted input and apply request-level authorization | `ExchangeRequest`, `ParticipantRequest`, `VerifyLoginCodeRequest` |
| Actions | Execute one named use case; preserve transaction boundaries and lifecycle checks | `DrawExchange`, `ReopenExchange`, `RevealRecipient`, `SendLoginCode` |
| Services | Shared behavior with a real reuse boundary | `SessionAccess`, `DeliveryQuota`, `DrawNames` |
| View models | Explicit public projections; never serialize whole private models | `ExchangeView` |
| Eloquent models | Persistence, relations, casts and event transaction primitive | `Exchange`, `Participant`, `Delivery` |
| Queue jobs | Reload delivery state, suppress obsolete messages and send in the recorded language | `SendAccessLink` |

Actions expose a typed `execute()` method and receive concrete dependencies through Laravel's container where needed. There is no generic repository wrapper around Eloquent, global event bus or interface for every class. Responsibilities are separated where authorization, transaction consistency, projection privacy or testing requires a boundary. New abstractions should solve an observed problem.

The critical boundary is the event transaction, not the number of classes. State-changing actions reauthorize against the refreshed event **inside the lock**. Draw, reveal, reopening and registration must share that lock. Delivery and database queue inserts commit with the associated mutation. Preserve these invariants when adding actions, alternative entry points or persistence changes.

`SessionAccess` owns organizer identity/version checks and participant-session resolution. `ExchangeView` returns allow-listed data; organizer projections exclude addresses, wishes and recipient assignments. Form validation cannot replace these authorization checks. `strict_types`, typed relationships and Larastan/PHPStan checks help keep the boundaries explicit.

## Frontend responsibilities

Pages assemble features and receive server-owned props. The management page composes `ParticipantsPanel`, `EventSettings`, `ExclusionsPanel` and `DrawPanel` from `resources/js/features/events/`. `ParticipantRow` owns row rendering, `useEventAction` coordinates management mutations, and the pure `drawAvailability` function expresses UI draw availability without replacing server enforcement.

Shared event types live in `resources/js/types/exchange.ts`. Generic fields, currency selection and event-detail formatting are separate components. Clipboard behavior and scoped animation lifecycles live in focused hooks. GSAP animation contexts clean up on unmount, and reveal celebration respects reduced motion. Inertia remains the form/navigation integration; no additional global state framework is needed.

React escapes user-provided values by default. Do not introduce raw HTML rendering for wishes, participant names, event messages or translated interpolation. Server validation errors and busy states remain visible and accessible.

## Localization

Supported locales are `en`, `fr`, `es` and `de`. The language switcher posts to `/locale`. Selection uses the session preference, then a supported `Accept-Language` choice, then the configured app locale when no header is present. Unsupported language headers fall back to English. New installation examples default to English; the additive migration defaults existing records to French to preserve their previous language.

Frontend catalogs live under `resources/js/i18n/`. English message keys are typed, interpolation is plain text, and `Intl` formats currencies and dates for the selected language. Catalog tests check matching keys and placeholders across all four languages. Backend catalogs translate validation, notices and mail. Add a phrase to every catalog in the same change.

Events and participants store their chosen locale, and deliveries snapshot the recipient locale. A queued notification does not depend on the worker's ambient language. The worker restores its previous locale after sending, including on failure. Login-code mail uses the requesting browser's chosen language. User-authored content is preserved as entered and is not machine-translated.

## Tests and change workflow

Add a failing behavioral test before implementing a new rule or fixing a regression. Refactor behind the existing HTTP and concurrency contract, then keep the focused test as a guard. Do not claim retroactively that old code was written test-first.

- PHPUnit exercises HTTP authorization, lifecycle rules, validation, language propagation and privacy projections against both database engines.
- Runtime scripts exercise actual process collisions, SMTP failure/retry and Docker backup/restore behavior.
- Vitest covers pure frontend rules, translation parity/interpolation/formatting and component accessibility/escaping.
- Playwright exercises browser sessions, CSRF, complete event journeys and all four language selections.
- PHPStan/Larastan, strict TypeScript, Pint and Prettier cover static correctness and consistent formatting.

Run the commands in [CONTRIBUTING.md](../CONTRIBUTING.md). Evidence belongs in [validation.md](validation.md), with local checks, remote CI, registry publication and production verification reported separately.
