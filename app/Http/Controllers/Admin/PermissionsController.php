<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PermissionsController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions:id,name')->withCount('users')->orderBy('id')->get();
        $permissions = Permission::orderBy('id')->get();
        $role = $roles->firstWhere('id', (int) request()->query('role')) ?? $roles->first();

        return view('admin.permissions.index', compact('roles', 'permissions', 'role'));
    }

    public function data(Request $request)
    {
        $permissions = Permission::select(['id', 'name']);

        return DataTables::of($permissions)
            ->addIndexColumn()
            ->editColumn('name', fn ($row) => \App\Support\AccessLabels::permission($row->name))
            ->addColumn('group', function ($row) {
                $parts = explode(' ', $row->name);
                return \App\Support\AccessLabels::group($parts[1] ?? 'general');
            })
            ->addColumn('actions', function ($row) {
                return '<button class="btn btn-sm btn-outline-warning edit-permission-btn me-1"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasAddPermission">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="' . route('admin.permissions.destroy', $row) . '" class="d-inline" onsubmit="return confirm(' . e(json_encode(__('ui.are_you_sure'))) . ')">'
                        . csrf_field() . method_field('DELETE') .
                        '<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name'
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', __('access.permission_created'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', __('access.permission_updated'));
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', __('access.permission_deleted'));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->syncPermissions($request->permissions ?? []);

        // the users of the role get what the role now allows
        foreach ($role->users as $member) {
            $member->syncPermissions($role->permissions);
        }

        return redirect()->route('admin.permissions.index', ['role' => $role->id])->with('success', __('access.role_permissions_updated', ['role' => \App\Support\AccessLabels::role($role->name)]));
    }

    public function rolePermissionsJson($roleId)
{
    $role = Role::findOrFail($roleId);
    return response()->json([
        'permissions' => $role->permissions->pluck('name')
    ]);
}



}
