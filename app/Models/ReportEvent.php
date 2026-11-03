<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportEvent extends Model
{
    protected $fillable = [
        'report_id',
        'from_status',
        'to_status',
        'type',
        'message',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
