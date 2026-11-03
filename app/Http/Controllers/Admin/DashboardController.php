<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudit;
use App\Models\MediaAsset;
use App\Models\SiteContent;
use App\Support\AdEnquiries;

class DashboardController extends Controller
{
    public function __construct(private readonly AdEnquiries $adEnquiries) {}

    public function index()
    {
        $user = request()->user();

        if (! $user->canManageCms()) {
            if ($user->hasCaseAccess()) {
                return redirect()->route('admin.cases.index');
            }

            if ($user->hasAnyPermission('admin.users.view', 'admin.users.manage')) {
                return redirect()->route('admin.access.users.index');
            }

            if ($user->hasPermission('admin.roles.manage')) {
                return redirect()->route('admin.access.roles.index');
            }

            if ($user->hasPermission('admin.audit.view')) {
                return redirect()->route('admin.access.audit');
            }

            abort(403);
        }

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

        $enquiries = [
            'counts' => $this->adEnquiries->counts(),
            'pending' => $this->adEnquiries->latestPending(5),
        ];

        return view('admin.dashboard', compact('stats', 'recent', 'sections', 'audits', 'enquiries'));
    }
}
