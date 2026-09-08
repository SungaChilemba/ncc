<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRevision extends Model
{
    protected $fillable = [
        'site_content_id',
        'user_id',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function siteContent()
    {
        return $this->belongsTo(SiteContent::class);
    }
}
