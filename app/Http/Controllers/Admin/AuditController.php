<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('audit.view'), 403);

        $query = Activity::with(['causer', 'subject'])
            ->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $activities = $query->paginate(10)->withQueryString();

        $logNames = Activity::distinct()->pluck('log_name')->sort();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.audit.index', compact('activities', 'logNames', 'users'));
    }
}
