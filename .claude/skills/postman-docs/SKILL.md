---
name: postman-docs
description: How to document a new or changed API endpoint in the Senaa Postman collection. Use immediately after creating or modifying any endpoint, route, or request/response shape.
---

# Postman Documentation — Senaa API

This skill defines how every API endpoint must be documented in the shared Postman collection.
It complements `laravel-api-endpoint` (step 8 of that workflow points here) and
`senaa-sprint1-backend` (source of truth for actual request/response shapes).

## Collection location

`docs/postman/senaa-api.postman_collection.json`

If it doesn't exist yet, create it with two collection-level variables:

- `base_url` — e.g. `http://localhost:8000/api`
- `token` — empty by default, populated automatically after login (see below)

## Folder structure

Mirror the API's own sections as top-level folders in the collection:

- `Auth`
- `Password Reset`
- `Reference Data`
- `Profile`
- `Admin`

Add a new folder only if a genuinely new API section is introduced — don't fragment these five.

## What every request must include

- **Name**: verb + resource, e.g. `Register Customer`, `Reset Password`, `List Cities`.
- **Full URL** built with `{{base_url}}`, never a hardcoded host.
- **Headers**: all required headers, plus `Authorization: Bearer {{token}}` on every protected route.
- **Example body**: realistic Palestinian values — phone numbers like `+970591234567`, city `Gaza`, etc. Not `foo`/`test`/`asdf`.
- **Example success response**: a real, complete JSON sample of what the endpoint returns.
- **At least one example failure response**: `422` (validation) or `401` (auth), whichever applies.

## Auto-storing the auth token

On the login request (and register, since it also returns a token), add a **Tests** tab script
that extracts the token from the response and saves it into the `token` collection variable, so
every subsequent protected request works without manual copy-pasting.

## Mandatory rule

**An endpoint is not considered done until it is added to this Postman collection.**
The frontend team builds against this collection, not against the code — skipping this step
breaks their work.

## Keeping it in sync

If you change the fields of an existing endpoint (request or response), update its example body
and example response in the **same commit** as the code change. A stale example is worse than no
example, because it silently misleads the frontend team.
