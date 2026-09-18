---
name: senaa-business-rules
description: Business and authorization rules for the Senaa platform — who can see what, which fields are locked, and how account verification status behaves. Use whenever implementing permissions, profile updates, visibility filters, or account status logic.
---

# Senaa — Business Rules

Cross-cutting business and authorization rules for the Senaa platform. These apply on top of
whatever a specific sprint spec (e.g. `senaa-sprint1-backend`) says about a given endpoint.

## Account status

- `users.status` is `active` or `suspended` only. A suspended user cannot log in at all.
- Role tables (`craftsmen`, `institutions`, `contractors`) have their own `status`:
  `pending`, `approved`, `rejected`.
- `pending` does **not** block login or usage — it is a trust badge only, never a gate.
- The "verified" badge appears when `verified_at` is not null.
- Customers are never subject to admin review — no status/verification flow applies to them.

## Locked fields — enforced server-side, not in the UI

The following fields must never be user-editable, at any layer:

- `national_id`
- `licence_number`
- `commercial_registration_number`
- `craft_id` — locked for Sprint 1

**How the lock is implemented:**

- The field is absent from the FormRequest entirely.
- The field is removed from the model's `fillable`.
- The controller only ever writes `validated()` data — never the raw request.

Only an admin may change these fields, through a dedicated admin endpoint, and every such
change must be logged in `account_reviews`.

## Editable fields

Name, bio, profile photo, city, and area are freely editable by the user via `PUT /api/profile`.

## Visibility rules

- Job opportunities posted by institutions are visible to: craftsman, contractor,
  institution, and admin.
- Customers must never see job opportunities — `403`.
- An institution sees **all** job opportunities, not only the ones it posted itself.
- Enforce this at the route level via middleware, not as an `if` condition inside the controller.

## Scheduled for later sprints — do not implement yet

- A craftsman adding a new craft/service is saved as `pending` in `craftsman_services` and stays
  hidden from customers until an admin approves it. (Sprint 6)
- Publishing job opportunities. (Sprint 7)

Do not build ahead of these sprints even if it seems convenient — implement only what the
current sprint's spec calls for.
