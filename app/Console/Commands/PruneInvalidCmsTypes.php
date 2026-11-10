<?php

namespace App\Console\Commands;

use App\Models\SiteContent;
use Illuminate\Console\Command;

class PruneInvalidCmsTypes extends Command
{
    protected $signature = 'cms:prune-invalid-types
                            {--dry-run : Report the rows that would be removed without deleting them}';

    protected $description = 'Delete CMS rows whose type is no longer allowed for their section';

    public function handle(): int
    {
        $sections = config('cms.sections', []);
        $invalid = [];

        foreach ($sections as $section => $sectionConfig) {
            $types = $sectionConfig['types'] ?? [];

            $rows = SiteContent::query()
                ->where('section', $section)
                ->when($types !== [], fn ($query) => $query->whereNotIn('type', $types))
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->orderBy('type')
                ->get();

            foreach ($rows as $row) {
                $invalid[] = [
                    'section' => $section,
                    'type' => $row->type,
                    'total' => (int) $row->total,
                ];
            }
        }

        $unknownSections = SiteContent::query()
            ->whereNotIn('section', array_keys($sections))
            ->selectRaw('section, count(*) as total')
            ->groupBy('section')
            ->orderBy('section')
            ->get();

        if ($invalid === [] && $unknownSections->isEmpty()) {
            $this->info('No invalid CMS types found.');

            return self::SUCCESS;
        }

        if ($invalid !== []) {
            $this->table(
                ['Section', 'Type', 'Rows'],
                array_map(fn (array $row): array => [$row['section'], $row['type'], $row['total']], $invalid)
            );
        }

        foreach ($unknownSections as $row) {
            $this->warn("Skipped unknown section [{$row->section}] ({$row->total} row(s)); add a config section before pruning it.");
        }

        $total = array_sum(array_column($invalid, 'total'));

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$total} invalid CMS row(s) would be deleted.");

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($invalid as $row) {
            $deleted += SiteContent::query()
                ->where('section', $row['section'])
                ->where('type', $row['type'])
                ->delete();
        }

        $this->info("Deleted {$deleted} invalid CMS row(s).");

        return self::SUCCESS;
    }
}
