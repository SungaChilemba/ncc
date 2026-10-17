<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Database\Eloquent\Builder;

class AdEnquiries
{
    public const FILTERS = ['all', 'pending_review', 'approved', 'expired', 'rejected'];

    public const LABELS = [
        'all' => 'All',
        'pending_review' => 'Pending',
        'approved' => 'Approved',
        'expired' => 'Expired',
        'rejected' => 'Rejected',
    ];

    public function query(): Builder
    {
        return SiteContent::where('section', 'advertise')
            ->where('type', 'submission')
            ->where('category', 'Ad Inquiries');
    }

    public function filter(?string $status): Builder
    {
        $query = $this->query();
        $today = now()->toDateString();

        return match ($status) {
            'pending_review' => $query->where(function (Builder $query) {
                $query->whereNull('data->status')->orWhere('data->status', 'pending_review');
            }),
            'approved' => $query->where('data->status', 'approved')
                ->where(function (Builder $query) use ($today) {
                    $query->whereNull('data->ends_at')->orWhere('data->ends_at', '>=', $today);
                }),
            'expired' => $query->where('data->status', 'approved')
                ->whereNotNull('data->ends_at')
                ->where('data->ends_at', '<', $today),
            'rejected' => $query->where('data->status', 'rejected'),
            default => $query,
        };
    }

    public function counts(): array
    {
        $counts = ['all' => $this->query()->count()];

        foreach (array_slice(self::FILTERS, 1) as $status) {
            $counts[$status] = $this->filter($status)->count();
        }

        return $counts;
    }

    public function pendingCount(): int
    {
        return $this->filter('pending_review')->count();
    }

    public function latestPending(int $limit = 5)
    {
        return $this->filter('pending_review')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
