# Case Management Backend

This document describes the reporting and case-management backend that replaces
the old `site_contents` submission flow. It covers roles, authentication, evidence
handling, retention, and the operational steps needed to run it safely.

## Data model

| Table | Purpose |
|---|---|
| `reports` | One row per case. Names, contact details, and the narrative are encrypted at rest. |
| `report_evidence` | Metadata for private evidence files. Files live on the `report_evidence` disk, never on a public URL. |
| `report_events` | Case timeline. Events marked `is_public` are shown to the reporter. |
| `report_notes` | Encrypted internal notes. Never shown to reporters. |
| `report_access_logs` | Append-only trail of views, identity access, downloads, status changes, assignments, notes, and anonymisation. IP and user-agent values are stored only as keyed hashes. |

The public tracking secret is a high-entropy reference (`NCC-YYYY-XXXXXX`, Crockford
base32) plus a hashed 6-digit PIN. A verified reporter receives a 15-minute session
grant for evidence upload and download. Repeated wrong PINs lock the case.

## Roles and permissions

Access is role-based and stored in `roles`, `permissions`, `permission_role`, and
`role_user`. The old `users.is_admin`, `users.role`, and `users.case_role` columns
are retained only for migration and are no longer authoritative. A user can hold
several roles; permissions are combined and the widest case scope wins.

Built-in roles ship with the application:

| Role key | Purpose | Case scope | TOTP |
|---|---|---|---|
| `system_admin` | Manages users, roles, and website content | none | role does not force it |
| `cms_admin` | Full website content, media, newsletters, enquiries | none | no |
| `cms_editor` | Website sections and media | none | no |
| `case_intake` | Queue, identity, assignment, triage | all | yes |
| `case_officer` | Works only on assigned cases | assigned | yes |
| `case_manager` | Every case, assignment, all transitions, audit trail | all | yes |
| `case_auditor` | Redacted metadata and access trail | all | yes |

The system administrator deliberately has **no automatic case access**. To work on
cases, an administrator must also hold a case role. This keeps IT administration
separate from child data.

Rules are enforced by `App\Policies\ReportPolicy`, `User::caseScope()`, and
`Report::scopeVisibleTo`. Auditors see case metadata, timeline, evidence counts, and
the access trail, but not child or reporter identity, narrative, notes, or evidence
files.

### Custom roles

Administrators with `admin.roles.manage` can create roles at `/admin/access/roles`
without a code change. They choose:

- the permission set;
- the case scope (`none`, `assigned`, or `all`);
- whether the role can be assigned cases;
- whether TOTP is required.

Any role that grants a `cases.*` permission is forced to require TOTP and must have a
case scope other than `none`.

Permissions come from a code-owned catalog in `App\Support\AccessCatalog`. A
permission only enforces anything when application code checks that key, so a truly
new capability still needs a code release. Administrators compose new roles from the
existing catalog; they cannot invent permissions from the CMS.

Users are managed at `/admin/access/users` with `admin.users.manage`. Safeguards:

- a user cannot change their own role assignments;
- at least one active system administrator must remain;
- deactivating a user ends their sessions immediately;
- built-in roles and the protected `system_admin` role cannot be deleted.

Every role and user change is written to `admin_audit_logs` and shown at
`/admin/access/audit` to anyone with `admin.audit.view`.

## Authentication and TOTP

1. Staff sign in at `/admin/login`. Case-only users land on `/admin/cases`; CMS users
   land on the CMS dashboard; access-control-only users land on user management.
2. A user whose roles require TOTP and who has not confirmed it is sent to
   `/admin/cases/totp/setup`.
3. After confirming a 6-digit code, eight one-time recovery codes are shown once.
4. Every new session must pass `/admin/cases/totp/challenge` before protected admin
   routes open. The verification lasts `REPORT_TOTP_SESSION_MINUTES` (default 480).
5. Logging out invalidates the session, so the next login requires a fresh code.

TOTP secrets and recovery codes are encrypted casts on `users`. Recovery codes are
removed after use. A user administrator can reset a locked-out account from
`/admin/access/users`, which clears `two_factor_secret`, `two_factor_recovery_codes`,
and `two_factor_confirmed_at`.

### Route separation

| Area | Middleware | Who |
|---|---|---|
| `/admin` entry | `AdminMiddleware` | CMS, case, or access-control permission |
| `/admin/cases/*` | `CaseTotpMiddleware` | case permission + confirmed TOTP + recent challenge |
| `/admin/access/*` | `CaseTotpMiddleware` | the specific `admin.users.*`, `admin.roles.manage`, or `admin.audit.view` permission |
| CMS routes (`/admin/contents`, `/admin/media`, `/admin/newsletter`, enquiries) | `CmsMiddleware` | CMS permission only |

## Evidence

- Stored under `storage/app/report-evidence` through the private `report_evidence`
  disk (`REPORT_EVIDENCE_DISK`).
- No public URL exists. Downloads stream through an authorised controller with
  `X-Content-Type-Options: nosniff`, `Cache-Control: no-store, private`, and a
  sandbox CSP header.
- Allowed types: JPEG, PNG, MP3, MP4, PDF. Per-file, per-request, total-file, and
  total-size limits are configured in `config/reporting.php`.
- Malware scanning is intentionally `not_scanned` for now; wire a scanner into
  `ReportEvidence::scan_status` before launch if the hosting supports it.
- Staff uploads and reporter uploads are both logged in `report_access_logs`.

## Retention and erasure

Closed cases receive `retention_delete_at = closed_at + REPORT_RETENTION_YEARS`
(default 2 years). `legal_hold` always wins.

```bash
# Preview how many cases are due
php artisan reports:purge-expired --dry-run

# Anonymise everything that is due (scheduled daily at 02:15)
php artisan reports:purge-expired

# Honour a specific erasure request; --reason is required
php artisan reports:erase NCC-2026-A1B2C3 --reason="Subject erasure request" --force
```

Anonymisation:

- deletes evidence files and force-deletes their rows;
- deletes internal notes;
- clears child name, reporter name/contact, contact hash, and village;
- replaces the narrative with `[Anonymised under the retention policy]`;
- rotates the reporter PIN and clears lock state;
- keeps the de-identified row so national statistics and audit history survive;
- writes an `anonymised` entry to `report_access_logs` with the reason.

Run the scheduler in production (cron or `php artisan schedule:work`), otherwise
retention will not run.

## Public statistics

`App\Support\ReportStats` returns national aggregates only. Any cell below
`REPORT_STATS_MIN_CELL` (default 5) is suppressed before it reaches the view, and
resolution-rate/average figures are suppressed when their sample is too small.
District, village, reference, and individual data are never exposed.

## Legacy reporting removal

Migration `2026_11_03_000007_remove_legacy_reporting_submissions.php` deletes old
`site_contents` rows with `section = reporting` and `type = submission`, along with
their public media. `submission` was removed from the CMS reporting section type
list, so it can no longer be created or published from the CMS.

## Local development accounts

`DatabaseSeeder` runs `AccessControlSeeder` (permission catalog, built-in roles, and
legacy user mapping) and, outside production, `CaseStaffSeeder`. It creates one
confirmed TOTP account per case role and prints the TOTP secret to the console:

| Email | Password | Role |
|---|---|---|
| `admin@example.com` | `password` | `system_admin` (no case access) |
| `intake@example.com` | `password` | `case_intake` |
| `officer@example.com` | `password` | `case_officer` |
| `manager@example.com` | `password` | `case_manager` |
| `auditor@example.com` | `password` | `case_auditor` |

Use the printed secret in an authenticator app to complete the challenge. The
seeder never runs when `APP_ENV=production`.

To create a hybrid account, sign in as the system administrator, open
`/admin/access/users`, and assign both a CMS role and a case role. Custom roles can
be created at `/admin/access/roles`.

## Production checklist

- Set a strong `APP_KEY` and escrow it. Rotating `APP_KEY` makes all encrypted case
  PII unreadable.
- Set `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, and a
  real mailer/queue worker if notifications are added later.
- Run `php artisan migrate --force` and confirm the `report_evidence` directory is
  writable but not web-accessible.
- Run `php artisan db:seed --class=AccessControlSeeder --force` so the permission
  catalog and built-in roles exist. Then create the first system administrator and
  assign the `system_admin` role before disabling legacy login paths.
- Review every role with NCC management. In particular, confirm who holds
  `system_admin`, who holds case roles, and whether any custom role has
  `admin.roles.manage` or `cases.identity.view`.
- Remember that adding a permission to the catalog is a code change. New roles can
  combine existing permissions immediately, but a new capability must be implemented
  and checked in code first.
- Run `php artisan schedule:work` or add the Laravel scheduler to cron.
- Confirm backups are encrypted and that restored copies respect retention.
- Review the retention period and erasure workflow with NCC legal counsel before
  go-live.
