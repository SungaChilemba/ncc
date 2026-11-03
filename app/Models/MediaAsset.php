<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'alt_text',
        'caption',
        'description',
    ];

    public function getHumanSizeAttribute(): string
    {
        if ($this->size >= 1048576) {
            return number_format($this->size / 1048576, 1).' MB';
        }

        if ($this->size >= 1024) {
            return number_format($this->size / 1024, 1).' KB';
        }

        return $this->size.' B';
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'images/') || str_starts_with($this->path, 'resource/')) {
            return asset($this->path);
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
