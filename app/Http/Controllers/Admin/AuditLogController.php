<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $users = \App\Models\User::select('id', 'name')->where('account_type', 'admin')->orderBy('name')->get();
        $models = Activity::query()->select('log_name')->distinct()->whereNotNull('log_name')->orderBy('log_name')->pluck('log_name');
        $filters = [
            'user_id' => $request->integer('user_id') ?: null,
            'model' => (string) $request->query('model', ''),
            'event' => (string) $request->query('event', ''),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? $request->query('date') : '',
            'q' => trim((string) $request->query('q', '')),
        ];

        $logs = Activity::query()->with('causer:id,name,username')
            ->when($filters['user_id'], fn ($q, $v) => $q->where('causer_id', $v))
            ->when($filters['model'] !== '', fn ($q) => $q->where('log_name', $filters['model']))
            ->when($filters['event'] !== '', fn ($q) => $q->where('event', $filters['event']))
            ->when($filters['date'] !== '', fn ($q) => $q->whereDate('created_at', $filters['date']))
            ->when($filters['q'] !== '', fn ($q) => $q->where('description', 'like', '%'.$filters['q'].'%'))
            ->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return page('admin.audit.index', compact('users', 'models', 'filters', 'logs'));
    }

    public function data(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('model')) {
            $query->where('log_name', $request->model);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return datatables()->of($query)
            ->addIndexColumn()
            ->editColumn('event', fn($r) => __('administration.events')[$r->event] ?? $r->event)
            ->editColumn('log_name', fn($r) => __('administration.audit_models')[$r->log_name] ?? $r->log_name)
            ->addColumn('user', fn($r) => $r->causer?->name ?? '-')
            ->addColumn('time', fn($r) => $r->created_at->locale(app()->getLocale())->diffForHumans())
            ->editColumn('properties', fn($r) => json_encode($r->properties))
            ->make(true);
    }
}
