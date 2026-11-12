<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAudit extends Model
{
    protected $fillable = [
        'site_content_id',
        'user_id',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(SiteContent::class, 'site_content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
