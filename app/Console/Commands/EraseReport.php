<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Support\ReportReference;
use App\Support\ReportRetention;
use Illuminate\Console\Command;

class EraseReport extends Command
{
    protected $signature = 'reports:erase
                            {reference : Case reference number, for example NCC-2026-A1B2C3}
                            {--reason= : Why the erasure request is being honoured (required)}
                            {--force : Skip the interactive confirmation prompt}';

    protected $description = 'Erase personal data for a single case, keeping de-identified statistics and audit history';

    public function handle(ReportRetention $retention): int
    {
        $reason = trim((string) $this->option('reason'));

        if ($reason === '') {
            $this->error('An erasure reason is required. Pass --reason="..." so the audit trail explains the action.');

            return self::FAILURE;
        }

        $reference = ReportReference::normalize($this->argument('reference'));
        $report = Report::withTrashed()->where('reference_number', $reference)->first();

        if (! $report) {
            $this->error("No case found for reference {$reference}.");

            return self::FAILURE;
        }

        if ($report->legal_hold) {
            $this->error('This case is under legal hold and cannot be erased automatically. Escalate to legal counsel.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm("Erase all personal data for {$reference}? This cannot be undone.");

            if (! $confirmed) {
                $this->warn('Cancelled.');

                return self::FAILURE;
            }
        }

        if (! $retention->anonymise($report, 'Erasure request: '.$reason)) {
            $this->warn("{$reference} is already anonymised.");

            return self::SUCCESS;
        }

        $this->info("Personal data for {$reference} has been erased.");

        return self::SUCCESS;
    }
}
