<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reporting / case management
    |--------------------------------------------------------------------------
    |
    | Evidence is deliberately written to a private disk. There is no public
    | URL for case evidence; files are streamed through an authorised controller.
    |
    */

    'evidence_disk' => env('REPORT_EVIDENCE_DISK', 'report_evidence'),

    'max_files_per_request' => (int) env('REPORT_EVIDENCE_MAX_FILES', 5),
    'max_total_files' => (int) env('REPORT_EVIDENCE_MAX_TOTAL_FILES', 20),
    'max_total_mb' => (int) env('REPORT_EVIDENCE_MAX_TOTAL_MB', 100),

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'mp3', 'mp4', 'pdf'],

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'audio/mpeg',
        'video/mp4',
        'application/pdf',
    ],

    'max_file_mb' => (int) env('REPORT_EVIDENCE_MAX_FILE_MB', 20),

    'pin_max_attempts' => (int) env('REPORT_PIN_MAX_ATTEMPTS', 5),
    'pin_lock_minutes' => (int) env('REPORT_PIN_LOCK_MINUTES', 15),
    'grant_minutes' => (int) env('REPORT_TRACK_GRANT_MINUTES', 15),

    // Case staff must re-confirm a TOTP code after this long, and after every
    // fresh login (the session is invalidated on logout).
    'totp_session_minutes' => (int) env('REPORT_TOTP_SESSION_MINUTES', 480),
    'totp_recovery_codes' => 8,

    // Closed cases are retained for two years, as agreed with the client.
    'retention_years' => (int) env('REPORT_RETENTION_YEARS', 2),

    // Public statistics are national-level only and small cells are suppressed.
    'stats_min_cell' => (int) env('REPORT_STATS_MIN_CELL', 5),

    'estimated_response' => 'We aim to acknowledge new cases promptly and provide an update within 4–6 weeks.',
    'hotline' => '116',
];
