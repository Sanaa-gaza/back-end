---
name: senaa-sprint1-backend
description: Authoritative specification for the Senaa marketplace Sprint 1 backend (authentication and registration). Use whenever writing, reviewing, or planning Laravel backend code for Senaa — migrations, models, FormRequests, controllers, API routes, or tests related to users, registration, login, email verification, password reset, file uploads, or admin account review.
---

# Senaa — Sprint 1 Backend Specification

Senaa is a marketplace connecting skilled tradespeople with customers in Gaza.
This document is the **single source of truth** for Sprint 1 (Authentication & Registration).

**It supersedes the original SRS and User Stories document.** That document contains
errors and is out of sync with the approved designs. Where the two disagree, this file wins.
Never implement a requirement from the old SRS that contradicts anything written here.

## Stack

- Laravel 12, PHP 8.3
- MySQL (InnoDB), local dev via WAMP
- Laravel Sanctum for API token authentication
- REST API consumed by a separate React.js frontend
- Team: 2 backend developers, 1 frontend, 1 UX/UI designer — all junior

## Team decisions already made (do not re-litigate)

1. **Email verification is required at registration.** Registering creates the account
   with `email_verified_at = NULL` and does **not** issue a token. A 6-digit code is
   sent to the email; the token is only issued after the code is verified. See §6.
2. **No "account under review" gate.** Every verified role logs in and uses the site
   right away. Admin verification runs in the background and only controls a
   **trust badge**.
3. **The OTP code screen (12.png) now serves two purposes**: confirming the email
   right after registration, and the password-reset flow. The two are distinguished
   by a `purpose` value (`register` or `reset`), not by separate screens.
4. **Phone SMS verification is out of scope** for Sprint 1 (no SMS gateway chosen).
5. **The customer onboarding questionnaire is out of scope** for Sprint 1 (not designed).
6. **Phone input accepts either `+970` or `+972`** as the country code (same Palestinian
   number, two different international dialing codes for the same network), but the
   number is always **normalized and stored as `+970XXXXXXXXX`** — see §9.

---

## 1. Scope

| ID | Story | Screens | Status |
|---|---|---|---|
| S1-01 | Role selection | 1 | In |
| S1-02 | Login | 2 | In |
| S1-03 | Customer registration | 3 | In |
| S1-04 | Craftsman registration (2 steps) | 4, 5 | In |
| S1-05 | Institution registration (2 steps) | 6, 7 | In |
| S1-06 | Contractor registration (3 steps) | 8, 9, 10 | In |
| S1-07 | Forgot password | 11, 12, 13 | In |
| S1-08 | Admin account review | — | API only, no UI |

> S1-03 through S1-06 all end on the code screen (12.png, shared with S1-07) for
> mandatory email confirmation before a token is issued.

Out of scope: SMS phone verification, onboarding questionnaire, "under review" screen.

---

## 2. Roles

Five roles exist in `users.role`:

| Role | Registration endpoint | Admin verification | Trust badge |
|---|---|---|---|
| `customer` | `/api/auth/register/customer` | No | No |
| `craftsman` | `/api/auth/register/craftsman` | Yes | Yes |
| `institution` | `/api/auth/register/institution` | Yes | Yes |
| `contractor` | `/api/auth/register/contractor` | Yes | Yes |
| `admin` | Seeded manually | — | — |

A professional account (craftsman, institution, contractor) starts at `status = pending`
in its own role table. **`pending` never blocks login or site access** (once the email is
verified). It only means the verified badge is not shown yet.

---

## 3. Access levels

- **Guest (unauthenticated):** may only reach public read-only endpoints —
  governorates, areas, services, activity types, and public craftsman listings/profiles.
  No mutating action is allowed.
- **Registered but unverified** (`email_verified_at IS NULL`): cannot log in or reach
  any authenticated route. `login` responds `403` and routes the user back to the code
  screen with a freshly issued code (see §6.4) instead of a generic error.
- **Registered and verified:** full access according to `role` (customer / craftsman /
  institution / contractor / admin), subject to `users.status` (`active`/`suspended`)
  and the role table's `status` (`pending`/`approved`/`rejected` — pending never blocks
  access, it only withholds the trust badge).

---

## 4. Registration specifications

Field lists below come from the approved designs. Do not add fields that are not listed,
and do not drop fields that are.

### S1-03 — Customer (screen 3)

| Field | Column | Validation |
|---|---|---|
| Full name | `full_name` | required, 3–100 chars |
| Email | `email` | required, email, unique |
| Phone | `phone` | required, Palestinian format, unique |
| Password | `password` | required, password policy |
| Governorate | `governorate_id` | required, exists |
| Residential address (`areas` table) | `area_id` | required, exists, must belong to `governorate_id` |
| Password confirmation | `password_confirmation` | required, must match `password` |
| Terms agreement | `terms_accepted_at`, `terms_version` | required, must be true |

Result: creates `role = customer` with `email_verified_at = NULL`. **No token returned.**
Response carries the masked email only; a `purpose = register` code is sent. See §6.1.

### S1-04 — Craftsman (screens 4 + 5)

**Step 1 (personal information):** full name, email, phone, password,
**password confirmation**, governorate, residential address (area).

**Step 2 (craftsman details):**

| Field | Column | Validation |
|---|---|---|
| Profile photo | `craftsmen.personal_photo_path` | required, image |
| Photo holding ID | `craftsmen.id_selfie_path` | required, image — **admin-only, never exposed in any public API response** |
| Craft | `craftsmen.craft_id` | required, exists in `services` |
| Bio | `craftsmen.bio` | optional, max 500 |
| Terms agreement | on `users` | required |

Result: creates `role = craftsman`, `craftsmen.status = pending`, `email_verified_at = NULL`.
**No token returned** — same flow as §6.1.

### S1-05 — Institution (screens 6 + 7)

**Step 1 (official information):**

| Field | Column | Validation |
|---|---|---|
| Institution name | `institutions.institution_name` | required |
| Commercial registration number | `institutions.commercial_registration_number` | required, unique |
| Mobile | `users.phone` | required, Palestinian format, unique |
| Official email | `users.email` | required, email, unique |
| Password | `users.password` | required, password policy |
| Password confirmation | `password_confirmation` | required, must match `password` |
| Governorate / Residential address | `users.governorate_id`, `users.area_id` | required |
| Activity type | `institutions.activity_type_id` | required, exists |

**Step 2 (legal documents):** commercial licence upload (required, JPG/PNG/PDF, max 10 MB)
→ `institutions.license_file_path`; description (optional, max 500); terms agreement.

Result: creates `role = institution`, `institutions.status = pending`, `email_verified_at = NULL`.
**No token returned** — same flow as §6.1.

### S1-06 — Contractor (screens 8 + 9 + 10)

**Step 1 (basic data):** four-part full name, **national ID (9 digits, unique)**,
mobile, email, password, **password confirmation**, governorate, residential address (area).

**Step 2 (professional data):** workers count (integer ≥ 1), main specialization,
description (optional, max 500).

**Step 3 (legal documents):** contractor/practice licence upload (required, JPG/PNG/PDF,
max 10 MB) → `contractors.license_file_path`; terms agreement.

Result: creates `role = contractor`, `contractors.status = pending`, `email_verified_at = NULL`.
**No token returned** — same flow as §6.1.

> `national_id` is collected **only for contractors** in the current designs.
> It is therefore `nullable` on `users`.

### Multi-step submission strategy

Do **not** build one endpoint per step. The frontend holds wizard state and submits
**one request at the end**. Files go through the separate `POST /api/uploads` endpoint
first, and the registration request carries the returned paths. No draft-saving logic
in Sprint 1.

---

## 5. Login (S1-02, screen 2)

Fields: email, password, `remember` (boolean).

Rules:

- Check `email_verified_at` **first**, before credentials. See §6.4.
- Return **one generic error message** for wrong credentials. Never reveal which field
  was wrong, and never reveal whether an email exists.
- Increment `failed_attempts` on failure. After **5 consecutive failures**, set
  `locked_until = now + 15 minutes` and reject login until it passes.
- Reset `failed_attempts` to 0 and set `last_login_at` on success.
- `remember = true` issues a longer-lived Sanctum token.
- A user with `users.status = suspended` cannot log in; return the suspension reason.
- The response carries `role`, `status`, and verification state so the frontend can route.

---

## 6. Verification codes — registration & password reset

Both flows share one `otp_codes` table and one code lifecycle, distinguished by a
`purpose` column (`register` or `reset`).

### 6.1 Registration verification (all four roles)

1. The registration endpoint validates input and creates the `users` row (and role row
   where relevant) with `email_verified_at = NULL`.
2. **No token is issued.** The endpoint returns
   `{ success: true, data: { email: "j***@example.com" }, message: "..." }` — the masked
   email only, never a token.
3. A 6-digit code is generated with `purpose = register` and emailed to the user.
4. The frontend shows the code screen (12.png).
5. `POST /api/auth/verify-email { email, code, purpose: "register" }`:
   - Wrong or expired code → `422`. Does not touch the resend budget (§6.3).
   - Correct code → set `email_verified_at = now()`, consume the code, issue the
     Sanctum token, and return it. The user is now logged in.
6. `POST /api/auth/resend-code { email, purpose: "register" }` — subject to §6.3.

### 6.2 Password reset (S1-07, screens 11 → 12 → 13)

1. `POST /api/auth/forgot-password { email }` — always returns the same neutral
   message regardless of whether the email is registered; generates a
   `purpose = reset` code only if it is.
2. `POST /api/auth/verify-email { email, code, purpose: "reset" }` — on success, marks
   the code consumed and returns a short-lived **reset token** (not a login token).
3. `POST /api/auth/reset-password { reset_token, password, password_confirmation }` —
   sets the new password and **revokes all existing tokens/sessions** for that user.
   Reject any attempt without a valid, unexpired reset token.
4. `POST /api/auth/resend-code { email, purpose: "reset" }` — subject to §6.3.

### 6.3 Shared OTP rules (both purposes)

| Rule | Value |
|---|---|
| Code length | 6 digits |
| Code lifetime | **3 minutes** |
| Resend cooldown | 45 seconds |
| Resend limit | 3 per hour, per email + purpose |
| Storage | Hashed, never plaintext |
| Reuse | Single use — invalidated on successful verification **or** when a new code is issued |
| Wrong-code attempts | Do **not** count against the resend limit — only sends count |

### 6.4 Login and unverified accounts

`login` checks `email_verified_at` before anything else:

- `NULL` → do not authenticate against the password. Issue a fresh `purpose = register`
  code and respond `403` with a payload telling the frontend to route to the code
  screen — not the generic invalid-credentials message.
- Set → proceed with the existing credential/lockout checks in §5.

### 6.5 Middleware

Add `verified.email`, applied after `auth:sanctum` on protected routes as defense in
depth. Tokens are only ever issued post-verification, but this guards against a token
surviving a later change to `email_verified_at` (e.g. an admin action).

---

## 7. Admin review (S1-08, API only)

No frontend screens this sprint — build the endpoints anyway so professional accounts
can be approved for testing.

- List accounts with `status = pending`, filterable by account type.
- Show full account data and uploaded documents.
- Approve → set `status = approved`, `verified_at`, `verified_by`; the trust badge appears.
- Reject → **a reason is mandatory**; store it and notify the account owner.
- Suspend / reactivate a user account, with reason.
- Log every decision in `account_reviews` with the admin id and timestamp.
- Protect all admin routes with `role:admin`.

Approval grants a badge. It does **not** unlock access.

---

## 8. Database schema

Create migrations in this order.

### Reference tables

```
governorates    id, name, timestamps
areas           id, governorate_id → governorates, name, timestamps  -- UI label: "Residential Address"
services        id, name, is_active, timestamps      -- craft list
activity_types  id, name, is_active, timestamps      -- institution activity list
```

`areas` keeps its technical table/column name — only its UI label changed (see below).

Relationships: `Governorate` `hasMany` `Area` and `hasMany` `User`; `Area` `belongsTo`
`Governorate`.

Seeders required: the five Gaza Strip governorates (North Gaza, Gaza, Deir al-Balah,
Khan Yunis, Rafah) and their areas; the three MVP crafts (plumber, electrician, AC
technician); institution activity types.

### users

```
users
  id
  full_name
  email              unique
  phone              unique            -- stored as +970XXXXXXXXX
  password
  role               enum(customer, craftsman, institution, contractor, admin)
  national_id        unique, nullable  -- contractors only for now
  governorate_id     → governorates
  area_id            → areas          -- UI label: "Residential Address"
  status             enum(active, suspended) default active
  suspension_reason  nullable
  failed_attempts    default 0
  locked_until       nullable
  last_login_at      nullable
  terms_accepted_at
  terms_version
  email_verified_at  nullable          -- NULL until the registration code is verified
  phone_verified_at  nullable          -- reserved, stays null in Sprint 1
  remember_token, timestamps
```

Indexes: `role`, `email`, `phone`, `national_id`.

### Role tables

```
craftsmen
  id, user_id → users
  craft_id → services
  personal_photo_path                  -- public
  id_selfie_path                       -- admin-only, never in public responses
  bio                  nullable, max 500
  status               enum(pending, approved, rejected) default pending
  rejection_reason     nullable
  verified_at, verified_by  nullable
  avg_rating           default 0
  timestamps

institutions
  id, user_id → users
  institution_name
  commercial_registration_number  unique
  activity_type_id → activity_types
  license_file_path
  description          nullable, max 500
  status, rejection_reason, verified_at, verified_by
  timestamps

contractors
  id, user_id → users
  workers_count        >= 1
  main_specialization
  license_file_path
  description          nullable, max 500
  status, rejection_reason, verified_at, verified_by
  timestamps
```

Shared identity data (name, email, phone, governorate, area, national ID) lives on `users`.
**Never duplicate it in role tables.**

### Supporting tables

```
otp_codes
  id, email, purpose enum(register, reset), code_hash, expires_at, attempts, consumed_at, created_at

account_reviews
  id, account_type, account_id, admin_id → users
  decision enum(approved, rejected, suspended, activated)
  reason, created_at
```

> `otp_codes` replaces the earlier `password_reset_codes` table. The `purpose` column
> is what lets the same table and the same `verify-email` / `resend-code` endpoints
> serve both registration confirmation and password reset.

### Forward-compatibility tables

Create these now even though the current UI allows a single value — changing the schema
later is far more expensive.

```
craftsman_services   craftsman_id, service_id     -- multiple crafts later
craftsman_areas      craftsman_id, area_id        -- multiple service areas later
attachments          id, owner_type, owner_id, file_path, file_type, created_at
```

---

## 9. Conventions — follow these in every file

### Response envelope

Every endpoint returns this shape. Implement it once in an `ApiResponse` trait and use it
everywhere; never hand-build a response array in a controller.

```json
{ "success": true, "data": {}, "message": "..." }
```

```json
{ "success": false, "message": "...", "errors": { "email": ["..."] } }
```

### Phone numbers

- Country code is chosen from a fixed list only: `+970` or `+972` (same Palestinian
  network, same local number — just two different international dialing codes for it).
  No free-text prefix.
- 9 digits, starting with `59` or `56`, regardless of which prefix was chosen.
- **Always normalize and store as `+970XXXXXXXXX`**, regardless of which prefix the
  client submitted — this prevents the same physical number from registering twice
  (once as `+970...`, once as `+972...`) and bypassing the `unique` constraint.
- Server-side validation is mandatory: accept `/^\+(970|972)(59|56)\d{7}$/` on input,
  then normalize to `+970XXXXXXXXX` **before** any `unique` check or storage.
- Implement as a reusable `PhoneRule` (validates) plus a normalization step (e.g. a
  static helper or mutator) applied before persisting — never store the raw input.

### Passwords

**Final rule (decided):** minimum 8 characters, at least one uppercase letter, at least
one digit, at least one special character. Implement as a single reusable `PasswordRule`
used by every registration FormRequest and by the "set new password" step of password
reset.

`password_confirmation` is required in **all four registration forms** (customer,
craftsman, institution, contractor) and on the password-reset "set new password" step.
Use Laravel's built-in `confirmed` rule on the `password` field in every FormRequest that
takes a password.

### File uploads

- Formats: JPG, PNG, PDF
- Maximum size: 10 MB
- Validate the **actual MIME type**, not the file extension
- Store under `storage/app/public`, with `php artisan storage:link`
- Keep limits in `config/upload.php` — never hardcode them in controllers

### Validation placement

All validation goes in **FormRequest** classes, never inline in controllers. Controllers
stay thin: validate (via FormRequest), delegate, return an `ApiResponse`.

### Middleware

| Name | Purpose |
|---|---|
| `role:{roles}` | Restrict a route to specific roles (admin routes) |
| `active.account` | Block suspended accounts from everything |
| `verified.email` | Block accounts with `email_verified_at = NULL` from protected routes (defense in depth, see §6.5) |

Do **not** build an `approved.account` middleware in Sprint 1 — approval is not a gate.
It may be introduced later for service requests and offers if the team decides so.

### Database engine

`config/database.php` must set `'engine' => 'InnoDB'` on the `mysql` connection.
The local WAMP MySQL defaults to MyISAM, which breaks utf8mb4 unique indexes.

---

## 10. Endpoints (24 total)

### Authentication

```
POST   /api/auth/register/customer
POST   /api/auth/register/craftsman
POST   /api/auth/register/institution
POST   /api/auth/register/contractor
POST   /api/auth/verify-email          -- body: { email, code, purpose }
POST   /api/auth/resend-code           -- body: { email, purpose }
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
```

Registration endpoints no longer return a token — see §6.1. `verify-email` and
`resend-code` replace the old purpose-specific `verify-reset-code` /
`resend-reset-code` endpoints; `purpose` picks the branch (`register` or `reset`).

### Password reset

```
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
```

### Reference data

```
GET    /api/governorates
GET    /api/governorates/{id}/areas
GET    /api/services
GET    /api/activity-types
```

### Profile and uploads

```
GET    /api/profile
PUT    /api/profile
POST   /api/uploads
```

### Admin

```
GET    /api/admin/accounts/pending
GET    /api/admin/accounts/{type}/{id}
POST   /api/admin/accounts/{type}/{id}/approve
POST   /api/admin/accounts/{type}/{id}/reject
POST   /api/admin/accounts/{type}/{id}/suspend
POST   /api/admin/accounts/{type}/{id}/activate
```

---

## 11. Standard workflow for a new endpoint

Follow these steps in order, every time:

1. Migration (if new tables or columns are needed)
2. Model with `$fillable`, `$hidden`, casts, and relationships
3. FormRequest with all validation rules and Arabic error messages
4. Controller method — thin, returning `ApiResponse`
5. API Resource to shape the JSON output
6. Route registered in `routes/api.php` with the right middleware
7. Feature test covering the happy path **and** the failure cases
8. Update the Postman collection so the frontend can consume it

Never skip step 7 or step 8. The frontend developer is blocked without step 8.

---

## 12. Git workflow

- One branch per task, branched off `develop`
- Merge into `develop` through Pull Requests only
- Nobody pushes directly to `main`
- `.env` is never committed; keep `.env.example` updated instead

---

## 13. Open decisions

Raise these with the team rather than guessing:

1. What are the values of the institution "activity type" list?
2. Is the contractor's "main specialization" free text or a dropdown?
3. Should national ID be added for customers and craftsmen, as the old SRS required?

Until a decision is made, implement what is written in this file and keep the affected
logic isolated in one place so the change stays cheap.
