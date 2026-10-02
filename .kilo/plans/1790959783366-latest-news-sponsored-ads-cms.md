# Plan: Sponsored Ads from CMS — Reusable Component for Latest News & Advertise Pages

## Goal
Replace hard-coded Sponsored Ads in `latest-news.blade.php` with CMS data, using a **reusable Blade component** so the same carousel renders on `/latest-news`, `/advertise`, and any future page without duplication.

## Current State
- `latest-news.blade.php:316-341` — hard-coded `$sponsoredAds` array (3 demo entries)
- `advertise.blade.php:22,442-506` — fetches CMS cards via `$cardsByCat->get("Sponsored Ads")` and renders its own carousel markup (similar but not identical Alpine.js config)
- `PageController::latestNews()` passes no Sponsored Ads data
- CMS source: `section=advertise`, `type=card`, `category=Sponsored Ads` (config/cms.php:27)

## Required Changes

### 1. Add CMS method to fetch Sponsored Ads
**File:** `app/Support/CmsContent.php`
```php
public function sponsoredAds(?int $limit = null): Collection
{
    $query = SiteContent::forSection('advertise')
        ->ofType('card')
        ->published()
        ->where('category', 'Sponsored Ads')
        ->ordered();

    if ($limit !== null) {
        $query->limit($limit);
    }

    return $query->get();
}
```

### 2. Create reusable Blade component
**File:** `resources/views/components/sponsored-ads-carousel.blade.php`

- Accepts `Collection $ads` (mapped to template shape) and optional `int $limit`
- Encapsulates **all** Alpine.js carousel markup (slider, dots, arrows, responsive perView)
- Maps each `SiteContent` card to the shape the carousel expects:
  ```php
  [
      'title'       => $ad->title,
      'description' => $ad->description,
      'image'       => $ad->image_url ? $ad->image_url . '?v=' . $ad->updated_at->timestamp : asset('images/news1.png'),
      'is_featured' => (bool) data_get($ad->data, 'is_featured'),
      'ends_at'     => data_get($ad->data, 'ends_at') ?? '',
      'url'         => $ad->button_link ?: route('advertise'),
  ]
  ```
- Renders nothing if `$ads->isEmpty()` (caller decides fallback)

### 3. Update PageController to pass Sponsored Ads to latest-news view
**File:** `app/Http/Controllers/PageController.php` → `latestNews()`
```php
$sponsoredAds = $this->content->sponsoredAds();  // Collection of SiteContent models

return view('pages.latest-news', [
    // ... existing vars
    'sponsoredAds' => $sponsoredAds,
    'suppressCmsBlocks' => true,
]);
```

### 4. Update latest-news.blade.php — use component
**File:** `resources/views/pages/latest-news.blade.php`

Replace lines 278-424 (entire Sponsored Ads section) with:
```blade
@if ($sponsoredAds->isNotEmpty())
    <x-sponsored-ads-carousel :ads="$sponsoredAds" />
@endif
```
Remove the hard-coded `@php $sponsoredAds = [...] @endphp` block and the duplicated carousel markup.

### 5. Update advertise.blade.php — use same component
**File:** `resources/views/pages/advertise.blade.php`

Replace lines 442-506 (the `@if ($sponsoredAds->isNotEmpty())` carousel block) with:
```blade
<x-sponsored-ads-carousel :ads="$sponsoredAds" />
```
(Keep the existing `$sponsoredAds` variable from line 22 — it's already a Collection of SiteContent models.)

### 6. Optional: Demo fallback for latest-news
If product wants demo ads when CMS empty on latest-news only, add in controller or view:
```php
// In latestNews() or inline in latest-news.blade.php
$sponsoredAds = $sponsoredAds->isNotEmpty() ? $sponsoredAds : collect([ /* demo array */ ]);
```
Advertise page intentionally shows nothing when empty (current behavior).

## Affected Files
1. `app/Support/CmsContent.php` — new `sponsoredAds()` method
2. `app/Http/Controllers/PageController.php` — pass `sponsoredAds` to latest-news view
3. `resources/views/components/sponsored-ads-carousel.blade.php` — **new** reusable component
4. `resources/views/pages/latest-news.blade.php` — replace hard-coded section with component
5. `resources/views/pages/advertise.blade.php` — replace duplicate carousel with component

## Validation
- `/latest-news` — Sponsored Ads carousel renders CMS cards (or demo fallback if added)
- `/advertise` — Sponsored Ads carousel renders identically (same component)
- Add a Sponsored Ad in admin (section=advertise, type=card, category=Sponsored Ads) → appears on **both** pages
- Alpine.js carousel: prev/next/dots, responsive 1/2/3 per view, no JS errors
- Card fields: title, description, image, FEATURED badge (from `data.is_featured`), ends_at (from `data.ends_at`), link to advertise page
- No duplicate markup between pages

## Risks / Edge Cases
- CMS cards may lack `image_url` / `button_link` / `ends_at` → fallbacks in component mapping
- Category match exact string "Sponsored Ads" (case-sensitive)
- Advertise page already groups cards by category; component receives pre-filtered collection
- Component is stateless — safe to include multiple times on same page (each gets own Alpine scope)

## Out of Scope
- Admin UI for Sponsored Ads (exists via advertise section)
- Changing carousel behavior / card layout
- Pagination / load-more for Sponsored Ads
- Other pages not yet identified — component ready when needed