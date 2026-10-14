# Student Choice — API Reference

Every endpoint below is real — each example response was captured live from the running app against the actual imported catalog (then cleaned up again for the write examples, so nothing fake was left in the database). Large arrays are trimmed with a `// … N more` note for readability; the real response isn't truncated.

**Base URL:** `http://127.0.0.1:8000/api` (local dev via `php artisan serve`)
**Auth:** Sanctum bearer tokens. After `/login` or `/register`, send `Authorization: Bearer <token>` on every subsequent request.
**Always send** `Accept: application/json` — without it, Laravel's error responses render as HTML instead of JSON (this trips people up more than anything else).

## Error responses

These shapes apply across every endpoint below, so they're documented once instead of repeated.

**422 — validation failed** (missing/invalid fields)
```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**401 — no/invalid/expired token**
```json
{ "message": "Unauthenticated." }
```

**403 — authenticated, but not allowed** (e.g. a student hitting an `/admin/*` route, or a banned account)
```json
{ "message": "This action is restricted to administrators." }
```

**404 — record doesn't exist**
```json
{ "message": "No query results for model [App\\Models\\Major] 99999" }
```
(Only this clean shape when `APP_DEBUG=false`. In local dev with debug on, Laravel returns a full stack trace instead — don't ship `APP_DEBUG=true` to production.)

---

## Auth

### `POST /register`
Public. Creates a student account + an empty profile row, returns a token.

Request body:
```json
{ "name": "Docs Example", "email": "docs-example@student-choice.test", "password": "password123", "password_confirmation": "password123" }
```

Response `201`:
```json
{
  "user": {
    "id": 3,
    "name": "Docs Example",
    "email": "docs-example@student-choice.test",
    "role": "student",
    "is_banned": false,
    "banned_reason": null,
    "created_at": "2026-10-13T14:38:31.000000Z",
    "profile": {
      "gpa": null, "tawjihi_branch": null, "preferred_region": null, "preferred_city": null,
      "budget_max": null, "photo_url": null, "description": null, "preferred_fields": null,
      "scholarships": null
    }
  },
  "token": "3|i9mX...redacted"
}
```

### `POST /login`
Public. Returns `403` instead of a token if the account is banned (see Error responses).

Request body: `{ "email": "...", "password": "..." }` → same response shape as `/register`.

### `POST /logout`
Auth required. Revokes the current token.
Response `200`: `{ "message": "Logged out." }`

### `GET /user`
Auth required. Returns the current user, same shape as `/register`'s `user` object — this is `UserResource`, reused everywhere a user is serialized.

```json
{
  "data": {
    "id": 2, "name": "Demo Student", "email": "student@student-choice.test",
    "role": "student", "is_banned": false, "banned_reason": null,
    "created_at": "2026-09-29T22:28:23.000000Z",
    "profile": {
      "gpa": 85, "tawjihi_branch": "العلمي", "preferred_region": null, "preferred_city": "طولكرم",
      "budget_max": 30, "photo_url": null, "description": "Demo student account for testing.",
      "preferred_fields": ["تكنولوجيا الاتصالات والمعلومات", "الهندسة والحرف الهندسية"],
      "scholarships": [{ "id": 1, "name": "أبناء العاملين بالتعليم العالي 100%", "discount_percentage": 100 }]
    }
  }
}
```
`scholarships` is only ever set by an admin (see "Admin — students" below) — there's no way for a student to grant themselves one via `PUT /profile`.

---

## Profile

### `PUT /profile`
Auth required. All fields `sometimes` (send only what you're changing).

Request body:
```json
{
  "name": "Demo Student", "email": "student@student-choice.test",
  "gpa": 85, "budget_max": 30, "description": "...",
  "tawjihi_branch_id": 1, "preferred_region_id": null, "preferred_city_id": 1,
  "preferred_field_ids": [9, 14]
}
```
`budget_max` is **per credit hour**, not a total tuition cap — majors range from 68 to 135+ total hours in the real catalog, so a lump-sum budget wouldn't be comparable across them (see `docs/technical-flows.md` §4).

Response: same `UserResource` shape as `GET /user`.

### `POST /profile/photo`
Auth required. `multipart/form-data` with a `photo` file field (image, max 4MB). Response: same `UserResource` shape, with `profile.photo_url` now populated.

---

## Majors & scholarships (public browsing)

### `GET /majors`
Public, paginated (20/page). `?per_page=` and `?page=` supported.

```json
{
  "data": [
    {
      "id": 1,
      "name": "أحياء جزيئية تطبيقية",
      "institution": { "id": 1, "name": "جامعة فلسطين التقنية / خضوري", "type": "جامعة" },
      "faculty": "العلوم التطبيقية",
      "degree_level": "بكالوريوس",
      "field_of_study": "العلوم البيولوجية والعلوم المتصلة بها",
      "campuses": [{ "id": 1, "name": null, "city": "طولكرم", "region": "الضفة الغربية" }],
      "tawjihi_branches": ["العلمي", "الصناعي", "الزراعي"],
      "credit_hour_price": 20,
      "credit_hours": 129,
      "total_cost": 2580,
      "study_duration_text": "حوالي 4 سنوات (تقديري؛ مدة صريحة غير مذكورة في صفحة القبول)",
      "admission_average": 65,
      "min_gpa_to_apply": 65,
      "admission_link": "https://www.ptuk.edu.ps/ar/admissions/"
    }
    // … 1 more on this page (per_page=2 example)
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 39, "per_page": 2, "total": 77, ... }
}
```
Note: no `scholarships` field here, on `show`, or anywhere else on a major — scholarships belong to *students*, not majors (see "Admin — students" below).

### `GET /majors/{id}`
Public. Same shape as above (no `scholarships` field — see note above).

### `GET /majors/match`
**Auth required** (this is the one student-specific endpoint — it scores every major against the logged-in student's profile). `?per_page=` / `?page=` supported; paginates over the already-scored, already-sorted list.

```json
{
  "data": [
    {
      "id": 14,
      "name": "الذكاء الاصطناعي",
      "institution": { "id": 1, "name": "جامعة فلسطين التقنية / خضوري", "type": "جامعة" },
      "faculty": "كلية تكنولوجيا المعلومات",
      "field_of_study": "تكنولوجيا الاتصالات والمعلومات",
      "campuses": [{ "id": 1, "city": "طولكرم", "region": "الضفة الغربية" }],
      "tawjihi_branches": ["العلمي", "الصناعي", "الزراعي", "وتكنولوجيا المعلومات"],
      "credit_hour_price": 26, "net_hour_price": 0,
      "credit_hours": 135, "total_cost": 3510,
      "admission_average": 74, "min_gpa_to_apply": 65,
      "match": {
        "score": 100,
        "breakdown": { "preferences": 100, "gpa": 100, "tawjihi": 100, "budget": 100, "location": 100 }
      }
    }
    // … 76 more, sorted by match.score descending
  ],
  "meta": { "total": 77, "page": 1, "per_page": 2 }
}
```
`match` and `net_hour_price` are the only fields this endpoint adds over `/majors/{id}`. `net_hour_price` is the major's `credit_hour_price` (**not** `total_cost`) after the logged-in student's own scholarship discount (here a 100% scholarship, so `net_hour_price: 0`) — it's **only present when a discount actually applies**; a student with no scholarship never sees this key at all. Budget matching compares against the per-hour price, not the total program cost — see `docs/technical-flows.md` §4 for why and exactly how the score is computed.

### `GET /scholarships`
Public, paginated. This is just the catalog of scholarship *types* (e.g. "children of a martyr — 100%") — who actually holds one is private, only visible via the admin endpoints below.
```json
{
  "data": [
    {
      "id": 1, "name": "أبناء العاملين بالتعليم العالي 100%",
      "description": "أبناء العاملين بالتعليم العالي 100%",
      "discount_percentage": 100, "eligibility_notes": null
    }
  ],
  "meta": { "total": 12, ... }
}
```

### `GET /scholarships/{id}`
Public. Same single-object shape as above.

---

## Lookup dropdowns (public)

All five return the **full unpaginated list** (small tables, meant for populating `<select>`s) — no `data`/`meta` wrapper, just a plain array.

`GET /regions`
```json
[{ "id": 1, "name": "الضفة الغربية" }]
```

`GET /cities` — includes the parent region:
```json
[
  { "id": 1, "region_id": 1, "name": "طولكرم", "region": { "id": 1, "name": "الضفة الغربية" } },
  { "id": 2, "region_id": 1, "name": "جميع المقرات", "region": { "id": 1, "name": "الضفة الغربية" } }
  // … 3 more
]
```

`GET /degree-levels`
```json
[{ "id": 1, "name": "بكالوريوس" }, { "id": 2, "name": "دبلوم متوسط" }]
```

`GET /fields-of-study` — 16 rows, e.g.:
```json
[{ "id": 1, "name": "العلوم البيولوجية والعلوم المتصلة بها" }, { "id": 2, "name": "الأعمال والإدارة" }]
```

`GET /tawjihi-branches`
```json
[{ "id": 1, "name": "العلمي" }, { "id": 2, "name": "الصناعي" }, { "id": 3, "name": "الزراعي" }]
```

---

## Support tickets (student)

### `GET /support-tickets`
Auth required. Returns only the current user's tickets, paginated.
```json
{
  "data": [
    {
      "id": 1, "user_id": 2, "subject": "Question about scholarships",
      "message": "Can I combine two scholarships for the same major?",
      "admin_reply": "No, only one scholarship can be applied per major. Pick whichever gives the larger discount.",
      "status": "answered",
      "created_at": "2026-10-13T14:38:32.000000Z", "updated_at": "2026-10-13T14:39:00.000000Z"
    }
  ],
  "meta": { "total": 1, ... }
}
```

### `POST /support-tickets`
Auth required. `{ "subject": "...", "message": "..." }` → `201`, returns the created ticket (same shape as above, `admin_reply: null`, `status: "open"`).

---

## Admin — institutions / faculties / campuses

All behind `auth:sanctum` + `admin` middleware (`403` otherwise). Full CRUD (`index`, `show`, `store`, `update`, `destroy`) on each — these three are plain Eloquent models (no API Resource wrapping), so `store`/`update` return the raw model row, `index`/`show` return it with relations loaded.

### `GET/POST/PUT/DELETE /admin/institutions`
`POST` body: `{ "name": "...", "institution_type": "جامعة", "official_website": null }`
`POST` response `201`:
```json
{ "id": 2, "name": "ZZ Docs Test Institution", "institution_type": "جامعة", "created_at": "...", "updated_at": "..." }
```
`DELETE` → `204` no body. (Deleting an institution cascades to its campuses, faculties, and majors — see the migrations' `cascadeOnDelete()`.)

### `GET/POST/PUT/DELETE /admin/faculties`
`POST` body: `{ "institution_id": 2, "name": "..." }` → same plain-model shape as above.

### `GET/POST/PUT/DELETE /admin/campuses`
`POST` body: `{ "institution_id": 2, "city_id": 1, "name": null }` (name optional — a branch label).
```json
{ "institution_id": 2, "city_id": 1, "created_at": "...", "updated_at": "...", "id": 6 }
```

---

## Admin — majors & scholarships

These two use `MajorResource`/`ScholarshipResource` (wrapped in `{"data": ...}`), matching the public browse endpoints exactly.

### `GET/POST/PUT/DELETE /admin/majors`
`POST` body — scalar fields plus two pivot arrays (no scholarship linkage here — see "Admin — students" below):
```json
{
  "institution_id": 2, "faculty_id": 14, "field_of_study_id": 1, "name": "ZZ Docs Test Major",
  "credit_hour_price": 40, "credit_hours": 132, "admission_average": 70, "min_gpa_to_apply": 60,
  "campus_ids": [6], "tawjihi_branch_ids": []
}
```
`degree_level_id`, `classification` (removed — see `docs/technical-flows.md` §7), `study_duration_text`, and `admission_link` are all optional/nullable. Response `201` is the full `MajorResource` shape (identical to `GET /majors/{id}`).

`campus_ids` / `tawjihi_branch_ids` are each `sync()`'d — omit a key entirely to leave that pivot untouched; send `[]` to clear it. An empty `tawjihi_branch_ids` means "open to all branches" (see matching engine notes).

### `GET/POST/PUT/DELETE /admin/scholarships`
This manages the scholarship *catalog* only (name/description/discount/eligibility notes) — assigning one to a student happens on the student endpoints below, not here.

`POST` body: `{ "name": "...", "discount_percentage": 50, "description": "...", "eligibility_notes": null }`
Response `201`:
```json
{ "data": { "id": 13, "name": "ZZ Docs Test Scholarship", "description": "Example scholarship for docs.", "discount_percentage": 50, "eligibility_notes": null } }
```

`GET /admin/scholarships` additionally eager-loads who holds each one (the public `GET /scholarships` deliberately does not, since that's private):
```json
{ "id": 1, "name": "أبناء العاملين بالتعليم العالي 100%", "description": "أبناء العاملين بالتعليم العالي 100%", "discount_percentage": 100, "eligibility_notes": null, "students": [{ "id": 2, "name": "Demo Student" }] }
```

---

## Admin — lookup CRUD

Same five tables as the public dropdowns, write-only here (`index` isn't duplicated under `/admin` — use the public `GET` endpoints above to read). Routes use an explicit `{id}` (not Laravel's `apiResource()`) so one shared controller method works for all five — see `docs/technical-flows.md` §7.

`POST /admin/regions`, `/admin/cities`, `/admin/degree-levels`, `/admin/fields-of-study`, `/admin/tawjihi-branches`
Body: `{ "name": "..." }` (`cities` additionally requires `region_id`).

Response `201` (region example):
```json
{ "name": "ZZ Docs Test Region", "updated_at": "...", "created_at": "...", "id": 2 }
```

`PUT /admin/{table}/{id}` → `200`, same shape:
```json
{ "id": 2, "name": "ZZ Docs Test Region (renamed)", "created_at": "...", "updated_at": "..." }
```

`DELETE /admin/{table}/{id}` → `204` no body.

---

## Admin — students

Route-model-binding resolves `{student}` against the whole `users` table, so every method here guards that the target's `role === 'student'` (returns `404` on an admin id) — see `docs/technical-flows.md` §5.

### `GET /admin/students`
Paginated, `UserResource` shape (same as `GET /user`), one entry per student.

### `GET /admin/students/{id}`
Single student, same shape.

### `POST /admin/students`
Creates both the `users` row and its `student_profiles` row in one call.
```json
{ "name": "...", "email": "...", "password": "...", "gpa": 80, "tawjihi_branch_id": 1, "preferred_city_id": 1, "budget_max": 30, "preferred_field_ids": [9], "scholarship_ids": [1] }
```
`password` required on create; omit it on `PUT` to leave the current password unchanged. `scholarship_ids` is `sync()`'d against `student_scholarships` — **this is the only place a scholarship gets assigned to a student** (the scholarship IDs come from `GET /admin/scholarships`, the catalog above). A scholarship isn't tied to a particular major here — it applies to whichever major this student is matched against (see `docs/technical-flows.md` §2, §4).

### `PUT /admin/students/{id}`
Same body shape (minus required `password`). Updates account fields, profile fields, and `scholarship_ids` together.

### `DELETE /admin/students/{id}`
`204`. Cascades to delete the `student_profiles` row too.

### `POST /admin/students/{id}/ban`
Body: `{ "reason": "optional string" }`. Sets `is_banned = true`, **and deletes every Sanctum token that student currently holds** — they're logged out everywhere immediately, and `POST /login` will refuse them (`403`) until unbanned. Response: `UserResource` with `is_banned: true`.

### `POST /admin/students/{id}/unban`
No body. Clears the ban. Response: `UserResource` with `is_banned: false`. (The student still needs to log in again to get a fresh token.)

---

## Admin — support tickets

### `GET /admin/support-tickets`
Paginated, every student's tickets, each with the ticket owner's `id`/`name`/`email` attached.

### `PUT /admin/support-tickets/{id}`
Body: `{ "admin_reply": "...", "status": "answered" }` (`status` optional, defaults to `"answered"` when omitted — valid values: `open`, `answered`, `closed`).
```json
{
  "id": 1, "user_id": 2, "subject": "Question about scholarships",
  "message": "Can I combine two scholarships for the same major?",
  "admin_reply": "No, only one scholarship can be applied per major. Pick whichever gives the larger discount.",
  "status": "answered", "created_at": "...", "updated_at": "..."
}
```
