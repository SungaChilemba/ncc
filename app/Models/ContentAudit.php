<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
