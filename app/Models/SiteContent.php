<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteContent extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'section',
        'type',
        'slug',
        'title',
        'subtitle',
        'category',
        'location',
        'description',
        'data',
        'image_path',
        'alt_text',
        'file_path',
        'media_asset_id',
        'accent_color',
        'button_text',
        'button_url',
        'sort_order',
        'is_published',
        'published_at',
        'published_on',
        'preview_token',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'published_on' => 'date',
        'data' => 'array',
    ];

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function revisions()
    {
        return $this->hasMany(ContentRevision::class);
    }

    protected static function booted(): void
    {
        static::saving(function (SiteContent $content): void {
            if ($content->type === 'submission') {
                $content->is_published = false;
                $content->published_at = null;
            }
        });
    }

    public function scopeForSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('updated_at');
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('mediaAsset') && $this->mediaAsset) {
            return $this->mediaAsset->url;
        }

        return $this->assetUrl($this->image_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->relationLoaded('mediaAsset') && $this->mediaAsset) {
            return $this->mediaAsset->url;
        }

        return $this->assetUrl($this->file_path);
    }

    public function getButtonLinkAttribute(): ?string
    {
        return $this->assetUrl($this->button_url);
    }

    public function getPublicUrlAttribute(): ?string
    {
        if ($this->section === 'news' && $this->type === 'news' && $this->slug) {
            return route('news.show', $this->slug);
        }

        if ($this->section === 'resources' && $this->type === 'resource' && $this->slug) {
            return route('resources.show', $this->slug);
        }

        $routeName = config("cms.sections.{$this->section}.route");

        return $routeName ? route($routeName) : null;
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if ($path === '#' || str_starts_with($path, 'mailto:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'resource/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}
