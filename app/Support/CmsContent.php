<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Canonical published global news (section=news, type=news) ordered by published_on DESC with updated_at fallback.
     */
    public function publishedNews(?int $limit = null): Collection
    {
        $query = SiteContent::forSection('news')
            ->ofType('news')
            ->published()
            ->orderByRaw('published_on IS NULL, published_on DESC')
            ->orderByDesc('updated_at')
            ->orderBy('sort_order');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function latestNews(int $limit = 6): Collection
    {
        return $this->publishedNews($limit);
    }

    public function newsCategories(): Collection
    {
        return $this->publishedNews()
            ->pluck('category')
            ->filter()
            ->unique()
            ->values();
    }

    public function featuredNews(int $limit = 3): Collection
    {
        $all = $this->publishedNews();

        $featured = $all->filter(function (SiteContent $item) {
            $placements = data_get($item->data, 'placements');
            if (is_array($placements) && in_array('featured', $placements, true)) {
                return true;
            }
            if (data_get($item->data, 'is_featured') === true) {
                return true;
            }
            if (data_get($item->data, 'featured') === true) {
                return true;
            }

            return false;
        });

        if ($featured->isNotEmpty()) {
            return $featured->take($limit)->values();
        }

        return $all->take($limit)->values();
    }

    public function heroNews(int $limit = 3): Collection
    {
        $all = $this->publishedNews();

        $hero = $all->filter(function (SiteContent $item) {
            $placements = data_get($item->data, 'placements');
            if (is_array($placements) && (in_array('hero', $placements, true) || in_array('featured', $placements, true))) {
                return true;
            }

            return false;
        });

        if ($hero->isNotEmpty()) {
            return $hero->take($limit)->values();
        }

        return $all->take($limit)->values();
    }

    public function relatedNews(SiteContent $article, int $limit = 3): Collection
    {
        return SiteContent::forSection('news')
            ->ofType('news')
            ->published()
            ->where('id', '!=', $article->id)
            ->orderByRaw('published_on IS NULL, published_on DESC')
            ->orderByDesc('updated_at')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function sponsoredAds(?int $limit = null): Collection
    {
        $query = $this->liveSponsoredAdsQuery()->ordered();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Selection rule for the /advertise hero carousel: adverts flagged
     * is_featured take the first slots (most recent first), then any remaining
     * slots are filled with the most recent non-featured adverts. Expired and
     * unpublished adverts are never eligible.
     */
    public function heroSponsoredAds(int $limit = 3): Collection
    {
        return $this->liveSponsoredAdsQuery()
            ->get()
            ->sort(function (SiteContent $a, SiteContent $b) {
                $featured = (int) (bool) data_get($b->data, 'is_featured')
                    <=> (int) (bool) data_get($a->data, 'is_featured');

                if ($featured !== 0) {
                    return $featured;
                }

                return $this->recencyTimestamp($b) <=> $this->recencyTimestamp($a);
            })
            ->take($limit)
            ->values();
    }

    /**
     * Published, non-expired sponsored adverts across the advertise section.
     */
    protected function liveSponsoredAdsQuery(): Builder
    {
        return SiteContent::forSection('advertise')
            ->ofType('card')
            ->published()
            ->where('category', 'Sponsored Ads')
            ->where(function (Builder $query) {
                $query->whereNull('data->ends_at')
                    ->orWhere('data->ends_at', '>=', now()->toDateString());
            });
    }

    /**
     * Best-available timestamp used to order hero adverts by recency.
     */
    protected function recencyTimestamp(SiteContent $ad): int
    {
        return ($ad->published_at ?? $ad->updated_at ?? $ad->created_at)?->getTimestamp() ?? 0;
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

        return $hero->image_url.'?v='.$hero->updated_at->timestamp;
    }
}
