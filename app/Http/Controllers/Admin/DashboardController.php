<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudit;
use App\Models\MediaAsset;
use App\Models\SiteContent;

class DashboardController extends Controller
{
    public function index()
    {
        $sections = collect(config('cms.sections'))->map(function (array $definition, string $key) {
            $contents = SiteContent::forSection($key)->get();

            return [
                ...$definition,
                'key' => $key,
                'total' => $contents->count(),
                'published' => $contents->where('is_published', true)->count(),
                'drafts' => $contents->where('is_published', false)->count(),
                'updated_at' => optional($contents->sortByDesc('updated_at')->first())->updated_at,
            ];
        });

        $stats = [
            'total' => SiteContent::count(),
            'published' => SiteContent::published()->count(),
            'drafts' => SiteContent::where('is_published', false)->count(),
            'sections' => $sections->count(),
            'media' => MediaAsset::count(),
        ];

        $recent = SiteContent::latest()->take(6)->get();
        $audits = ContentAudit::latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recent', 'sections', 'audits'));
    }
}
