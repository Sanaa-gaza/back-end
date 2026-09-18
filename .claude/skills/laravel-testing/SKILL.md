---
name: laravel-testing
description: How to write feature tests for Senaa API endpoints. Use when writing, fixing, or reviewing tests.
---

# Laravel Testing — Senaa API

This skill defines how feature tests must be written for Senaa API endpoints. It implements
step 7 of the `laravel-api-endpoint` workflow.

## Location and naming

Every endpoint needs a feature test in `tests/Feature`, named after the endpoint
(e.g. `RegisterCustomerTest`, `LoginTest`, `ResetPasswordTest`).

## Database

- Always use `RefreshDatabase`.
- Tests run against a separate test database — they must never touch development data
  (see `phpunit.xml` config referenced in `CLAUDE.md`).

## Mandatory cases per endpoint

Every endpoint's test must cover:

- **Success case** — response is `200` or `201`, and the JSON structure matches what's expected.
- **Validation failure** — `422`, with the offending field names present in `errors`.
- **Protected route without a token** — `401`.
- **Route restricted to a role, accessed by another role** — `403`.
- **Suspended account** — login/access is denied.

## Registration-specific cases

For every registration endpoint, also test:

- Duplicate email.
- Duplicate phone number.
- Invalid phone format.
- Weak password.

## Data setup

Use factories to create data. Never hand-write rows/arrays for test fixtures.

## Test naming

Name test functions to describe the behavior being verified, e.g.:

```
test_customer_cannot_register_with_duplicate_email
```

## Scope

Test our project's logic only. Do not write tests that verify Laravel's own framework
behavior (e.g. that `RefreshDatabase` resets the database, or that validation rules
themselves work).
