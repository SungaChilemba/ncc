# Reporting & Tracking — Backend Connection Plan

Status: **Implemented for phases 1-5**, with malware scanning and notifications deliberately deferred. See `docs/case-management.md` for the operating model.
Scope: `resources/views/pages/reporting.blade.php`, public reporting routes, admin handling of reports, reporting statistics.

---

## 1. What exists today

### Public page and routes

| Piece | Current state | File |
|---|---|---|
| File Complaint | Posts to `reporting.store`; writes into `site_contents` | `routes/web.php:19`, `PageController::storeReport()` |
| Attach Evidence | Posts to `reporting.evidence.store`; looks up a case by reference only | `routes/web.php:20`, `PageController::storeEvidence()` |
| Track Case | **Static demo markup — no route, no form, no query** | `reporting.blade.php:192-213` |
| Statistics | **Hard-coded numbers and chart data** | `reporting.blade.php:215-237`, `:274-303` |
| Admin handling | Generic CMS content screens only | `ContentController`, `admin/contents/index.blade.php` |

### How complaint data is stored now

`storeReport()` creates a `SiteContent` row:

- `section = reporting`, `type = submission`, `category = Case Submissions`
- Child name, reporter name, contact and description live in the `data` JSON and the `description` column.
- Reference format: `NCC-YYYY-NNNNN` (`random_int(10000, 99999)`) — only ~90,000 possibilities per year and easy to enumerate.
- `is_published = false`, but it is not access-controlled beyond the admin login.

Evidence is written to the **public disk** (`storage/app/public/evidence/<reference>/`) and recorded as a `MediaAsset`. That means:

- Any file is reachable at `/storage/evidence/<reference>/<filename>` with no login.
- The reference number is the only secret, and it is guessable.
- There is no per-case limit on total evidence, no malware scan, no EXIF stripping, and no file-content validation beyond the extension/MIME group.

### Admin handling now

- `AdminMiddleware` only checks `canManageCms()` (`is_admin` OR role `admin`/`editor`).
- Every CMS editor can open a report in the normal content editor, see the raw `data` JSON, edit/delete it, and bulk-delete submissions.
- `ContentRevision` snapshots and `ContentAudit.changes` duplicate the PII on every edit.
- There is no case status workflow, assignment, internal notes, timeline, custody log, or view audit.
- Every access to a case is effectively invisible.

### Root problem

The reporting feature reuses the CMS content table. That table is designed for public marketing content, is broadly editable, is revisioned, and is searchable by anyone with CMS access. **Child-protection case data should not live there.**

**Recommendation: introduce a dedicated case domain (`reports`, `report_evidence`, `report_events`, `report_notes`, `report_access_logs`) and keep `site_contents` for public page copy only.**

---

## 2. Goals and non-goals

### Goals

1. Make complaint submission, evidence attachment, tracking and statistics fully backed by the database.
2. Treat every report as sensitive personal data for a child and reporter.
3. Enforce least-privilege, role-based and assignment-based access to case data.
4. Produce an audit trail for every view, download, change, export and deletion.
5. Give reporters a safe, non-enumerable way to track their case and add evidence.
6. Publish only aggregated, non-identifying statistics.
7. Keep the existing UI look and feel; replace the demo/static parts with real data.

### Non-goals (for this phase)

- Public case search, case comments, or reporter-to-officer chat.
- Full integration with police/social-welfare systems.
- Replacing the CMS. Only reporting submissions move out of it.
- Native mobile apps.

---

## 3. Proposed architecture

```
Public visitor                 Admin / case staff
     |                                |
     v                                v
PublicReportController          Admin\ReportController
  - create (complaint)            - queue / filters
  - verify (ref + PIN)            - detail (field-redacted)
  - track                         - assign / status / notes
  - add evidence                  - evidence stream
     |                           - export / delete
     v                                v
ReportService / ReportPolicy / ReportAccessLogger
     |                                |
     +---------> reports <------------+
                 report_evidence
                 report_events
                 report_notes
                 report_access_logs
                     |
        encrypted PII columns + private evidence disk
                     |
          Queue: virus scan, EXIF strip, notify, purge
```

### Key decisions

| Decision | Recommendation | Why |
|---|---|---|
| Storage model | New dedicated tables, not `site_contents` | Field-level control, retention, audit, RBAC |
| Case secret | Reference + 6-digit PIN, PIN hashed | Reference alone is enumerable |
| Evidence storage | Private disk, streamed by controller | Current files are publicly downloadable |
| PII at rest | Encrypted casts for names, contact, description | DB/backup leak containment |
| Access model | `case_role` separate from CMS `role` | CMS editors should not automatically see child data |
| Delete/export | Manager/super-admin only, reason required | Separation of duties |
| Public stats | Aggregates with small-cell suppression | Prevent re-identification |
| Existing data | Backfill + revoke public evidence URLs | Old URLs remain live otherwise |

---

## 4. Data model

### 4.1 `reports` (the case)

```text
id
reference_number        string(24) unique       // NCC-2026-7KQ4M9, Crockford base32
access_pin_hash         string                  // Hash::make(6-digit PIN), never plaintext
pin_attempts            unsigned int default 0
pin_locked_until        timestamp nullable
status                  string(30) default 'received'   // received|triage|investigation|referred|resolved|closed|duplicate|withdrawn
priority                string(20) default 'standard'   // standard|urgent
violation_type          string(60)
incident_date           date nullable
district                string(120)
village                 string(160) nullable
child_age               unsigned tinyint
child_gender            string(20)
child_name              text nullable encrypted        // encrypted cast
description             text encrypted                 // encrypted cast
reporter_name           text nullable encrypted        // encrypted cast
reporter_contact        text nullable encrypted        // encrypted cast
reporter_contact_hash   string(64) nullable index      // HMAC for exact-match dedupe, not reversible
is_anonymous            boolean default true
consent_given            boolean default false
assigned_to             foreignId users nullable
received_at             timestamp
acknowledged_at         timestamp nullable
closed_at               timestamp nullable
closure_outcome         string nullable
retention_delete_at     timestamp nullable
legal_hold              boolean default false
legacy_reference        string nullable index
created_at / updated_at
deleted_at              softDeletes
```

Indexes: `reference_number` (unique), `status`, `district`, `violation_type`, `assigned_to`, `received_at`, `retention_delete_at`.

Notes:

- `district`, `violation_type`, `child_age`, `incident_date` stay queryable for stats; they are coarse, not directly identifying.
- Names, contact and free-text description use Laravel `encrypted` casts. They cannot be `LIKE`-searched — search the queue by reference, district, type, status and date only.
- `reporter_contact_hash` = `hash_hmac('sha256', normalized contact, config('app.key'))` for duplicate detection without storing a second copy.
- Consider a dedicated `REPORT_ENCRYPTION_KEY` (custom cast) so the key can be rotated independently of `APP_KEY`; otherwise document `APP_KEY` escrow/rotation, because rotating `APP_KEY` makes all case PII unreadable.

### 4.2 `report_evidence`

```text
id
report_id              foreignId reports cascadeOnDelete
uploaded_by            foreignId users nullable       // null = reporter
upload_token_id        nullable                        // links to the verified public session
disk                   string default 'report_evidence'
path                   string                          // random filename, never user input
original_name          string
mime_type              string(120)
size                   unsigned bigint
sha256                 string(64)                      // dedupe / custody
scan_status            string default 'pending'        // pending|clean|infected|failed
scan_message           text nullable
redacted_at            timestamp nullable
uploaded_at            timestamp
deleted_at             softDeletes
```

### 4.3 `report_events` (timeline + audit-visible state changes)

```text
id
report_id              foreignId cascadeOnDelete
from_status            string nullable
to_status              string nullable
type                   string(40)   // received|triaged|assigned|status_change|evidence_added|referred|closed|reopened
message                text nullable          // public-safe text only
is_public              boolean default false
created_by             foreignId users nullable
created_at
```

The public tracking page renders only `is_public = true` events. Internal notes never appear here.

### 4.4 `report_notes` (internal only)

```text
id
report_id              foreignId cascadeOnDelete
user_id                foreignId users
body                   text encrypted
created_at / updated_at
```

### 4.5 `report_access_logs` (append-only custody log)

```text
id
report_id              foreignId nullable
user_id                foreignId nullable
action                 string(40)   // viewed|identity_viewed|evidence_listed|evidence_downloaded|status_changed|assigned|note_added|exported|deleted|break_glass
fields_accessed        json nullable
reason                 text nullable
ip_hash                string(64) nullable
user_agent_hash        string(64) nullable
created_at
```

- Never update or delete rows from the application.
- Revoke `DELETE` on this table for the application DB user; optionally ship a copy to S3 Object Lock / append-only storage.
- Log **views**, not only writes. That is the core of "who looked at this child's file".

### 4.6 Optional later tables

- `report_referrals` (agency, referral reference, date, consent basis).
- `report_duplicates` (report_id ↔ duplicate_of_report_id, confirmed_by).
- `report_notifications` (channel, status, sent_at) for SMS/email delivery tracking.

---

## 5. Public flows and endpoints

### 5.1 Submit a complaint — `POST /reporting`

Keep the existing form fields and add:

- Honeypot field + minimum time-to-submit (e.g. 3 seconds).
- Optional CAPTCHA (Cloudflare Turnstile / hCaptcha) after risk signals.
- Consent checkbox: privacy notice + confirmation that uploading media of a child has consent.
- Idempotency token generated when the form is rendered, stored in cache for 10 minutes. A double-submit returns the same reference instead of creating a duplicate.
- Validation: age 0–17, description 10–5,000 chars, district allow-list (or free text with max length), `incident_date` not in the future, contact format detection (phone/email).

Response: **reference + PIN**, shown once, with clear guidance to write it down. Never show the PIN in a URL, query string, or email body if avoidable.

Reference format: `NCC-<year>-<6 chars>` using Crockford base32 (no `0/O`, `1/I/L`). 32^6 ≈ 1.07 billion combinations per year. Generate with `random_int`, retry on collision, keep unique index.

PIN: 6 digits for phone usability. Store only a hash (Argon2id or bcrypt). Compare with `Hash::check()` (timing-safe). Alternative for reporters with email/phone: a 256-bit magic link token in addition to the PIN. Do not replace the PIN by a link only, because many reporters will not have email.

### 5.2 Verify a case — `POST /reporting/verify`

- Input: reference + PIN.
- On success: regenerate the session, set a short-lived server-side grant (e.g. 15 minutes) for that report only, redirect to track or evidence.
- On failure: **one generic error** for both "no such reference" and "wrong PIN". Add incremental delay after failures, a per-reference attempt counter, and a per-IP limit. Do not reveal whether the reference exists.
- Lock the PIN for a period after N failures (e.g. 5 failures → 15 minutes), but allow the legitimate reporter to recover via the 116 hotline and staff verification.
- Never log the submitted PIN or reference in application logs.

### 5.3 Track a case — `GET/POST /reporting/track`

Show, after verification:

- Reference, status (public label), date received, last update.
- Public timeline only.
- Violation type and district (already known to the reporter).
- Estimated response window and the 116 hotline.
- **Never** show the description, child name, reporter name/contact, internal notes, officer name, or evidence thumbnails.

### 5.4 Attach evidence — `POST /reporting/evidence`

Requires verification (reference + PIN, or an active 15-minute grant from the same session).

- Up to 5 files per request and a hard per-case cap (e.g. 20 files / 100 MB total).
- Allowed: JPEG, PNG, MP3, MP4, PDF. Validate by file content (`finfo` / `getimagesize` / `%PDF-`), not only extension.
- Store with a random filename on the private disk; keep the original name only as metadata.
- Queue a virus scan; mark `pending` until clean; do not serve `pending`/`infected` files.
- Strip EXIF/GPS from images before accepting (GD re-encode or `intervention/image`), or document the residual risk and add the library.
- Return a neutral confirmation; do not echo file paths.
- Audit: `evidence_added` on the timeline (public if appropriate) + `report_access_logs`.

### 5.5 Evidence download

- No public URL. Serve through `Admin\ReportEvidenceController@show` (and a reporter-scoped route if needed).
- Authorization: policy check + assignment + scan_status = clean.
- Response headers: `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`, `Cache-Control: no-store, private`, `Content-Security-Policy: default-src 'none'; sandbox`.
- Prefer short-lived signed URLs generated only after the policy check. Log every download with user, report, evidence id, time and IP hash.
- Store under a dedicated disk `report_evidence` with `root => storage_path('app/report-evidence')`, `visibility => private`, no `serve` option. It must **not** be inside `storage/app/public` and must not be covered by `storage:link`.

### 5.6 Statistics

- Public tab reads real aggregates from `reports` (received/closed counts, resolution rate, average days to close, cases by violation type, monthly trend).
- Cache for 1 hour; invalidate on status change.
- **Small-cell suppression**: never publish a bucket of 1–4; show `—` or `<5`. Apply complementary suppression so totals cannot be used to derive a hidden cell. Do not cross-tabulate district × age × violation type.
- The chart receives server-generated labels/data; remove the hard-coded array in `reporting.blade.php`.
- Optional JSON endpoint `GET /reporting/statistics` (cached, rate-limited) if the page should refresh without reload.

---

## 6. Roles, permissions and field-level access

### 6.1 Role model

Keep the existing CMS `users.role` (`admin`/`editor`) for content. Add a **separate** `case_role`:

| `case_role` | Purpose |
|---|---|
| `intake` | Register/triage new cases; limited fields |
| `officer` | Investigate assigned cases; add evidence, notes, status changes |
| `manager` | See all cases, assign, close, export (reason required) |
| `auditor` | Read-only aggregate/reporting; no PII, no evidence |
| `null` | No case access (default; includes CMS editors) |

`is_admin` should not automatically grant case access. A CMS administrator who needs case access gets an explicit `case_role = manager`. This is deliberate: CMS access and child-data access are different privileges.

If the organisation grows, replace the enum with `roles` / `permissions` tables and Laravel Gates. For the current size, a `ReportPolicy` + `case_role` is enough.

### 6.2 Capability matrix

| Capability | intake | officer | manager | auditor | CMS editor | super admin |
|---|---|---|---|---|---|---|
| See queue | limited | assigned + unassigned | all | aggregates only | no | no (unless case_role) |
| View identity (child/reporter) | no | assigned | all | no | no | no (unless case_role) |
| View description | triage summary | assigned | all | no | no | no |
| View evidence list | no | assigned | all | no | no | no |
| Download evidence | no | assigned (logged) | all (logged) | no | no | no |
| Add internal note | yes | assigned | all | no | no | no |
| Change status | triage only | assigned | all | no | no | no |
| Assign / reassign | no | no | yes | no | no | no |
| Close / reopen | no | request | yes | no | no | no |
| Export case data | no | no | yes + reason | no | no | no |
| Export aggregate stats | no | no | yes | yes (PII-free) | no | no |
| Delete case | no | no | no | no | no | yes + reason + legal-hold check |
| Manage `case_role` | no | no | no | no | no | yes |

### 6.3 Enforcement points

1. **Route middleware**: a `CaseAccess` middleware for `/admin/cases/*` that rejects users without a `case_role`.
2. **Policy**: `ReportPolicy` on every controller action (`viewAny`, `view`, `viewIdentity`, `viewEvidence`, `downloadEvidence`, `addNote`, `changeStatus`, `assign`, `export`, `delete`).
3. **Query scope**: officers receive `->where('assigned_to', Auth::id())`; do not rely on the UI to hide rows.
4. **Field redaction**: a `ReportPresenter`/resource that removes identity fields unless `viewIdentity` passes. Never send raw encrypted values to the browser for unauthorized roles.
5. **Re-authentication**: password (and 2FA) before export, bulk download, role changes, and case deletion.
6. **View logging**: log the detail page open, identity reveal, evidence list and every download — not just writes.

### 6.4 Account security

- Require TOTP 2FA for `manager`, `officer`, `auditor` and super-admin accounts (add `laravel/fortify` or `pragmarx/google2fa`).
- Case-area session lifetime: 30 minutes idle, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SameSite=Lax`.
- Login throttling already exists (`AuthController`); add alerting on repeated failures and on logins from new devices/IPs.
- Offboarding: disable account, revoke sessions, reassign open cases. Document the checklist.
- Optional: IP allow-list/VPN for the case area if staff work from fixed offices.

---

## 7. Privacy and data-protection controls

These are requirements, not nice-to-haves. Confirm the retention periods and legal basis with NCC legal counsel and the relevant data-protection authority before launch.

### 7.1 Data minimisation

- Only collect what is needed to triage and investigate.
- Make child name and reporter identity genuinely optional (already optional in the UI) and explain why.
- Do not add national ID, exact address, school name, or photos of the reporter.
- Add a short privacy notice beside the form: what is collected, who sees it, how long it is kept, how to request access/correction, and the 116 hotline.

### 7.2 Encryption and key management

- Encrypt names, contact details, description and internal notes at rest (Laravel `encrypted` cast or a dedicated key).
- Encrypt database backups and restrict who can restore them.
- Store the encryption key outside the repository; document escrow and rotation. Rotating `APP_KEY` without re-encrypting makes all PII unreadable.
- TLS everywhere; HSTS; secure cookies.

### 7.3 Evidence protections

- Private disk only; no `storage:link` exposure.
- Random filenames; no directory names derived from the reference.
- Content-based validation, size/count caps, malware scan, EXIF/GPS stripping.
- Quarantine until clean; delete infected files and record the event.
- Serve as attachment with `nosniff` and a sandbox CSP.
- Never include evidence in emails, exports or public pages by default.

### 7.4 Logging and error handling

- `APP_DEBUG=false` in production; `LOG_LEVEL=warning` or `info`.
- Do not log request bodies, PINs, references, names or contacts. Add a redaction processor / custom exception handler.
- Disable or strictly protect debug tools (Telescope, Ignition) in production.
- Audit logs must not contain the PII itself; log IDs and actions.

### 7.5 Retention and deletion

- Define a retention schedule per data type, e.g. case record 7 years after closure, raw evidence 2 years after closure (or per legal advice), access logs 7 years.
- `retention_delete_at` on each case; a nightly job purges or anonymises.
- Anonymise rather than delete where aggregate history is needed: null names/contact/description, keep district/type/age band/dates.
- `legal_hold` blocks automatic purge.
- Provide a documented subject-access/correction/erasure workflow. Child-protection records may have a legal obligation to retain; that must be assessed case by case.
- Purge old backups and `site_contents` revisions that contain legacy PII.

### 7.6 Abuse and enumeration resistance

- High-entropy references + hashed PINs.
- Per-reference and per-IP rate limits; exponential backoff; temporary lock.
- Generic errors for not-found vs wrong-PIN.
- Honeypot + optional CAPTCHA on complaint submission.
- Idempotency to prevent duplicate cases from retries.
- No case data in URL query strings or browser history; use POST + session grant.
- `noindex, nofollow` and `Cache-Control: no-store` on tracking/evidence pages.
- Consider a WAF or CDN rate limiting in front of the public forms.

### 7.7 Sharing and exports

- Exports are manager-only, require a reason, are logged, and are watermarked with the user and timestamp.
- Default export excludes evidence files and internal notes unless explicitly selected.
- Referral to another agency requires a documented legal basis/consent; record it in `report_referrals`.
- Never send case details in SMS/email; send only "your case status changed — log in with your reference and PIN".

---

## 8. Admin experience

### 8.1 Separate "Cases" area

Add `/admin/cases` with its own navigation entry, not the generic CMS section list. Suggested screens:

1. **Case queue** — filters: status, priority, violation type, district, assigned officer, date range, overdue. Search by reference only (never by child name). Counters per status.
2. **Case detail** — header (reference, status, priority, received, SLA), identity panel (revealed only with permission and logged), description, timeline, internal notes, evidence panel, assignment, actions.
3. **Evidence viewer** — list with scan status, size, type, uploader (reporter/officer), download button. No inline rendering of untrusted PDFs/HTML.
4. **Case audit trail** — every view/download/change from `report_access_logs`.
5. **Stats dashboard** — operational metrics, caseload by officer (manager only), SLA breaches.

### 8.2 Workflow

```
received → triage → investigation → referred → resolved → closed
                ↘ duplicate / withdrawn
```

- On receipt: auto-priority (e.g. age < 5, abuse/child marriage → urgent), auto-acknowledgement.
- Triage: verify jurisdiction, set priority, assign officer.
- Investigation: notes, evidence, referrals, status updates.
- Closure: outcome category + reason; sets `closed_at` and `retention_delete_at`.
- Reopen within a configurable window (manager only).
- SLA timers and an overdue job; escalate to manager.
- Notify the reporter only on public status changes, and only if they opted in.

### 8.3 Remove reports from the CMS editor

- Remove `submission` from the `reporting` section types (or keep legacy read-only).
- Stop showing reporting submissions in `admin.contents.index`; link to the new case queue instead.
- Ensure `ContentController` cannot edit/delete case rows.
- Keep `site_contents` for the reporting page hero, instructions, stats copy and CTAs.

---

## 9. Routes (proposed)

### Public

```text
GET  /reporting                          reporting page
POST /reporting                          reporting.store        throttle:report-submit
POST /reporting/verify                   reporting.verify       throttle:report-verify
GET  /reporting/track                    reporting.track        (after verify)
POST /reporting/evidence                 reporting.evidence     throttle:report-evidence
GET  /reporting/statistics               reporting.statistics   throttle + cache
```

### Admin (prefix `/admin/cases`, name `admin.cases.`)

```text
GET    /                                 cases.index
GET    /{report}                         cases.show
GET    /{report}/audit                   cases.audit
POST   /{report}/assign                  cases.assign
POST   /{report}/status                  cases.status
POST   /{report}/notes                   cases.notes.store
GET    /{report}/evidence/{evidence}     cases.evidence.show   (stream/signed)
POST   /{report}/export                  cases.export
DELETE /{report}                         cases.destroy
GET    /statistics                       cases.statistics
GET    /export                           cases.export.aggregate
```

All admin routes: `auth` + `CaseAccess` middleware + policy checks + re-auth for export/delete.

---

## 10. Migration of existing data

The current public evidence URLs and `site_contents` PII are already exposed. Plan the migration as a security task, not just a data copy.

1. **Freeze** the old flow at deploy time (feature flag).
2. Backfill each `section=reporting, type=submission` row into `reports`:
   - Keep the old reference as `legacy_reference`; generate a new reference + PIN only if you can contact the reporter.
   - Move PII into encrypted columns; keep district/type/age/dates queryable.
   - Copy evidence files from `storage/app/public/evidence/...` to the private disk, generate new random names, compute SHA-256, create `report_evidence` rows.
   - Delete the original public files and their `MediaAsset` rows after verification.
3. Decide how legacy reporters can track:
   - If the data is demo/test data: discard it.
   - If real: allow a limited legacy lookup (reference + incident date + district) with strict rate limits, then require staff verification before evidence access. Do **not** silently leave the old public URLs alive.
4. Purge old `content_revisions` and `content_audits` that contain the PII, and old backups.
5. Invalidate caches/CDN entries for `/storage/evidence/*`.
6. Keep the old table read-only until the backfill is verified, then remove the submission rows.

---

## 11. Implementation phases

| Phase | Work | Output |
|---|---|---|
| 0. Decisions & DPIA | Retention periods, legal basis, role list, hosting/scanning constraints, whether legacy data is real | Signed-off policy + role matrix |
| 1. Foundation | Migrations, `Report` models, encrypted casts, `report_evidence` disk, config, factories | Schema + models + tests |
| 2. Public backend | Create/verify/track/evidence services, FormRequests, rate limiters, idempotency, PIN flow | Public forms fully wired |
| 3. Admin case management | Case queue, detail, assignment, status workflow, notes, timeline, audit log, evidence streaming | Staff can handle cases |
| 4. Privacy hardening | Malware scan, EXIF strip, private storage, retention/erasure jobs, notification queue, headers/logging | Data-protection controls live |
| 5. Statistics | Aggregation service, small-cell suppression, cache, chart wiring | Real stats |
| 6. Migration & launch | Backfill, revoke old URLs, purge PII, UAT, pen-test, staff training, monitoring | Go-live |

Rough effort for one experienced Laravel developer: **3–5 weeks** for phases 1–5, plus legal/policy time and a security review. Do not compress phase 4 or the pen-test.

---

## 12. Testing requirements

### Feature / security tests

- Complaint: validation, reference uniqueness, PIN hashing, idempotent double-submit, honeypot, rate limit, mass-assignment.
- Tracking: correct PIN, wrong PIN, non-existent reference, lockout, no existence leak, no PII in response.
- Evidence: content-type validation, size/count caps, private-disk storage, scan gate, EXIF strip, signed URL expiry, direct `/storage/...` access denied.
- RBAC matrix: every role × every capability; officer A cannot view officer B's case (IDOR); auditor cannot see PII; CMS editor cannot access cases.
- Field redaction in HTML and JSON.
- Audit log written on view, identity reveal, download, status change, export and delete.
- Retention/erasure job; legal hold blocks deletion.
- Statistics suppression for small buckets.
- XSS payload in description is escaped in admin and tracking pages.
- CSRF on all POSTs; secure headers.

### Manual / operational

- OWASP ASVS checklist and a third-party penetration test before launch.
- Restore-from-backup drill, including verifying that backups are encrypted and PII-free after retention.
- Load test the complaint form and evidence upload.
- Staff tabletop exercise for a data-subject request and a suspected breach.

---

## 13. Configuration additions (proposed)

```dotenv
REPORT_EVIDENCE_DISK=report_evidence
REPORT_EVIDENCE_MAX_FILES=5
REPORT_EVIDENCE_MAX_TOTAL_MB=100
REPORT_PIN_MAX_ATTEMPTS=5
REPORT_PIN_LOCK_MINUTES=15
REPORT_TRACK_GRANT_MINUTES=15
REPORT_SIGNED_URL_TTL=5
REPORT_RETENTION_YEARS=7
REPORT_EVIDENCE_RETENTION_YEARS=2
REPORT_STATS_MIN_CELL=5
REPORT_CAPTCHA_ENABLED=false
REPORT_NOTIFICATIONS_ENABLED=false
```

Also set in production: `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, a real mailer and queue worker.

---

## 14. Open decisions (please confirm)

1. **Legal retention**: how long must closed case records and evidence be kept? Is anonymisation acceptable, or is deletion required?
2. **Legacy data**: are the existing `site_contents` submissions real cases or demo/test data? This determines the backfill and PIN strategy.
3. **Staff roles**: who are the intake officers, investigators, managers and auditors? How many, and are they district-based?
4. **Assignment model**: should officers see unassigned cases in their district, or only cases explicitly assigned to them?
5. **2FA**: can all case staff use a TOTP app on a phone? If not, what compensating controls are possible?
6. **Hosting**: is this shared hosting or a VPS with shell access? Can we install ClamAV, ImageMagick and run a queue worker/cron?
7. **Notifications**: do we have an SMS gateway budget, or is email/116-only acceptable?
8. **Public stats**: is district-level breakdown acceptable, or should public stats stay at national/violation-type level to reduce re-identification risk?
9. **Anonymous reporting**: must it remain fully anonymous (no contact required, no recovery if PIN is lost)?
10. **Referrals**: which agencies receive cases, and what is the legal basis/consent wording for sharing?

---

## 15. Immediate next step

Before writing code, confirm the open decisions above and produce a one-page data-protection impact assessment covering: lawful basis, retention, access roles, evidence handling, and the breach-response contact. Then start Phase 1 (schema + private evidence disk), because every later phase depends on getting the data model and storage boundary right.
