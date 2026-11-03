<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('admin.audit.view'), 403);

        $query = AdminAuditLog::query()->with('actor');

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));

            $query->where(function ($inner) use ($term) {
                $inner->where('summary', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%");
            });
        }

        return view('admin.access.audit', [
            'logs' => $query->latest('created_at')->paginate(50)->withQueryString(),
            'actions' => AdminAuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
