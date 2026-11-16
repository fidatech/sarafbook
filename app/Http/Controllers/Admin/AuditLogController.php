<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::select('id', 'name')->get();
        return view('admin.audit.index', compact('users'));
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
