<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions:id,name')->withCount('users')->orderBy('id')->get();
        $permissions = Permission::orderBy('id')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', __('access.role_created'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array'
        ]);

        // the administrator keeps its name, so the checks that look for it keep working
        if (strtolower($role->name) !== 'admin') {
            $role->update(['name' => $validated['name']]);
        }
        $role->syncPermissions($validated['permissions'] ?? []);
        // users of the role get what the role now allows (permissions are kept on each user too)
        foreach ($role->users as $member) {
            $member->syncPermissions($role->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', __('access.role_updated'));
    }

    public function destroy(Role $role)
    {
        if (strtolower($role->name) === 'admin') {
            return back()->with('error', __('The administrator role cannot be deleted.'));
        }
        if ($role->users()->exists()) {
            return back()->with('error', __('Users have this role. Give them another role first.'));
        }
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', __('access.role_deleted'));
    }
}
