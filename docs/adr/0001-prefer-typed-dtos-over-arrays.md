# 1. Prefer typed DTOs over arrays where a fixed shape exists

## Status

Accepted (overrides the architecture brief's default).

## Context

The architecture brief scopes DTOs narrowly: "only where they help: a queue
payload, an integration boundary, or a call with more than about four
parameters." Under that reading, `CreateProgram::handle(array $data)` — a
3-field array — was left untyped.

The user asked to use DTOs over arrays wherever possible, overriding that
default.

## Decision

Prefer a small `final readonly`-style class over an untyped/shape-annotated
array for any internal call where the shape is fixed and known ahead of time
(Action inputs, cross-layer data handoffs), not just at integration
boundaries or 4+ parameter calls.

This does **not** apply to:

- Framework-mandated signatures (`Illuminate\Contracts\Database\Eloquent\CastsAttributes::set()`
  must return `array`; `FormRequest::rules()` must return `array`).
- Raw external input before it's been parsed into a known shape (e.g. an
  inbound postback body — the whole point of the adapter is to turn that
  array into a `PostbackData` DTO; wrapping the pre-parse array in a class
  adds a file with no type safety gained).
- Deliberately generic/polymorphic payloads where the shape *is* the point
  (e.g. `OutboxMessage::$payload`, which varies per event type by design).

## Consequences

New Action inputs get a co-located `<Verb><Noun>Data` class
(e.g. `App\Actions\Commerce\CreateProgramData`) instead of a
`@param array{...}` shape annotation. This is a few more small files in
exchange for real static-analysis coverage on construction (a typo'd array
key fails at compile-time-adjacent PHPStan checking instead of silently
returning null at runtime).
