<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Define modules and permissions
        $modules = [
            'dashboard' => ['view'],
            'customers' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'transactions' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'journal' => ['view', 'delete', 'export', 'print', 'send-whatsapp'],
            'expenses' => ['view', 'create', 'update', 'delete'],
            'exchanges' => ['view', 'create', 'update', 'delete'],
            'exchange rates' => ['view', 'create', 'update', 'delete'],
            'remittances' => ['view', 'create', 'update', 'delete'],
            'expense categories' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
            'currency reports' => ['view'],
            'creditors' => ['view'],
            'debtors' => ['view'],
            'users' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'create', 'update', 'delete'],
            'currencies' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'update'],
            'audit logs' => ['view'],
            'customers reports' => ['view'],
            'profit loss reports' => ['view'],
            'account categories' => ['view', 'create', 'update', 'delete'],
            'account sub categories' => ['view', 'create', 'update', 'delete'],
            'safe' => ['view'],
            'transfers' => ['view', 'create', 'update', 'delete'],
        ];

        // Create permissions per module and action
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$module}"]);
            }
        }

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'id' => 1,
                'name' => 'Administrator',
                'account_type' => 'admin',
                'created_by' => 1,
                'password' => Hash::make('admin@123'),
            ]
        );

        $admin->assignRole($adminRole);
        $user = User::find(1);
        $user->syncPermissions(Permission::all());


        Setting::create([
            'company_name' => 'Fida Technologies & Marketing Services',
            'logo' => 'logo.png',
            'contact' => '+93784006200',
            'email' => 'info@fida.af',
            'address' => 'Kabul, Afghanistan',
            'note_en' => 'Please check and confirm the sent balance.',
            'note_fa' => 'لطف نموده بیلانس را بررسي و از صحت آن اطمینان حاصل نمایید.',
            'note_ps' => 'مهرباني وکړئ د لېږدول شوي بیلانس د صحت په اړه خپل اطمینان حاصل کړئ.',
            'default_language' => 'en',
            'currency' => 1,
        ]);
    }
}
