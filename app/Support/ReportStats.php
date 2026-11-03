<?php

namespace App\Support;

use App\Models\Report;
use Illuminate\Support\Facades\DB;

/**
 * National-level, anonymised aggregates for the public statistics tab.
 * Any cell smaller than the configured minimum is suppressed so the numbers
 * cannot be used to re-identify a case in a small district or category.
 */
class ReportStats
{
    public function forYear(?int $year = null): array
    {
        $year ??= now()->year;
        $minCell = max(1, (int) config('reporting.stats_min_cell', 5));

        $base = Report::query()
            ->whereYear('received_at', $year);

        $total = (clone $base)->count();

        $closed = (clone $base)
            ->whereIn('status', Report::CLOSED_STATUSES)
            ->count();

        $byViolation = (clone $base)
            ->select('violation_type', DB::raw('count(*) as total'))
            ->groupBy('violation_type')
            ->pluck('total', 'violation_type')
            ->all();

        $byStatus = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $responseDays = (clone $base)
            ->whereNotNull('acknowledged_at')
            ->selectRaw('avg(julianday(acknowledged_at) - julianday(received_at)) as days')
            ->value('days');

        $resolutionDays = (clone $base)
            ->whereNotNull('closed_at')
            ->selectRaw('avg(julianday(closed_at) - julianday(received_at)) as days')
            ->value('days');

        return [
            'year' => $year,
            'total' => $this->cell($total, $minCell),
            'closed' => $this->cell($closed, $minCell),
            'resolution_rate' => $total > 0 && $closed >= $minCell
                ? round(($closed / $total) * 100)
                : null,
            'average_response_days' => $this->days($responseDays, $total, $minCell),
            'average_resolution_days' => $this->days($resolutionDays, $closed, $minCell),
            'by_violation' => $this->mapCells($byViolation, $minCell),
            'by_status' => $this->mapCells($byStatus, $minCell),
            'min_cell' => $minCell,
            'generated_at' => now(),
        ];
    }

    /**
     * Return only cells at or above the minimum, preserving order by count.
     *
     * @param  array<string, int|string>  $counts
     * @return array<string, int|null>
     */
    private function mapCells(array $counts, int $minCell): array
    {
        arsort($counts);

        $cells = [];

        foreach ($counts as $label => $count) {
            $cells[$label] = $this->cell((int) $count, $minCell);
        }

        return $cells;
    }

    private function cell(int $count, int $minCell): ?int
    {
        return $count >= $minCell ? $count : null;
    }

    private function days(float|int|string|null $value, int $sample, int $minCell): ?int
    {
        if ($sample < $minCell || $value === null) {
            return null;
        }

        return (int) round((float) $value);
    }
}
