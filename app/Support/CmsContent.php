<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Collection;

class CmsContent
{
    public function forSection(string $section): Collection
    {
        return SiteContent::forSection($section)
            ->published()
            ->ordered()
            ->get()
            ->groupBy('type');
    }

    public function first(string $section, string $type): ?SiteContent
    {
        return SiteContent::forSection($section)
            ->ofType($type)
            ->published()
            ->ordered()
            ->first();
    }

    public function type(string $section, string $type): Collection
    {
        return SiteContent::forSection($section)
            ->ofType($type)
            ->published()
            ->orderByDesc('published_on')
            ->ordered()
            ->get();
    }

    public function bySlug(string $section, string $slug, ?string $type = null): ?SiteContent
    {
        return SiteContent::forSection($section)
            ->when($type, fn ($query) => $query->ofType($type))
            ->where('slug', $slug)
            ->published()
            ->first();
    }

    public function heroImage(?SiteContent $hero, string $fallback): string
    {
        if (! $hero?->image_path) {
            return asset($fallback);
        }

        return $hero->image_url . '?v=' . $hero->updated_at->timestamp;
    }
}
