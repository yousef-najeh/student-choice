# Student Choice — Technical Flows

This document explains how the major pieces of the backend actually work end to end:
request → middleware → controller → service/model → response. Pair it with
`docs/database-erd.drawio` (open at [diagrams.net](https://app.diagrams.net) / draw.io) for the
data model.

## Stack recap

- **Laravel 12**, PHP 8.2 (via XAMPP), **MySQL** (via XAMPP).
- **Laravel Sanctum** — stateless bearer-token API auth (no cookies/sessions for the API).
- **PhpSpreadsheet** (the engine behind `maatwebsite/excel`) — reads the source `.xlsx` for `import:majors`.
- A **Blade + vanilla JS** frontend under `resources/views/app/*`, calling the same JSON API any other frontend would.

All API routes live in `routes/api.php`, prefixed `/api` automatically. All page routes live in `routes/web.php`.

---

## 1. Authentication

### Register (`POST /api/register`)
1. `RegisterRequest` validates name/email/password (password confirmed, email unique).
2. `AuthController::register` creates a `User` row with `role = student`, and an **empty** `StudentProfile` row linked to it — every student always has exactly one profile row from the moment they exist, even before they fill anything in (see §3).
3. A Sanctum personal access token is minted (`$user->createToken('api')`) and returned alongside the user as JSON. The frontend stores this token in `localStorage` and sends it as `Authorization: Bearer <token>` on every subsequent request.

### Login (`POST /api/login`)
1. `LoginRequest` validates email/password are present.
2. Look up the user by email, verify the password hash. Wrong credentials → `422` via `ValidationException`.
3. **Ban check happens here too**: if `is_banned`, respond `403` with the ban reason *before* issuing a token — a banned user can never mint a new token by logging in again.
4. Otherwise, mint and return a token exactly like register.

### Every other authenticated request
- Route group in `routes/api.php`: `Route::middleware(['auth:sanctum', 'not_banned'])->group(...)`.
- `auth:sanctum` resolves the bearer token to a `User` and populates `$request->user()`. No valid/unexpired token → `401 Unauthenticated`.
- `EnsureUserIsNotBanned` (aliased `not_banned`) runs **after** `auth:sanctum` (order matters — it needs `$request->user()` already resolved) and re-checks `is_banned` on *every* request, not just login. If a user was banned mid-session, this middleware deletes their current token on the spot and returns `403`. In practice this rarely fires because `StudentController::ban()` already deletes all of a student's tokens the moment they're banned (see §5) — it's a defensive backstop, not the primary mechanism.
- Admin-only routes additionally get the `admin` alias (`EnsureUserIsAdmin`), which checks `$request->user()->role === 'admin'` and returns `403` otherwise.

```
Request → auth:sanctum (resolve user, 401 if none) → not_banned (403 if banned) → [admin] (403 if not admin) → Controller
```

---

## 2. Data model shape (see the ERD for the full picture)

The schema is deliberately normalized instead of a few wide tables:

- **Lookup tables** (`regions`, `cities`, `degree_levels`, `fields_of_study`, `tawjihi_branches`) hold every repeating string exactly once. Both `majors` and `student_profiles` reference these by foreign key — never by raw string — so "Computer Science" is the same row everywhere it's used, and a student's preferred field of study is directly comparable to a major's field of study.
- **`institutions` → `campuses` → `majors`**: an institution (a university) can run several campuses (each pinned to one city), and a major is linked to *specific* campuses via the `major_campus` pivot — because the same major is often taught at more than one branch (see §6). A major's location for matching purposes is "wherever its campuses are," not a single city on the institution.
- **`users` vs `student_profiles`**: `users` is auth-only (name/email/password/role/ban state). Everything student-specific (GPA, tawjihi branch, budget, preferred location, photo, bio) lives in `student_profiles`, one-to-one with `users`. Admin accounts simply have no `student_profiles` row.
- **`student_preferred_fields`** is a pivot to `fields_of_study` — a student's "preferences" are a set of real foreign keys, not a JSON blob, so they can be joined/filtered/matched like any other relation.
- **`major_tawjihi_branch`**: if a major has **zero** rows here, the matching engine treats it as "open to every branch" (this is exactly how the importer encodes "جميع فروع الثانوية العامة" — see §6) — an *absence* of restriction rows means no restriction, not "excludes everyone."
- **`student_scholarships`**: a scholarship belongs to a *student*, not to a major. It's an award admin grants a specific student (e.g. "50% off"), and it discounts whichever major that student is matched against — not a fixed set of majors. `scholarships` itself stays a catalog table (name, description, discount %, eligibility notes) so admin reuses named scholarship types instead of retyping a raw percentage each time; `student_scholarships` is the many-to-many that says who actually holds one.

---

## 3. Profile update (`PUT /api/profile`, `POST /api/profile/photo`)

1. `UpdateProfileRequest` validates every field as `sometimes` (partial updates allowed) — including that `tawjihi_branch_id`/`preferred_region_id`/`preferred_city_id`/`preferred_field_ids.*` actually `exists` in their lookup tables.
2. `ProfileController::update`:
   - Updates `name`/`email`/`password` on the `User` row (password only if present in the request).
   - Updates the scalar profile fields (`gpa`, branch, region, city, budget, description) on the existing `StudentProfile` row.
   - If `preferred_field_ids` was sent, `sync()`s the `student_preferred_fields` pivot to exactly that set (send `[]` to clear all preferences).
3. Photo upload is a separate endpoint (`POST /api/profile/photo`, multipart) so the frontend doesn't have to encode a file into the same JSON PUT. The old file (if any) is deleted from `storage/app/public/photos` before the new one is stored, and `photo_path` is updated. `UserResource` turns that stored path into a public URL (`asset('storage/...')`) for the frontend.

---

## 4. Major matching (`GET /api/majors/match`) — the core feature

This is a **weighted score**, not a hard filter — every major is always returned, ranked, with a visible breakdown of *why* it scored the way it did. Logic lives in `App\Services\MajorMatchingService`, weights in `config/matching.php`.

```
MajorController::match()
  → find the logged-in student's StudentProfile (+ preferredFields, + scholarships)
  → MajorMatchingService::scoredMajorsFor($profile)
      studentDiscount = MAX(discount_percentage) across the student's own scholarships — computed
                        once, since a scholarship belongs to the student, not to any one major
                        (see §2) — so it applies the same regardless of which major is being scored

      for every Major (eager-loaded with institution/faculty/campuses.city.region/tawjihiBranches):
        preferences_score  = 100 if major.field_of_study_id ∈ student's preferred fields, else 0   (50 if student set no preferences at all — "neutral")
        gpa_score          = 100 if gpa ≥ admission_average
                             linear 40→100 if min_gpa_to_apply ≤ gpa < admission_average
                             0 if gpa < min_gpa_to_apply
                             50 if gpa or admission_average unknown ("neutral")
        tawjihi_score      = 100 if major has no branch restrictions, OR student's branch is in the allowed set
                             0 if branches ARE restricted and student's isn't one of them
                             50 if student hasn't set a branch yet
        budget_score       = 100 if credit_hour_price, net of studentDiscount, ≤ student's budget_max
                             tapering down to 0 the further over budget it is
                             50 if budget_max or credit_hour_price is unknown
        location_score     = 100 if any of the major's campuses is in the student's preferred city
                             60 if not, but same region
                             0 if neither
                             50 if student set no location preference

        total = Σ (dimension_score × weight)   — weights: preferences 30%, gpa 25%, tawjihi 20%, budget 15%, location 10%
        net_hour_price = credit_hour_price net of studentDiscount, only set (and only returned by
                   the API) when a discount actually applies — otherwise the UI just shows
                   credit_hour_price as-is

  → sort all majors by total score, descending
  → paginate in PHP (the whole catalog is scored every request — fine at this dataset size;
     see "Known limitations" below if the catalog grows a lot)
```

Budget is compared **per credit hour**, not against the total program cost — majors range from 68 to 135+ total hours in the real catalog, so a lump-sum total isn't comparable across them, but the per-hour price is. `student_profiles.budget_max` means "the most I can afford per credit hour," not a total tuition cap (see §3's `UpdateProfileRequest`). `total_cost` (`credit_hour_price × credit_hours`) is still returned by the API and shown on the major detail page for reference, it just isn't what matching or the card's primary "Cost/hr" figure uses.

`MajorResource` includes `net_hour_price` and the `match.score`/`match.breakdown` object only when present (`$this->when(...)`), so the exact same resource class serves both `/majors` (plain browsing, no score, no net_hour_price) and `/majors/match` (scored, with net_hour_price when the student has a scholarship).

**Why weighted instead of filtered**: a hard filter (e.g. "hide any major the student's GPA doesn't meet") would show a student *nothing* if their GPA falls just short everywhere, or if they haven't filled in their profile yet. Weighting means there's always a ranked list, low-fit majors just sink to the bottom with a visibly low score per dimension — this was a deliberate choice made when planning the feature.

---

## 5. Admin: student management & bans

`StudentController` (under `/api/admin/students/*`) does full CRUD on student accounts **and** their profile in one request (e.g. `POST /admin/students` creates both the `User` and `StudentProfile` rows; `PUT` updates both) — including `scholarship_ids`, which `sync()`s the student's `student_scholarships` pivot. This is also the only place scholarships get assigned — there's no self-service way for a student to grant themselves one via `PUT /profile`.

Every method starts by calling `ensureIsStudent($user)`, which `abort(404)`s if the target's `role !== 'student'`. This matters because Laravel's route-model-binding resolves `{student}` against the whole `users` table by id — without this guard, an admin route could accidentally target *another admin account* by guessing an id.

**Ban** (`POST /admin/students/{id}/ban`):
1. Sets `is_banned = true`, stores the optional `reason`, stamps `banned_at`.
2. **Deletes every Sanctum token that student has** (`$student->tokens()->delete()`) — this immediately invalidates any session they're currently using, on any device. They can't make another authenticated request without a new token, and `login()` (§1) refuses to issue one while banned.

**Unban** just flips `is_banned` back and clears the reason/timestamp; the student must log in again to get a fresh token.

---

## 6. Importing the real catalog (`php artisan import:majors [path]`)

`App\Console\Commands\ImportMajors` reads the provided `.xlsx` (18 fixed columns, A–R) via PhpSpreadsheet and, **for every row**:

1. Header sanity check — if columns A/B/C/... don't match the expected Arabic headers, the whole import aborts rather than silently importing garbage into the wrong fields.
2. `firstOrCreate`s the row's `Region`, `Institution`, `DegreeLevel`, `FieldOfStudy` by name.
3. **Campuses (multi-city handling)**: the "city/branch" column sometimes lists more than one city separated by `/` (e.g. `"طولكرم / رام الله"`). Each city name becomes its own `City` + `Campus` row (`institution_id` + `city_id`, unique pair), and **all** of them get linked to the major — this is exactly how "College X runs this major at both its Tulkarm and Ramallah branches" gets represented.
4. **Tawjihi branches**: the column is free text like `"العلمي، الصناعي، الزراعي"` or `"جميع فروع الثانوية العامة"` (= "all branches"). If the cell contains the word "جميع" ("all"), **no branch rows are created at all** — intentionally, because an empty `major_tawjihi_branch` set is exactly what `MajorMatchingService` reads as "open to everyone" (§2, §4). Otherwise the cell is split on commas and on `" و" + "ال"` (the Arabic "and the—" conjunction glued onto the next word, e.g. `"العلمي والصناعي"`) to recover individual branch names — a best-effort parse of messy natural-language text, not a full NLP pass.
5. **Scholarships**: the column is one free-text blob per row like `"2026-2027: أبناء الشهيد 100%؛ الأوائل 100%؛ ..."`. It's split on `؛` (Arabic semicolon) into individual clauses; each clause becomes (or reuses, by exact-text match) one `Scholarship` *catalog* row, with any `NN%` in the clause captured as `discount_percentage`. These aren't linked to the major being imported — scholarships belong to students, not majors (§2) — the import just seeds the catalog of scholarship types an admin can later assign to a specific student via `/admin/students/{id}`.
6. The `Major` itself is `updateOrCreate`d keyed on `(institution_id, name)` — **re-running the import is safe**: it refreshes existing rows instead of duplicating them.
7. Finally, the major's campuses/tawjihi-branches pivots are `sync()`'d to exactly what this row produced.

Run it any time the source spreadsheet changes: `php artisan import:majors "جامعه خضوري.xlsx"`.

---

## 7. Lookup CRUD (regions, cities, degree levels, fields of study, tawjihi branches)

All five share one abstract `App\Http\Controllers\Api\Lookup\LookupController`: `index()` is public (dropdown data for any frontend), `store`/`update`/`destroy` are wired only under the `admin` middleware group. Each concrete controller (`RegionController`, `DegreeLevelController`, ...) is just `protected string $model = Region::class;` — one line. `CityController` is the one override, since a city additionally needs `region_id`.

These routes are registered with an explicit `{id}` segment (not Laravel's `apiResource()` helper) specifically so the shared base controller's `update(Request $request, int $id)` signature binds correctly across all five — `apiResource()` would derive a different route-parameter name per resource (`region`, `city`, `degree_level`, ...), which wouldn't match a single shared method signature.

**Note on `classifications`**: an earlier version of the schema had a `classifications` lookup table (a finer sub-category *inside* a field of study, e.g. "Business Administration" inside "Business & Management"). It was removed — it duplicated `fields_of_study` without actually being linked to it (no `field_of_study_id` FK), and the matching engine never used it. If finer-grained categorization is needed later, the right fix is a proper parent-child relationship (`classifications.field_of_study_id` FK, the way `cities.region_id` works), not a second unlinked flat list.

---

## 8. Frontend

`resources/views/app/*` + `public/js/api.js` + `public/js/ui.js` + `public/css/app.css`. No build step, no framework — plain Blade pages with inline `<script>` blocks calling `fetch`. Auth is enforced client-side (`requireAuth()`/`requireAdmin()` in `api.js`, redirecting to `/login` when there's no token in `localStorage`) because the Sanctum token lives in `localStorage`, not a cookie — the server has no way to know who's logged in on a plain page load, only the API requests that follow carry the bearer token.

- `Api` (in `api.js`) wraps `fetch`, always sends `Accept: application/json`, attaches `Authorization: Bearer <token>` from `localStorage` when present, and throws on non-2xx so pages can `catch` and show the error.
- `ui.js` provides `toast()`, `confirmModal()` (replacing native `alert`/`confirm`/`prompt`), `scoreRing()` (the SVG radial match-score indicator), `dimBars()` (the per-dimension match breakdown), and `paginationControls()` (Prev/Next + page count, driven by a Laravel paginator's `meta`).
- `/` redirects straight to `/login`, which itself redirects to `/dashboard` client-side if a valid token is already stored (skips showing the login form to an already-authenticated visitor).
- `/login` — register or log in; stores the returned token + user in `localStorage`.
- `/dashboard` — the student's primary landing page: runs `/api/majors/match` immediately and shows ranked results as cards with a score ring + colored breakdown bars; shows a "complete your profile" nudge banner (linking to `/profile`) whenever GPA/branch/budget are missing from `GET /user`'s `profile` object, since those gaps mean several scoring dimensions fall back to neutral.
- `/profile` — the settings form (account, academic info, preferences, photo) that used to live on the dashboard — split out so the dashboard can lead with results instead of a form.
- `/majors` — browse + search `/api/majors` and `/api/scholarships`. Fetches the whole catalog once (`per_page=500` — the catalog is small) so the search box actually searches everything instead of just the first paginated page, then paginates the *filtered* results client-side.
- `/majors/{id}` — major detail page: full campus/scholarship/admission info from `GET /api/majors/{id}`, plus (students only) that major's own score breakdown, found by matching its id against `GET /api/majors/match` rather than adding a dedicated endpoint.
- `/admin` — tabbed panel (Majors / Scholarships / Students / Lookups) hitting the `/api/admin/*` endpoints, gated behind `requireAdmin()`; each table now has real pagination via `paginationControls()`. The nav's Admin link only renders for `role === 'admin'` (`nav.blade.php`) — a student never sees a link that would just 403 them.
- `resources/views/errors/404.blade.php` / `500.blade.php` — branded error pages (Laravel auto-renders these for non-matching web routes / server errors; API routes still get a plain JSON error, since `Accept: application/json` is always sent).

---

## Known limitations / next steps

- **Matching at scale**: `MajorMatchingService` scores the *entire* majors table on every `/majors/match` request, in PHP, then sorts. Fine for a catalog of dozens–low-thousands of majors; if the catalog grows much larger, the scoring would need to move into a SQL query (or be cached per-student and invalidated on profile/catalog change).
- **Scholarship/branch text parsing is best-effort**: the importer's regex-based splitting of free-text Arabic cells (§6) won't perfectly parse every phrasing the source data might use. A handful of tawjihi-branch and scholarship rows may need manual cleanup via the admin CRUD endpoints after import — this was a deliberate scope/time tradeoff, not an oversight.
- **No rate limiting / throttling** has been added beyond Laravel's defaults — worth adding to `/login` and `/register` specifically before any real deployment.
- **Photo storage is local disk** (`storage/app/public`) via `storage:link` — fine for XAMPP/local dev; would need to move to S3-compatible storage for a real multi-server deployment.
