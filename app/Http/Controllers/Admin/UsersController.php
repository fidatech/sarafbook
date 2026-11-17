<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UsersController extends Controller
{

    public function index(Request $request)
    {
        $roles = Role::orderBy('id')->get();
        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $status = in_array($request->query('status'), ['active', 'off'], true) ? $request->query('status') : '';

        $users = User::query()->with('roles:id,name')->withCount('permissions')
            ->where('account_type', 'admin')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->when($status !== '', fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')->orderBy('id')
            ->paginate(25)->withQueryString();

        return page('admin.users.index', [
            'users' => $users, 'roles' => $roles,
            'filters' => ['q' => $search, 'role' => $role, 'status' => $status],
            'maxUsers' => tenant()?->maxUsers(),
            'userCount' => User::where('account_type', 'admin')->count(),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
            'repeat_password' => 'required|same:password',
            'role' => 'required|exists:roles,name',
        ]);

        // the plan of the Sarafi may limit the number of users
        $maxUsers = tenant()?->maxUsers();
        if ($maxUsers && User::count() >= $maxUsers) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'username' => __('Your plan allows :count users. Ask for a larger plan to add more.', ['count' => $maxUsers]),
            ]);
        }

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->account_type = 'admin';
        $user->created_by = auth()->id();
        $user->password = Hash::make($request->password);
        $user->is_active = $request->boolean('is_active');
        $user->save();

        // Assign selected role
        $role = Role::where('name', $request->role)->firstOrFail();
        $user->assignRole($role);

        // Sync permissions from role
        $user->syncPermissions($role->permissions);

        return redirect()->back()->with('success', __('access.user_created'));
    }


    public function show(string $id)
    {
        $user = User::with('permissions', 'roles.permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('id')->get();

        return view('admin.users.show', compact('user', 'allPermissions'));
    }

    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'user' => $user,
            'role' => $user->roles->first()?->name,
        ]);
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'role' => 'required|exists:roles,name',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->is_active = $request->boolean('is_active');
        $user->save();

        // Sync role
        $newRole = Role::where('name', $request->role)->firstOrFail();
        $user->syncRoles([$newRole]);

        // Sync permissions automatically from role
        $user->syncPermissions($newRole->permissions);

        return done(__('access.user_updated'));
    }



    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the currently authenticated user
        if (auth()->id() == $user->id) {
            return done(__('access.own_account'), null, false, 403);
        }

        // Check for protected roles
        $protectedRoles = ['admin', 'superadmin'];

        foreach ($protectedRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();

                if ($role && $role->users()->count() <= 1) {
                    return done(__('access.last_role_user', ['role' => \App\Support\AccessLabels::role($roleName)]), null, false, 403);
                }
            }
        }

        $user->delete();

        return done(__('access.user_deleted'));
    }

    public function updateStatus(string $id)
    {
        $user = User::find($id);

        if ($user && $user->id === auth()->id()) {
            return back()->with('error', __('You cannot switch off your own account.'));
        }
        if ($user) {
            $user->is_active = $user->is_active ? 0 : 1;
            $user->save();

            return redirect()->back()->with('success', __('access.status_updated'));
        }

        return redirect()->back()->with('error', __('access.user_missing'));
    }

    public function updatePassword(Request $request, string $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ]);
        $user = User::find($id);
        if ($user) {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return redirect()->back()->with('success', __('access.password_updated'));
        }

        return redirect()->back()->with('error', __('access.user_missing'));
    }

    public function assignPermissions(Request $request, string $id)
    {
        $user = User::find($id);
        $permission = Permission::findOrFail($request->permission_id);

        if ($request->action === 'assign') {
            $user->givePermissionTo($permission);
            return response()->json(['message' => __('access.permission_assigned')]);
        }

        if ($request->action === 'revoke') {
            $user->revokePermissionTo($permission);
            return response()->json(['message' => __('access.permission_revoked')]);
        }

        return response()->json(['message' => __('access.invalid_action')], 400);
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', __('access.permissions_updated'));
    }

    public function fetchUsers(Request $request)
    {
        $users = User::withCount('permissions');

        if ($request->filled('account_type')) {
            $users->where('account_type', $request->account_type);
        } else {
            $users->where('account_type', 'admin');
        }

        // Filter by search term (name/email)
        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $users->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Sort users by creation date
        $users->orderBy('created_at', 'asc');

        return DataTables::of($users)
            ->addColumn('name_with_profile', function ($user) {
                $initials = strtoupper(substr($user->name, 0, 1));
                $bgColor = $user->profile_bg ?? '#6c757d';
                $userUrl = route('admin.users.show', $user->id);
                return '
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background-color: ' . $bgColor . ';
                        color: white;
                        font-weight: bold;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font-size: 18px;">
                        ' . $initials . '
                    </div>
                    <div>
                        <a href="' . $userUrl . '" style="font-weight: bold; color: inherit;">
                            ' . e($user->name) . '
                        </a>
                    </div>
                </div>';
            })
            ->addColumn('actions', function ($user) {
                $auth = auth()->user(); // logged-in user

                $url = route('admin.users.show', $user->id);

                $actions = '<div class="d-flex align-items-center gap-2">';

                // VIEW (show) button
                if ($auth->can('view users')) {
                    $actions .= '
                        <a href="' . $url . '" class="btn btn-icon btn-outline-dribbble waves-effect">
                            <i class="bi bi-gear"></i>
                        </a>
                    ';
                }

                // EDIT button
                if ($auth->can('edit users')) {
                    $actions .= '
                        <button class="btn btn-icon btn-outline-primary edit-user-btn" data-id="' . $user->id . '">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    ';
                }

                // DELETE button
                if ($auth->can('delete users') && $user->id !== $auth->id) {
                    $actions .= '
                        <button class="btn btn-icon btn-outline-danger delete-user-btn" data-id="' . $user->id . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $actions .= '</div>';

                return $actions;
            })
            ->addColumn('account_type', function ($user) {
                $role = $user->roles->first() ? $user->roles->first()->name : __('access.unknown');
                return '<span class="badge rounded-pill bg-info"><i class="bi bi-person-workspace me-2"></i>' . e(\App\Support\AccessLabels::role($role)) . '</span>';
            })
            ->addColumn('is_active', function ($user) {
                if ($user->is_active) {
                    return '<span class="badge rounded-pill bg-success"><i class="bi bi-check-circle-fill me-2"></i>' . __('ui.active') . '</span>';
                } else {
                    return '<span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle-fill me-2"></i>' . __('ui.deactivated') . '</span>';
                }
            })
            ->addColumn('created_at', function ($user) {
                return '<span title="' . $user->created_at->locale(app()->getLocale())->translatedFormat('F j, Y') . '">' . $user->created_at->locale(app()->getLocale())->translatedFormat('F j, Y') . '</span>';
            })
            ->addColumn('total_permissions', function ($user) {
                return '<span class="badge rounded-pill bg-danger">' . __('access.permission_count', ['count' => $user->permissions_count]) . '</span>';
            })
            ->rawColumns(['name_with_profile', 'actions', 'account_type', 'is_active', 'created_at', 'total_permissions'])
            ->make(true);
    }
}
