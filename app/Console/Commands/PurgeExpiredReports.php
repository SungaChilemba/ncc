<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Support\ReportRetention;
use Illuminate\Console\Command;

class PurgeExpiredReports extends Command
{
    protected $signature = 'reports:purge-expired
                            {--dry-run : Report how many cases are due without changing anything}
                            {--limit=200 : Maximum cases to process in one batch}';

    protected $description = 'Anonymise closed cases whose retention period has expired';

    public function handle(ReportRetention $retention): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $query = Report::withTrashed()
            ->where('legal_hold', false)
            ->whereNull('anonymised_at')
            ->whereNotNull('retention_delete_at')
            ->where('retention_delete_at', '<=', now());

        $total = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("{$total} case(s) are due for anonymisation.");

            return self::SUCCESS;
        }

        $processed = 0;

        do {
            $reports = $query->orderBy('retention_delete_at')->limit($limit)->get();

            foreach ($reports as $report) {
                if ($retention->anonymise($report, 'Retention period expired')) {
                    $processed++;
                }
            }
        } while ($reports->count() === $limit);

        $this->info("Anonymised {$processed} expired case(s) of {$total} due.");

        return self::SUCCESS;
    }
}
