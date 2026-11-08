# Section/Type-Aware CMS Editor — Implementation Plan

Status: **Proposed — no code changes made.**
Scope: `config/cms.php`, `app/Http/Controllers/Admin/ContentController.php`,
`resources/views/admin/contents/editor.blade.php`, plus adjacent admin views and tests.
Goal: make the CMS editor show only the fields each section/type actually uses on the
public site, give every field a plain-English label, and replace free-text inputs with
selects/datalists wherever the valid values are known.

---

## 1. Why this change

The editor is currently one shared form for all nine sections and nine types. Every field
is always visible, labels are generic, and several inputs write data that the matching
frontend page never reads. At the same time, some data the frontend *does* read (for
example `data.bullets`, `data.placements`, `data.price`, `data.ends_at`) can only be
edited through the "Advanced JSON" textarea.

The result is three concrete problems:

1. Editors cannot tell which fields matter for the item they are editing.
2. Some editor values silently never appear on the page.
3. Some page-controlling values are effectively hidden behind JSON.

This plan fixes those without changing the content model, autosave, preview, media,
document, publishing or revision behaviour.

### Visibility rule (non-negotiable)

An input is rendered **only** when the selected `section + type + category` actually
reads that value on the matching public page. There are no "just in case" fields.

- Fields with no consumer are **hidden**, not merely disabled or annotated.
- A field that only matters for one category is hidden until that category is selected.
- Types the page never renders are **removed from the section's type list entirely**.
  Existing rows of those types are cleaned up as part of this work, because the CMS
  content is test data and does not need preserving.
- Advanced JSON is collapsed behind a disclosure and described as an escape hatch, not
  part of the normal workflow.

> **Data status:** all current CMS content is test data. There is no production content
> to migrate, so the plan does not need `legacy_types`, dual-read paths, or a staged
> audit before removing dead types. Existing test rows for retired types will be deleted
> (or reseeded) as part of the change.

This rule outranks convenience: if a field is genuinely useful later, the frontend must
read it first, then the field is added back for that section/type.

### Non-goals

- No database migration (all new authoring values live in the existing `data` JSON column).
- No change to public route names or page URLs.
- No change to the enquiry workflow or `AdEnquiries` status rules.
- No redesign of the public pages beyond optional, backward-compatible reads of new
  structured keys.

---

## 1.1 Management flow — one frontend page, one clear path

The mental model should be: **choose a page → choose an item (or a content type) → fill
only the fields that page uses → save → view it on the page.** No CMS concept should
appear that does not map to something a visitor sees.

### Step 1 — Navigate by frontend page

- Sidebar: replace the flat list of nine links with a **"Website pages"** group whose
  order matches the public navigation: Home, About, What We Do, Children's Corner,
  Reporting, Resources, News, Donate, Advertise.
- Each link opens that page's content list. The active page is already highlighted
  (`admin/layouts/app.blade.php:53-57`); keep that and add the page label to the header.
- Keep Media Library and Newsletter outside the group, since they are shared tools, not
  pages.

### Step 2 — Page list makes the page's building blocks obvious

On `admin/contents/index.blade.php`:

- Header: page name + one-line "What this page contains" summary from config.
- **"View page"** link beside the title, so editors can always see the real result.
- **"Add new" becomes a type chooser**, not a blind form. Show only types the page
  actually renders (one entry per supported block, with a one-line description, e.g.
  "Resource document — a downloadable PDF listed under Resources"). Types the page does
  not render are not in the list at all.
- Type filter stays, but is populated from the same visible-type list.
- Add a **category filter** only for pages with a fixed category list (Resources,
  Donate, About, What We Do, Reporting, Advertise).
- Reordering stays, but the "Order" column is explained as "position on the page".

### Step 3 — Editor follows a predictable top-to-bottom story

Keep the single form and the existing sidebar summary. Group fields into three
collapsible, numbered panels so the order is always the same:

1. **What this is** — content type (read-only badge once chosen, with a "Change type"
   control), title, category, permalink.
2. **What visitors see** — only the schema-resolved content blocks for this
   section/type/category: body, subtitle, icon/role, bullets, image/alt text, document,
   buttons, repeaters, and type-specific meta (price, placements, video, allocation,
   source, biography, expiry).
3. **Where and when it appears** — display order, publish status/schedule, and the
   type-specific visibility toggles (news placement, featured advert).

Then, separated and collapsed: **Developer options → Advanced JSON**, with help text
saying it is only for fields not covered above and that structured fields win.

- A short contextual banner sits above the blocks: "Showing fields used by **Resource
  document** on the **Resources** page." It updates when type/category changes.
- Changing type or category recomputes blocks immediately; a "Change type" action warns
  that fields belonging to the old type will be hidden (their data is preserved until
  saved).
- Sidebar keeps Publish, Autosave status, Preview Draft, Save/Cancel. Draft/view links
  stay visible at the top.
- On save: redirect back to the page list with a success message that includes
  **"View on page"** and **"Preview draft"** links.

### Step 4 — Consistent vocabulary and affordances

- One label per concept everywhere (`Title`, `Body`, `Category`, `Button label`,
  `Button link`, `Image description`) plus per-type overrides only where the page's
  meaning differs (e.g. Children's `subtitle` is an icon class).
- Dropdowns/datalists from the schema, never a free-text field for a known value.
- Required fields marked with `*` and explained once at the top of the panel.
- Every non-obvious field has one short help line, not a paragraph.
- Empty list states say what to do next ("No items yet — add the first hero").

### Step 5 — Index and preview reinforce the same model

- Index shows only columns that matter for that page (e.g. hide Category on Home,
  hide Order for News).
- Preview labels the item's type and page, not raw data keys.
- A dashboard "Page coverage" card lists each page with its published item count and a
  direct "Manage" link, so editors can start from the dashboard too.

---

## 2. Current data flow

### Read path (public site)

```
PageController
  -> CmsContent (scopePublished + ordered)
    -> SiteContent model
      -> pages/*.blade.php and partials/*.blade.php
```

- `PageController::show()` passes `cmsHeroes`, `cmsCards`, `cmsCtas`, `cmsNews`,
  `cmsResources`, `cmsGallery` (`app/Http/Controllers/PageController.php:143-160` approx.).
- `advertise()` additionally passes `heroAds`, `heroStat`, `sponsoredAds`
  (`PageController.php:76-83`).
- `latestNews()` builds slides/featured/categories directly from published `news` items
  (`PageController.php:238-254`).
- `SiteContent::public_url` builds detail URLs for `news` and `resources`
  (`app/Models/SiteContent.php:125-138`).
- `SiteContent::button_link` normalises `button_url` for most public blocks
  (`SiteContent.php:120-123`).

### Write path (admin)

```
GET  admin/contents/{section}/create|{id}/edit
  -> ContentController::create/edit
    -> admin/contents/editor.blade.php
       (selectedType, structured items, bio sections, panels, autosave JS)

POST admin/contents/{section}
  -> ContentController::store/update
    -> validate (shared rules)
    -> preparePublishingState()
    -> prepareContentData()
       -> structuredDataFromFields() (source/items/bio)
       -> merge data_json + structured fields
    -> SiteContent::create/update
    -> recordRevision() + audit()
```

Key facts that constrain the design:

- `editor.blade.php` is a **single form**; visibility is already driven by
  `data-type-panel` attributes and `updateTypePanels()` (`editor.blade.php:114-158`,
  `:341-351`).
- The current selected type is computed in Blade from old input, the editing row, or
  `?type=` (`editor.blade.php:5`).
- Category validation is only strict for `resources` + `resource` and `donate` + `card`
  (`ContentController.php:51-55`, `:147-151`); every other section accepts free text.
- `data_json` is decoded, then `array_replace_recursive` merges structured form fields
  over it (`ContentController.php:536-577`).
- Revisions snapshot the fixed column list including `data`; restore uses the same list
  (`ContentController.php:366-388`, `:637-665`).
- `submission` is deliberately excluded from publishing and from `reporting.types`
  (`config/cms.php:73-79`, `ContentController.php:418-420`, `AdminContentTest.php:464-510`).

---

## 3. Section/type → frontend field map

Legend: **Y** = rendered by the page, **—** = not rendered, **~** = rendered only in a
specific category/fallback.

### 3.1 Home — `resources/views/pages/home.blade.php`

| Type | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle` (eyebrow), `description`, `image_path`, `button_text`, `button_url` | `alt_text`, `slug`, `category`, `location`, `published_on`, `document`, `accent_color`, items |
| `update` | `title`, `description`, `image_path`, `accent_color`, `button_text`, `button_url`/`button_link`, `slug` (fallback `news.show` link) | `subtitle`, `category`, `location`, `published_on`, `alt_text`, items |
| `news` | `title`, `slug`, `image_path`, `alt_text` | `description`, `subtitle`, `category`, `location`, `published_on`, buttons, items |
| `stat` | **none** — stats are hard-coded at `home.blade.php:490-507` | everything |
| `cta` | **none** — home never reads `$cmsCtas` | everything |

### 3.2 About — `resources/views/pages/about.blade.php`

| Type / category | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle`, `description`, `image_path` | `button_text`, `button_url`, `alt_text`, `slug` |
| `card` — story tabs (`Mandate, Mission & Objectives` / `Mandate, Mission & Vision`) | `slug` (`our-mandate`, `our-mission`, `our-objectives`/`our-vision`), `category`, `title`, `description` (fallback), `data.bullets`, `image_path` | `subtitle`, `alt_text`, buttons, items |
| `card` — `Commissioners`, `Ex-Officials`, `Management` | `category`, `title`, `subtitle` (role), `image_path`, `alt_text`, `data.bio_sections`, `description` (fallback) | `slug`, buttons, items |
| `card` — `Organogram` | Removed from the page for now; will return later with a dedicated dynamic renderer | everything |
| `card` — other categories | `category`, `title`, `subtitle`, `description`, `image_path`, `button_text`, `button_link`, `accent_color` (via `cms-public-blocks`) | items are not rendered by `cms-public-blocks` |
| `stat` | `title`, `description` | `accent_color`, `image_path` |
| `cta` | **none** — About never reads `$cmsCtas` | everything |

The three people tabs share one card component
(`resources/views/components/about-person-card.blade.php`), so they render the same
image, title, role (`subtitle`), biography sections, and biography modal. Management now
uses the `Management` category; `Organogram` is no longer rendered until a new dynamic
layout is designed.

### 3.3 What We Do — `resources/views/pages/what-we-do.blade.php` → `partials/cms-section-page.blade.php`

| Type | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle`, `description`, `image_path`, `alt_text` (first hero only) | `button_text`, `button_url`, `slug` |
| `card` | `category` (group heading), `subtitle`, `title`, `description`, `image_path`, `data.items`, `button_text`, `button_link` | `alt_text`, `accent_color` |
| `stat` | `title`, `description` | `accent_color`, image |
| `cta` | `title`, `description`, `button_text`, `button_link` | image, `accent_color` |

Categories seeded: `How We Work`, `Our Child, Our Responsibility`, `Our Programs`.

### 3.4 Children's Corner — `resources/views/pages/childrens-corner.blade.php`

| Type | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `description`, `image_path`, `alt_text` | `subtitle`, buttons, `slug` |
| `card` — `Activities` | `category`, `title`, `subtitle` (Font Awesome icon class) | `description`, image, buttons, items |
| `card` — `Child Rights` | `category`, `title`, `subtitle` (icon), `description` | image, buttons, items |
| `gallery` | `image_path`, `alt_text` (fallback `title`) | `description`, category, buttons |
| `news` | `title`, `slug`, `image_path`, `alt_text`, `published_on`, `location`, `category`, `description`, `button_text` (read-more label) | `button_url` (route is forced to `news.show`) |
| `resource` | `category`, `title`, `description`, `button_text`, `button_link`/`public_url` | `image_path`, `alt_text`, `document` |
| `cta` | `title`, `description`, `button_text`, `button_link` (first only) | image, `accent_color` |

**Gap:** `resource` is consumed but is **not** in
`config('cms.sections.childrens-corner.types')` (`config/cms.php:67-72`), so editors
cannot create one.

### 3.5 Reporting — `resources/views/pages/reporting.blade.php`

| Type / category | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle`, `description` | `image_path`, `alt_text`, buttons, `slug` |
| `card` — `Reporting Workflow` | `category`, `title`, `description` | `subtitle`, image, buttons, items, `accent_color` |
| `card` — other categories | `category`, `title`, `subtitle`, `description`, `image_path`, `button_text`, `button_url`, `accent_color` (via `cms-public-blocks`) | items |
| `stat` | **none** — Reporting stats are computed case statistics, not CMS stats | everything |
| `cta` | **none** — Reporting never reads `$cmsCtas` | everything |

Categories seeded: `Reporting Workflow`.

### 3.6 Resources — `resources/views/pages/resources.blade.php` + `resource-detail.blade.php`

| Type | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `description` | `subtitle`, image, buttons, `slug` |
| `resource` | `title`, `slug`, `description`, `category` (must slug-match the static filter keys), `file_path` (document), `data.size`, `data.pages`, `button_text` (download label), `updated_at` | `button_url` (download route is used), `image_path`, `alt_text`, `accent_color` |
| `card` | **none** — Resources never reads `$cmsCards` | everything |

**Gap:** category values accepted by validation (`config('cms.resource_categories')`) do
match the static filters after `Str::slug`, so the dropdown can stay strict.

### 3.7 News — `resources/views/pages/latest-news.blade.php` + `news.blade.php`

| Type | Consumed fields | Not consumed |
|---|---|---|
| `news` | `title`, `slug`, `category`, `location`, `published_on`, `description`, `image_path`, `alt_text`, `data.source.{name,url,posted_on,visible_excerpt}`, `data.placements` (`hero`, `featured`; also `data.is_featured`/`data.featured` fallbacks) | `subtitle`, `button_text`, `button_url`, `document`, `accent_color` |
| `hero` | **none** — `latestNews()` builds hero slides from `news` rows | everything |
| `card` | **none** | everything |

Categories seeded: `News`, `Monitoring`, `Child Protection`, `Policy`,
`Community Events`, `Partnerships`, `Child Participation`.

### 3.8 Donate — `resources/views/pages/donate.blade.php` → `partials/cms-section-page.blade.php`

| Type / category | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle`, `description`, `image_path`, `alt_text` | `button_text`/`button_url` (seeded but not rendered) |
| `card` — `Even a little Donation makes a big Difference` | `image_path`, `alt_text` (fallback `title`) | `description`, buttons, items |
| `card` — `Where will your donation go` | `category`, `description` (percentages parsed by regex) | title, image, buttons, items |
| `card` — `Watch Our Story` | `category`, `description` or `data.youtube_url` / `data.video_url` | title, image, buttons, items |
| `card` — `Thanks to our supporters` | `category`, `title`, `description` | image, buttons, items |
| `card` — `FAQs ...` | `category` (group heading), `title`, `description` | image, buttons, items |
| `card` — `Giving Options` / other | `category`, `subtitle`, `title`, `description`, `image_path`, `data.items`, `button_text`, `button_link` | `alt_text`, `accent_color` |
| `stat` | `title`, `description` | `accent_color`, image |
| `cta` | `title`, `description`, `button_text`, `button_link` | image, `accent_color` |

**Gap (bug):** the seeded categories `Giving Options`, `Watch Our Story` (capital O), and
`FAQs about donating to National Children's Commission` are **not** in
`config('cms.donate_categories')` (`config/cms.php:32-37`, `SiteContentSeeder.php:718-743`).
Because validation uses `Rule::in(...)` for `donate` + `card`, editing a seeded donation
card currently fails validation.

### 3.9 Advertise — `resources/views/pages/advertise.blade.php`

| Type / category | Consumed fields | Not consumed |
|---|---|---|
| `hero` | `title`, `subtitle`, `description`, `image_path`, `button_text`, `button_url` | `alt_text` (uses title), `slug`, `category` |
| `card` — `Opportunities` / `Sponsorship Opportunities` | `category`, `title`, `subtitle`, `image_path`, `data.price`, `data.badge`, `data.reach`, `data.placements` (array), `data.includes` (array), `button_text`, `button_link` | `description`, items |
| `card` — `Sponsored Ads` | `category`, `title`, `description`, `image_path`, `button_link`, `data.ends_at`, `data.is_featured` | `subtitle`, `accent_color` |
| `card` — `Partnership` / `Why Partner` / `Guidelines` | `category`, `title`, `subtitle`, `description`, `button_text`, `button_link` | `accent_color` |
| `stat` | `title`, `description`, `data.icon` (Font Awesome class) — only when title/description contains "partner" | `accent_color`, image |
| `cta` | `title`, `description`, `button_text`, `button_link` | image, `accent_color` |
| `submission` — `Ad Inquiries` | `data.status` (`pending_review`/`approved`/`rejected`), `data.company`, `data.ends_at`; drives the enquiry board and partner count | publishing fields (always private) |

Categories seeded: `Opportunities`, `Sponsored Ads` (supported by query, not seeded),
plus the view supports `Guidelines`, `Why Partner`, `Partnership`, and a fallback group.

---

## 4. Relevance ruling per type

Before hiding a type, the test is: **does this page display this kind of content at all?**
If yes, the type is relevant and must either be rendered or hidden until it is wired. If
no, it is dead weight and must not be offered.

| Section | Type | Rendered today? | Verdict |
|---|---|---|---|
| Home | `hero`, `update`, `news` | Yes | Keep. |
| Home | `stat` | No — the page hard-codes four impact counters (`home.blade.php:489-507`) | **Relevant concept, not wired.** Hide from Add New now; optional Phase 4: map the four counters to `stat` rows. |
| Home | `cta` | No — the page has two fixed action buttons, not a CMS CTA band | Not relevant. Retire. |
| About | `hero`, `card`, `stat` | Yes — `stat` renders at `about.blade.php:366-373` | Keep. |
| About | `cta` | No | Not relevant. Retire. |
| What We Do | `hero`, `card`, `stat`, `cta` | Yes — all rendered by `cms-section-page` | Keep. |
| Children's Corner | `hero`, `card`, `gallery`, `news`, `cta` | Yes | Keep. |
| Children's Corner | `resource` | Yes — the view renders it, but the type is missing from config | **Add** to the type list. |
| Reporting | `hero`, `card` | Yes | Keep. |
| Reporting | `stat` | No — the page shows live case statistics, which CMS rows must not shadow | Not relevant. Retire. |
| Reporting | `cta` | No | Not relevant. Retire. |
| Resources | `hero`, `resource` | Yes | Keep. |
| Resources | `card` | No — the grid is built only from `resource` rows | Not relevant. Retire. |
| News | `news` | Yes | Keep. |
| News | `hero`, `card` | No — the hero and featured slots are derived from `news` placements | Not relevant. Retire. |
| Donate | `hero`, `card`, `stat`, `cta` | Yes — all rendered by `cms-section-page` | Keep. |
| Advertise | `hero`, `card`, `stat`, `cta`, `submission` | Yes | Keep. |

Consequences:

- The only type that is **relevant but not rendered** is Home `stat`. It is removed from
  the Home type list until the four counters are wired. Its test rows are deleted with
  the other retired types.
- Every other hidden type is genuinely **not relevant** to its page and is removed from
  that section.
- `submission` remains Advertise-only and stays unpublishable.
- Do not confuse a hidden type with a hidden field: even for kept types, only the fields
  that page reads are shown (the block lists in §5).
- Because there is no real content, removed types leave no migration burden: a one-off
  cleanup deletes rows whose type is no longer in their section's `types`, and the
  seeder is refreshed against the new lists.

### Editor fields with no frontend consumer

These are the inputs to stop showing (or clearly mark) per section/type.

| Input / field | Where it is currently shown | Reality |
|---|---|---|
| `stat` type | `home`, `reporting` | Page never renders CMS stats |
| `cta` type | `home`, `about`, `reporting` | Pages never read `$cmsCtas` |
| `card` type | `resources` | Resources page never reads `$cmsCards` |
| `hero`, `card` types | `news` | Latest News/News detail never use them |
| `subtitle` | almost every type | Only used by hero eyebrows, About roles, Children's icons, generic cards |
| `location` | every type | Only `news` renders it (latest news, detail, home cards, children's corner) |
| `published_on` | every type | Only `news` renders it; `resources` uses `updated_at` with `published_on` fallback |
| `accent_color` | every type | Only Home `update` and `cms-public-blocks` read it |
| `button_url` | every type | Ignored by Resources `resource`, Children's `news`, About story/commissioner/organogram, Home `news` |
| `image` | every type | Ignored by Resources hero/resource, Reporting hero, Donate FAQ/allocation/story/thanks, Children's activity/right cards |
| `document` | every type | Only Resources `resource` reads `file_path` |
| `media_asset_id` | every type | Only meaningful where an image or document is read |
| `hero_featured` | all Advertise types | Only matters for `card` + `Sponsored Ads` |
| `alt_text` | every type | Many views fall back to `title`; hidden where no image is rendered |

**Data keys consumed but only editable through Advanced JSON today:**

- About story cards: `data.bullets` (`about.blade.php:42-62`).
- News: `data.placements` (`CmsContent::heroNews`/`featuredNews`, `CmsContent.php:72-116`).
- Donate: `data.youtube_url`/`data.video_url` (`cms-section-page.blade.php:46-56`).
- Advertise tiers: `data.price`, `data.badge`, `data.reach`, `data.placements`,
  `data.includes` (`advertise.blade.php:223-267`).
- Advertise sponsored ads: `data.ends_at` (`CmsContent.php:170-180`).
- Advertise stat: `data.icon` (`PageController.php:99-104`).
- Advertise enquiries: `data.status`, `data.company`, `data.ends_at`
  (`SiteContent::enquiry_status`, `AdEnquiries`).
- Resources: `data.size`, `data.pages` (`resources.blade.php:28-29`).

---

## 5. Proposed configuration shape

Keep `cms.sections.{section}.types` as the single source of truth for allowed types
(controller/index already use it). Add editor metadata alongside it.

### 5.0 Final type inventory

`types` is the complete, authoritative list for each section: offered for new items,
accepted by validation, and the only types the editor can resolve.

| Section | `types` |
|---|---|
| `home` | `hero`, `update`, `news` |
| `about` | `hero`, `card`, `stat` |
| `what-we-do` | `hero`, `card`, `stat`, `cta` |
| `childrens-corner` | `hero`, `card`, `gallery`, `news`, `resource`, `cta` |
| `reporting` | `hero`, `card` |
| `resources` | `hero`, `resource` |
| `news` | `news` |
| `donate` | `hero`, `card`, `stat`, `cta` |
| `advertise` | `hero`, `card`, `stat`, `cta`, `submission` |

Controller rule: `type` is always `Rule::in($sectionConfig['types'])`. No legacy branch.

**One-off cleanup** (safe because the data is test data): delete any `site_contents`
row whose type is not in its section's new `types`, then refresh the seeder. Provide this
as a small artisan command (e.g. `cms:prune-invalid-types`) that reports counts and
supports `--dry-run`, so it is obvious what was removed rather than an opaque SQL
statement. Run it in local/staging before the final seed.

### 5.1 Block catalogue

Use logical **blocks** rather than raw column names, because one block can emit several
inputs and a help text:

| Block | Inputs |
|---|---|
| `identity` | `type`, `title` |
| `permalink` | `slug` |
| `subtitle` | `subtitle` |
| `body` | `description` |
| `category` | `category` |
| `location` | `location` (datalist) |
| `published_on` | `published_on` |
| `image` | `image`, existing preview/remove |
| `alt_text` | `alt_text` |
| `media_library` | `media_asset_id` |
| `document` | `document`, existing preview/remove |
| `button` | `button_text`, `button_url` |
| `button_label` | `button_text` only |
| `accent` | `accent_color` |
| `sort` | `sort_order` |
| `source` | `source_name`, `source_url`, `source_posted_on`, `source_excerpt` |
| `items` | `item_label[]`, `item_value[]` |
| `bio` | `bio_heading[]`, `bio_body[]` |
| `bullets` | `bullets[]` (new) |
| `placements` | `news_placement[]` → `data.placements` (new) |
| `tier` | `price`, `badge`, `reach`, `tier_placement[]`, `tier_include[]` (new) |
| `ad_expiry` | `ends_at`, `hero_featured` (new `ends_at`) |
| `stat_icon` | `stat_icon` → `data.icon` (new) |
| `submission_meta` | `enquiry_status`, `company`, `ends_at` (new) |
| `resource_meta` | `size`, `pages` (new) |
| `video` | `youtube_url` (new), help says description fallback |
| `allocation` | `allocation_direct`, `allocation_fundraising` (new) |
| `json` | `data_json` |
| `publish` | `publish_mode`, `publish_at` |

### 5.2 Section example

```php
// config/cms.php
'accent_colors' => [
    'red' => 'Red',          // currently missing, but the editor defaults to red
    'green' => 'Green',
    // ...
],

'donate_categories' => [
    'Giving Options' => 'Giving Options',
    'Even a little Donation makes a big Difference' => 'Even a little Donation makes a big Difference',
    'Where will your donation go' => 'Where will your donation go',
    'Watch Our Story' => 'Watch Our Story',
    'Thanks to our supporters' => 'Thanks to our supporters',
    "FAQs about donating to National Children's Commission" => "FAQs about donating to National Children's Commission",
],

'sections' => [
    'resources' => [
        'label' => 'Resources',
        'description' => '...',
        'route' => 'resources',
        'types' => ['hero', 'resource'],
        'type_options' => [
            'hero' => [
                'label' => 'Page header',
                'blocks' => ['identity', 'body', 'sort', 'json', 'publish'],
                'labels' => ['title' => 'Page heading', 'description' => 'Intro paragraph'],
            ],
            'resource' => [
                'label' => 'Resource document',
                'blocks' => [
                    'identity', 'permalink', 'category', 'body',
                    'document', 'resource_meta', 'button_label',
                    'sort', 'json', 'publish',
                ],
                'labels' => [
                    'title' => 'Document title',
                    'category' => 'Resource category',
                    'button_text' => 'Download button label',
                    'document' => 'Document file',
                ],
                'help' => [
                    'category' => 'Controls the public filter. Must match one of the listed categories.',
                    'button_text' => 'Defaults to "Download" when empty.',
                ],
                'category_options' => 'cms.resource_categories',
                'required' => ['title', 'category'],
            ],
        ],
    ],
],
```

### 5.3 Category options

Add explicit lists for every section that groups by category:

| Config key | Values |
|---|---|
| `resource_categories` | existing six |
| `donate_categories` | add the three missing seeded values, keep exact casing |
| `advertise_categories` | add `Guidelines`, `Why Partner With NCC`, `Partnership`; keep existing |
| `about_categories` | `Mandate, Mission & Objectives`, `Mandate, Mission & Vision`, `Commissioners`, `Ex-Officials`, `Management` |
| `what_we_do_categories` | `How We Work`, `Our Child, Our Responsibility`, `Our Programs` |
| `children_categories` | `Activities`, `Child Rights`, `Kids News & Updates` |
| `reporting_categories` | `Reporting Workflow` |
| `news_categories` | seeded values + `allow_custom => true` (use a datalist, not a hard rule) |

For news, About "other", and location, use `<input list="...">` so editors keep the
ability to add a new value, while the known values are suggested.

### 5.4 Category-conditional blocks

About and Donate vary by category, not only by type. Support
`category_blocks` on a type option:

```php
'card' => [
    'blocks' => ['identity', 'category', 'subtitle', 'body', 'image', 'button', 'accent', 'sort', 'json', 'publish'],
    'category_blocks' => [
        'Commissioners' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
        'Ex-Officials' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
        'Management' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
        'Mandate, Mission & Objectives' => ['identity', 'permalink', 'category', 'bullets', 'image', 'sort', 'json', 'publish'],
        'Mandate, Mission & Vision' => ['identity', 'permalink', 'category', 'bullets', 'image', 'sort', 'json', 'publish'],
    ],
],
```

When a category is selected/typed, the editor recomputes the visible blocks using the
same cascade: `section blocks → type blocks → category blocks`.

---

## 6. New support class

Add `app/Support/CmsEditorSchema.php` (no persistence) with a small, testable API:

```php
final class CmsEditorSchema
{
    public static function forSection(string $section): array;
    public static function for(string $section, string $type, ?string $category = null): array;
    public static function fieldsFor(string $section, string $type, ?string $category = null): array;
    public static function fieldMeta(string $field): array; // label, help, control, options
    public static function categoryOptions(string $section, string $type): ?array;
}
```

Responsibilities:

1. Validate that `section`/`type` exist in config, falling back to the first type.
2. Resolve blocks in order: section defaults → type `blocks` → `category_blocks`.
3. Expand blocks into a flat list of field keys (for Blade conditionals and controller
   validation).
4. Return labels/help/control metadata merged from `field_meta`, section `labels`, and
   type `labels` (most specific wins).
5. Return option lists for category/accent/status/icon/datalist controls.

The controller passes `editorSchema` (all types for the section, plus the resolved
current type) to the view. This keeps Blade logic thin and makes the rules unit-testable.

---

## 7. Editor changes

### 7.1 Structure

Replace the ad-hoc `data-type-panel` string lists with one wrapper per logical block:

```blade
<div data-editor-block="source"> ... </div>
<div data-editor-block="tier"> ... </div>
```

Each wrapper carries the resolved field keys in a data attribute or the JS reads a
JSON map emitted by the controller:

```blade
<script>
    window.cmsEditorSchema = @json($editorSchema);
</script>
```

`updateTypePanels()` becomes `updateEditorBlocks()`:

- read current `type` and `category`;
- resolve the block list from `window.cmsEditorSchema`;
- toggle `hidden` on every `[data-editor-block]`;
- toggle publish vs protected panel for `submission`;
- update labels/help from schema (optional; labels can also be server-rendered per type).

Server-side, wrap each block in `@if (in_array($block, $initialBlocks, true))` so the
editor is usable without JavaScript. Keep the JS path for type/category changes.

### 7.2 Labels

Use one consistent vocabulary. Defaults:

| Field | Label | Help |
|---|---|---|
| `type` | Content type | What this item represents on the page |
| `title` | Title | |
| `slug` | URL slug | Leave blank to generate from the title |
| `subtitle` | Subtitle | Short line above or under the title |
| `description` | Body / description | |
| `category` | Category | Groups related items on the page |
| `location` | Location | Shown on news cards and article pages |
| `published_on` | Published date | Displayed date, not the publish switch |
| `image` | Main image | |
| `alt_text` | Image description (alt text) | Describe the image for screen readers |
| `media_asset_id` | Use a media library file | |
| `document` | Document file | PDF, DOC or DOCX up to 10 MB |
| `button_text` | Button label | |
| `button_url` | Button link | Internal path, full URL, or `mailto:` |
| `accent_color` | Accent colour | |
| `sort_order` | Display order | Lower numbers appear first |
| `source_*` | Source citation | Name, URL, date, visible excerpt |
| `item_*` | Repeating details | Label/value pairs shown as cards |
| `bio_*` | Biography sections | Used by Commissioner cards |
| `bullets` | Bullet points | One per line; used by the About story tabs |
| `news_placement` | News placement | Hero carousel, featured slot, or both |
| `price`/`badge`/`reach` | Package price/badge/reach | Sponsorship tier card |
| `tier_placement` | Ad placements | One per line |
| `tier_include` | What is included | One per line |
| `ends_at` | Campaign ends | Expired adverts stop appearing |
| `hero_featured` | Feature in the advertise hero | Only for Sponsored Ads |
| `stat_icon` | Stat icon | Font Awesome class, e.g. `fa-handshake` |
| `enquiry_status` | Enquiry status | Pending, approved, or rejected |
| `size`/`pages` | File size / page count | Resource metadata |
| `youtube_url` | Video URL | YouTube or embed URL |
| `allocation_*` | Donation allocation | Percentages shown in the pie chart |

Section/type overrides:

- Home `update`: `title` → "Story headline"; `button_text` → "Read more label".
- About `card` + story: `bullets` → "Story bullet points"; `image` → "Tab background image".
- About `card` + `Commissioners` / `Ex-Officials` / `Management`: `subtitle` → "Role"; `body` → "Fallback biography".
- Children's `card`: `subtitle` → "Icon class"; help links to Font Awesome.
- Children's `news`: `button_text` → "Read more label".
- Resources `resource`: `title` → "Document title"; `button_text` → "Download label".
- Advertise `stat`: `title` → "Number"; `description` → "Label"; `stat_icon` → "Icon".
- Advertise `submission`: `title` → "Reference / subject"; `body` → "Inquiry details".
- Donate `card`: category-specific labels, e.g. `description` → "Allocation text" for
  `Where will your donation go`, `description`/`youtube_url` → "Video URL" for
  `Watch Our Story`.

### 7.3 Dropdowns and datalists

| Control | Type | Source |
|---|---|---|
| Section | read-only badge (URL already fixes it) | `sectionConfig['label']` |
| Content type | `<select>` | `$typeOptions` / `type_options` |
| Category | `<select>` (strict) or `<input list>` (custom allowed) | `CmsEditorSchema::categoryOptions()` |
| Accent colour | `<select>` | `cms.accent_colors` (add `red`) |
| Publish status | `<select>` | draft / published / scheduled (existing) |
| Enquiry status | `<select>` | `pending_review`, `approved`, `rejected` |
| Location | `<input list>` | Malawi districts + `National`/`International` (new `cms.locations`) |
| News category | `<input list>` | `cms.news_categories` |
| Icon class | `<input list>` | common Font Awesome classes from the seeder + docs |
| News placement | checkboxes | `hero`, `featured` |
| Category-specific groups | `<select>` | per-section category lists |

Keep free text only where the value is genuinely open-ended (body copy, URLs, alt text,
item values, prices).

### 7.4 Repeaters

Add lightweight "Add row" / "Remove row" controls for:

- existing `items` and `bio` repeaters;
- new `bullets`, `news_placement`, `tier_placement`, `tier_include`.

Reuse the existing naming convention `name="bullets[]"` so the current autosave
serialiser (`key.endsWith('[]')`, `editor.blade.php:356-367`) keeps working. For
checkbox groups, autosave currently restores strings only; guard with a small
`restoreDraft` improvement that checks `field.type === 'checkbox'`/`radio`.

---

## 8. Controller changes

### 8.1 Store/update

1. Resolve the schema for the submitted type (and category):
   `$schema = CmsEditorSchema::for($section, $type);`
2. Build the category rule from the schema:
   - strict list → `Rule::in(array_keys($options))`;
   - custom-allowed → `['nullable', 'string', 'max:100']`;
   - none → omit/`nullable`.
3. Keep every existing rule for fields that remain, so current tests and data contracts
   stay valid.
4. Add rules for new fields:

```php
'bullets' => ['nullable', 'array'],
'bullets.*' => ['nullable', 'string', 'max:500'],
'news_placement' => ['nullable', 'array'],
'news_placement.*' => [Rule::in(['hero', 'featured'])],
'price' => ['nullable', 'string', 'max:50'],
'badge' => ['nullable', 'string', 'max:50'],
'reach' => ['nullable', 'string', 'max:100'],
'tier_placement' => ['nullable', 'array'],
'tier_placement.*' => ['nullable', 'string', 'max:100'],
'tier_include' => ['nullable', 'array'],
'tier_include.*' => ['nullable', 'string', 'max:255'],
'ends_at' => ['nullable', 'date'],
'stat_icon' => ['nullable', 'string', 'max:100'],
'enquiry_status' => ['nullable', Rule::in(['pending_review', 'approved', 'rejected'])],
'size' => ['nullable', 'string', 'max:30'],
'pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
'youtube_url' => ['nullable', 'url', 'max:500'],
'allocation_direct' => ['nullable', 'numeric', 'between:0,100'],
'allocation_fundraising' => ['nullable', 'numeric', 'between:0,100'],
```

5. Pass the schema to `create`/`edit` so Blade can render labels/options.

### 8.2 `prepareContentData()`

Extend the existing structured-data builder; do not replace it.

- Keep `source`, `items`, `bio_sections` behaviour and merge order
  (`data_json` first, structured fields over it).
- Add:
  - `bullets` → filter blank lines, store as a list.
  - `news_placement` → `data.placements`.
  - `price`/`badge`/`reach`/`tier_placement`/`tier_include` → `data.price`, etc.
  - `ends_at` → `data.ends_at` (keep `Y-m-d` for the existing string comparison).
  - `stat_icon` → `data.icon`.
  - `enquiry_status` → `data.status` (only for `advertise` + `submission`).
  - `size`/`pages` → `data.size`, `data.pages`.
  - `youtube_url` → `data.youtube_url`.
  - `allocation_direct`/`allocation_fundraising` → `data.allocation.direct` /
    `data.allocation.fundraising`.
- Only write keys that belong to the resolved block list for the submitted
  section/type/category. This prevents a hidden field from a previous type change leaking
  into the new type.
- Preserve the existing `data.is_featured` mapping for Advertise (from `hero_featured`),
  but restrict it to `advertise` + `card` + `Sponsored Ads` (or keep writing it for all
  advertise cards without harm; showing the checkbox only for Sponsored Ads is the UI
  change).
- Do not touch `preview_token`, publishing state, revision or audit behaviour.

### 8.3 Index and preview (small follow-ups)

- `index.blade.php`: add a category filter dropdown when the section has a fixed
  category list; keep the existing type/status selects and search.
- `preview.blade.php`: group the new data keys (tier, placements, allocation, video) under
  readable headings instead of one raw JSON block. Keep the raw JSON block as a fallback.
- Type labels in the index already come from `config('cms.types')`; no change needed
  unless per-section labels are introduced.

---

## 9. Validation strategy

Principles:

1. **Type is still the gate.** `type` is always `Rule::in($sectionConfig['types'])`.
   There is no legacy branch because the data is test data. `submission` stays
   Advertise-only and remains unpublishable.
2. **Category is strict only where the page requires exact values.** Resources,
   Donate, Reporting, About, What We Do, Children's Corner, and Advertise use
   `Rule::in(...)`. News uses a datalist + `max:100` so new categories still work.
3. **New fields are optional unless the page cannot render without them.** Start with
   `nullable` everywhere; only add `required` where the public block is meaningless
   without it (e.g. Resources `resource`: `title`, `category`; Advertise `submission`:
   `enquiry_status` once exposed).
4. **Keep existing limits** (title 255, category 100, button text 100, images 3 MB,
   documents 10 MB, URLs 255/500).
5. **Cross-field checks stay soft.** Allocation percentages can be saved if they do not
   total 100; the pie chart already normalises by the sum. Optionally show a warning, not
   a validation error.
6. **No new required publish fields.** Draft/scheduled/published semantics are unchanged.
7. **Submission status changes must not bypass `AdEnquiries`.** The status dropdown writes
   `data.status` only; the query/expiry logic remains in `AdEnquiries` and
   `SiteContent::enquiry_status`.

Add a **config/seed parity test** so category drift cannot silently return:

```php
public function test_seeded_categories_are_valid_for_their_section(): void
{
    // For each (section, type) with a configured category list,
    // assert every category used by SiteContentSeeder is in that list.
}
```

---

## 10. What stays the same

| Capability | Guarantee |
|---|---|
| Autosave | Same `data-autosave-key`, same `FormData` serialisation; new repeaters use `name="...[ ]"`. Improve checkbox restore only. |
| Preview | `admin.contents.preview` route unchanged; `$editingItem` still passed to the editor. |
| Media | Image upload, library selection, alt text, remove-image flow unchanged. |
| Document | Upload, current-document link, remove flow unchanged; shown only for Resources `resource`. |
| Publishing | Draft / Publish now / Schedule unchanged; `submission` still private. |
| Revisions | `data` is already snapshotted, so all new JSON keys restore automatically. No snapshot list change needed. |
| Audit | `created`, `updated`, `published`, `revision_restored` actions unchanged. |
| Structured data | `source`, `items`, `bio_sections` keep their current shapes and merge order. |
| Frontend rendering | No data key is renamed or removed; all public views keep working with the reseeded test rows. |

---

## 11. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Category drift (Donate seeded rows already fail strict validation) | Editors cannot save donation cards after the change | Add the missing values to config, run the parity test above, and reseed the test content against the new lists. No production audit needed. |
| Removing unused types (`home/stat`, `home/cta`, `about/cta`, `resources/card`, `news/hero`, `news/card`, `reporting/stat`, `reporting/cta`) | Rows of those types would be invalid after the config change | Delete them with the one-off `cms:prune-invalid-types` command (dry-run first). Since all content is test data, no migration or legacy mode is required. |
| Pruning test rows someone still wants as demo content | Local/staging loses examples | Run with `--dry-run`, review the list, then reseed. The seed data is the canonical demo content. |
| Hidden-but-submitted fields | Switching type could carry stale values into another type's `data` | Resolve allowed blocks server-side in `prepareContentData()` and only map those keys. Keep existing values in the DOM so nothing is lost unintentionally. |
| `data_json` vs form-field precedence | Two sources writing the same keys | Keep the current rule: structured fields override JSON. Document it in the Advanced JSON help text. |
| `array_replace_recursive` on sequential arrays | Stale items/includes can survive if arrays shrink | Keep existing item behaviour; for new list fields rebuild the array from submitted values rather than merging recursively. |
| One `media_asset_id` for both image and document | Selecting a library asset can populate the wrong path | Show the library selector only in blocks that use an asset; keep the existing `applyMediaSelection()` type branching. |
| `button_url` vs `button_link` inconsistency | Some views use raw `button_url`, others normalise via `button_link` | Do not rename the column. Standardise new help text ("internal path or full URL"). Optionally switch `home.blade.php` and `cms-public-blocks.blade.php` to `button_link` in a separate, tested change. |
| News placement/featured logic | Editors may expect immediate hero placement | Help text: placements only apply to published items; `heroNews()`/`featuredNews()` still fall back to the latest news. |
| Enquiry privacy | Editing status could expose private records | Only advertise `submission` gets `submission_meta`; reporting submissions stay excluded from the editor and remain private by default. |
| Autosave with checkboxes/radios | Restore could set the wrong state | Update `restoreDraft()` to handle `checkbox`/`radio` explicitly. |
| Without JavaScript | Block toggling would not work | Server-render the initial block list; JS is an enhancement only. |
| Existing tests assert raw strings ("Structured Fields", "Publish") | Cosmetic failures | Keep those headings or update the assertions in the same commit. |

---

## 12. Test plan

### 12.1 New schema/visibility tests

`tests/Feature/AdminContentEditorTest.php`

- `test_resource_editor_shows_document_and_resource_metadata_only`
  - `resources` + `resource` sees `Document file`, `Resource category`, `File size`,
    `Page count`, `Download button label`; does not see `Source citation`,
    `Biography sections`, `News placement`, `Location`.
- `test_news_editor_shows_source_and_placements`
  - `news` + `news` sees `Headline`, `Location`, `Published date`, `Source citation`,
    `News placement`; does not see `Document file`, `Biography sections`.
- `test_about_commissioner_card_shows_biography_fields`
  - `about` + `card` + `Commissioners` sees `Role`, `Biography sections`;
    does not see `Source citation`, `Document file`.
- `test_about_people_categories_share_the_same_card_fields`
  - `about` + `card` with `Commissioners`, `Ex-Officials`, or `Management` all show
    `Role`, `Image`, and `Biography sections`; `Organogram` is not offered.
- `test_about_page_removes_organogram_renderer`
  - The About page no longer outputs the old Organogram chart/heading.
- `test_advertise_sponsored_ad_shows_expiry_and_feature_toggle`
  - `advertise` + `card` + `Sponsored Ads` sees `Campaign ends`,
    `Feature in the advertise hero`; does not see `Biography sections`.
- `test_donate_category_options_include_all_seeded_groups`
  - The category select contains `Giving Options`, `Watch Our Story`, and the FAQ group.
- `test_childrens_corner_offers_resource_type_and_icon_field`
  - `childrens-corner` type select includes `Resource`; Activities card shows `Icon class`.
- `test_home_update_shows_accent_and_read_more_only`
  - `home` + `update` sees `Accent colour`, `Read more label`; does not see source/bio.
- `test_add_new_only_offers_types_the_page_renders`
  - `resources` Add New chooser contains Hero and Resource, not Card; `news` contains
    News, not Hero/Card.
- `test_editor_states_page_and_type_in_context_banner`
  - The editor response names the page label and the selected type label.

### 12.2 New authoring-data tests

`tests/Feature/AdminContentTest.php` (extend)

- Saving a News item with `news_placement[] = ['hero','featured']` writes
  `data.placements` and changes `heroNews()`/`featuredNews()` order.
- Saving a Sponsorship card writes `data.price`, `data.badge`, `data.reach`,
  `data.placements`, `data.includes` and the public page renders them.
- Saving a Sponsored Ad with `ends_at` in the past removes it from
  `CmsContent::sponsoredAds()`.
- Saving an About story card with `bullets[]` writes `data.bullets` and the About
  tabs render the bullets.
- Saving a Donate card with `youtube_url` writes `data.youtube_url` and the page embeds it.
- Saving an Advertise enquiry status writes `data.status` and `AdEnquiries` counts move
  between pending/approved/rejected.
- Saving a Resource with `size`/`pages` renders both on the Resources page.
- Strict category validation rejects an unknown Donate/Resources/About category.

### 12.3 Regression tests (must stay green)

- `AdminContentTest`: create/edit, publish, schedule, bulk, reorder, media reuse,
  document upload, revision restore, submission protection, invalid type.
- `PublicCmsContentTest`: all public rendering expectations.
- `AdvertiseHeroTest`: hero ad selection, expiry, featured flag.
- New: `test_editor_autosave_key_and_preview_link_are_preserved`.
- New: `test_every_seeded_category_is_in_its_section_config`.
- New: `test_every_configured_type_is_in_the_section_type_list`.
- New: `test_removed_types_are_not_creatable_or_accepted`.
- New: `test_prune_command_dry_run_keeps_rows_and_real_run_deletes_only_unconfigured_types`.

### 12.4 Manual checks

- Create/edit one item per section; switch type and category; confirm blocks appear and
  disappear, and saving the new type does not leave stale unrelated fields.
- Preview draft, restore a revision, upload an image, upload a document, schedule a post.
- Confirm the Advertise enquiry board still filters and counts correctly.

---

## 13. Phased rollout

### Phase 1 — Config, resolver, cleanup (no UI change)

- Add missing category/accent values; add `type_options`, `blocks`, `labels`, `help`,
  `category_blocks`, `locations`, `news_categories`; replace each section's `types` with
  the final list.
- Add `CmsEditorSchema` with unit tests.
- Add the config/seed parity test.
- Add `cms:prune-invalid-types` (with `--dry-run`) and run it; reseed the test content.

### Phase 2 — Editor UI and navigation

- Group the sidebar by frontend page and add "View page" to the page list.
- Replace the blind Add New link with a rendered-type chooser; add the page summary.
- Number the editor panels and add the contextual "page + type" banner.
- Replace `data-type-panel` with block wrappers.
- Emit `window.cmsEditorSchema`; update `updateEditorBlocks()`.
- Add labels, help text, selects/datalists, repeaters.
- Server-render the initial block set for no-JS use.

### Phase 3 — Controller and validation

- Resolve schema in `store`/`update`; derive category rules.
- Add new field validation.
- Extend `prepareContentData()` and `structuredDataFromFields()`.
- Pass schema to `create`/`edit`.

### Phase 4 — Optional frontend follow-ups

- Donate allocation: prefer `data.allocation` over regex, keep regex fallback.
- Donate video: prefer `data.youtube_url`, keep description fallback (already supported).
- About: design a new dynamic Organogram renderer before re-introducing the
  `Organogram` category; until then Management uses the shared people card.
- Home: if the impact counters should be editable, render `stat` rows and re-add the type;
  otherwise leave it out. Do not re-add a type the page does not render.
- Standardise `button_url` usage through `button_link`.

### Phase 5 — Cleanup and docs

- Reseed the canonical test content against the final type/category lists.
- Confirm `cms:prune-invalid-types` reports zero remaining rows.
- Update `docs/` with the final field map and category lists.

---

## 14. File-by-file summary

| File | Change |
|---|---|
| `config/cms.php` | Add `red`; complete category lists; add `type_options`, blocks, labels, help, `category_blocks`, `locations`, `news_categories`; replace `types` with the final lists. |
| `app/Support/CmsEditorSchema.php` | **New.** Resolve blocks, fields, labels, options per section/type/category. |
| `app/Console/Commands/PruneInvalidCmsTypes.php` | **New.** Report/delete rows whose type is not in their section's `types`; supports `--dry-run`. |
| `app/Http/Controllers/Admin/ContentController.php` | Pass schema; schema-driven category rule; new validation; map new keys into `data`; keep publishing/revision/audit intact. |
| `resources/views/admin/contents/editor.blade.php` | Numbered panels, contextual banner, block wrappers, labels, selects/datalists, repeaters, schema-driven JS toggling, safer autosave restore. |
| `resources/views/admin/contents/index.blade.php` | Page header with "View page", type chooser for Add New, rendered-types filter, optional category filter, page-appropriate columns. |
| `resources/views/admin/layouts/app.blade.php` | Group sections under "Website pages" in public-navigation order; keep shared tools separate. |
| `app/Http/Controllers/Admin/ContentController.php` (index) | Filter `typeOptions` to rendered types for the Add New chooser and index filter; pass page summary/schema. |
| `resources/views/components/about-person-card.blade.php` | **New.** Shared card + biography modal for Commissioners, Ex-Officials, and Management. |
| `resources/views/pages/about.blade.php` | Three people tabs share the new card; Management uses the `Management` category; old Organogram chart removed. |
| `database/seeders/SiteContentSeeder.php` | Align category casing/values with config; Management/Ex-Officials carry the shared subtitle + `bio_sections` shape; no Organogram seeds. |
| `resources/views/admin/contents/preview.blade.php` | Optional grouped rendering of new data keys. |
| `resources/views/partials/cms-section-page.blade.php` | Optional: prefer structured allocation/video keys with current fallbacks. |
| `resources/views/pages/home.blade.php` | Optional: use `button_link`; optionally render CMS stats/CTAs. |
| `tests/Feature/AdminContentEditorTest.php` | **New.** Visibility, labels, options, data mapping. |
| `tests/Feature/AdminContentTest.php` | Extend with new field and validation tests. |
| `tests/Feature/PublicCmsContentTest.php` | Shared people-card coverage and organogram removal. |
| `tests/Feature/AdvertiseHeroTest.php` | Regression only. |
| `docs/cms-editor-plan.md` | This plan. |

---

## 15. Decisions (resolved)

1. **Unused types:** judged by whether the page displays that kind of content. Home
   `stat` is the only relevant-but-unwired type (hard-coded counters); it is removed until
   wired. All other unused types are irrelevant and are removed from their sections.
   Because the content is test data, their rows are deleted by the prune command and the
   seeder is refreshed. No `legacy_types` branch.
2. **News categories:** datalist with seeded suggestions, custom values allowed.
3. **Location:** datalist of Malawi districts plus `National` / `International`, custom
   values allowed.
4. **Donate allocation:** structured `allocation_direct` / `allocation_fundraising`
   fields writing `data.allocation`, with the existing description-regex fallback.
5. **Home stats/CTAs:** retire from the Home editor now. If the homepage stats need to be
   editable later, wire the four counters to `stat` rows first, then re-enable the type.
