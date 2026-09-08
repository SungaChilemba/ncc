# NCC Website CMS

Laravel-powered public website and CMS for managing NCC page content, news, resources, media, and publishing workflows.

## Local Setup

```bash
composer install
npm install
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

If using the bundled portable tooling in this workspace, call PHP through `.local-tools/php/php.exe` when the shell has not refreshed `PATH`.

Default seeded CMS account:

- Email: `admin@example.com`
- Password: `password`

Change this password before any shared or production deployment.

## CMS Capabilities

- Section-based content editing for home, about, what we do, children's corner, reporting, resources, news, donate, and advertise.
- Detail pages for CMS news articles and resources.
- Resource downloads through named application routes.
- Media library for reusable images and documents.
- Editor/admin role support with the legacy `is_admin` flag preserved.
- Draft preview, public preview, publish/unpublish, bulk publishing actions, quick sort ordering, and audit trail.
- Structured JSON fields for repeatable content such as FAQs, profile rows, stats, timelines, and allocation lists.

## Content Model

The flexible CMS record is `site_contents`. Main fields:

- `section`: page key, constrained by `config/cms.php`
- `type`: content type, constrained per section
- `slug`: public detail URL for news/resources
- `title`, `subtitle`, `description`: primary editorial fields
- `category`, `location`, `published_on`: public metadata
- `image_path`, `file_path`, `media_asset_id`, `alt_text`: media/document fields
- `data`: optional structured JSON for repeatable blocks
- `is_published`, `published_at`, `preview_token`: publishing workflow

## Verification

Run the backend and frontend checks before shipping changes:

```bash
php artisan test
npm run build
```

Useful live smoke checks:

- `/`
- `/about`
- `/what-we-do`
- `/childrens-corner`
- `/reporting`
- `/resources`
- `/resources/ncc-act`
- `/resources/ncc-act/download`
- `/news`
- `/news/stronger-action-against-child-labour`
- `/admin`

## Deployment Readiness

Before production launch:

- Set production `.env` values: `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`, mail, cache, session, database, and filesystem settings.
- Generate and secure `APP_KEY`.
- Run `composer install --no-dev --optimize-autoloader`.
- Run `npm run build` and deploy `public/build`.
- Run `php artisan migrate --force`.
- Run `php artisan storage:link` and verify public uploads/documents are readable.
- Configure HTTPS, web server document root to `/public`, upload limits for 10 MB documents, and correct file permissions for `storage` and `bootstrap/cache`.
- Replace the seeded admin password and create named editor/admin accounts.
- Configure database and uploaded-media backups.
- Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache` after environment is final.
