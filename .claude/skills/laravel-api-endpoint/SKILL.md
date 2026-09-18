---
name: laravel-api-endpoint
description: Standard steps for adding or modifying an API endpoint in the Senaa project. Use whenever creating or editing a controller, FormRequest, route, API Resource, or an endpoint feature test.
---

# Laravel API Endpoint — Standard Workflow

This skill defines the mandatory process for adding or modifying any API endpoint in the
Senaa backend. It covers **workflow and layering rules only**. For detailed technical rules
(response shape, `PhoneRule`, `PasswordRule`, upload limits, database schema), see the
`senaa-sprint1-backend` skill — do not duplicate those rules here, to avoid future conflicts.

## Mandatory order for any new endpoint

Follow these steps in order. Do not skip ahead or reorder them.

1. **Migration** — if the endpoint requires new tables or columns.
2. **Model** — with `fillable`, `hidden`, `casts`, and relationships defined.
3. **FormRequest** — contains *all* validation rules and Arabic error messages for this endpoint.
4. **Controller** — thin: receives the already-validated request, delegates the work, and
   returns an `ApiResponse`. No business logic beyond orchestration belongs here.
5. **API Resource** — shapes the JSON output.
6. **Route registration** — register the route in `routes/api.php` with the correct middleware.
7. **Feature test** — covers both the success case and the failure cases.
8. **Postman collection update** — reflect the new/changed endpoint.

**Steps 7 and 8 must never be skipped.** Skipping step 8 breaks the frontend team, since they
build against the Postman collection, not against the code.

## Additional rules

- Never write validation logic inside a controller — all validation belongs in the FormRequest.
- Never build a response array by hand — always return responses through `ApiResponse`.
- Sensitive fields (e.g. the ID/identity document image) must never appear in any general-purpose
  API Resource.

## Reference

For response shape, `PhoneRule`, `PasswordRule`, file upload limits, and the database schema,
see the `senaa-sprint1-backend` skill. This skill does not restate those details.
