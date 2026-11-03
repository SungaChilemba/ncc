<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'user_id',
        'action',
        'fields_accessed',
        'reason',
        'ip_hash',
        'user_agent_hash',
        'created_at',
    ];

    protected $casts = [
        'fields_accessed' => 'array',
        'created_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
