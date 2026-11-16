<?php

namespace App\Tenancy;

use App\Models\Central\Tenant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/** Creates, migrates and removes the database of a Sarafi. Used by the platform panel and the commands. */
class TenantProvisioner
{
    public function __construct(protected Tenancy $tenancy) {}

    /**
     * @param  array{slug:string,name:string,domain?:string,plan_id?:int|null,status?:string,expires_at?:mixed,
     *               admin_username?:string,admin_password?:string,admin_name?:string,existing_database?:bool,
     *               contact_name?:string,contact_phone?:string,locale?:string}  $data
     * @return array{0:Tenant,1:string,2:string} the tenant, the administrator's username and password
     */
    public function create(array $data): array
    {
        $slug = Str::of($data['slug'])->lower()->replaceMatches('/[^a-z0-9-]/', '')->trim('-')->toString();
        if ($slug === '' || strlen($slug) > 40) {
            throw new InvalidArgumentException('The short name may hold only a-z, 0-9 and "-" (at most 40 letters).');
        }
        if (Tenant::query()->where('slug', $slug)->exists()) {
            throw new InvalidArgumentException("A Sarafi with the short name [$slug] already exists.");
        }

        $database = config('tenancy.database_prefix').str_replace('-', '_', $slug);
        $existing = (bool) ($data['existing_database'] ?? false);
        $central = DB::connection('central');

        if (! $existing) {
            $central->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }

        $dbUser = null;
        $dbPassword = null;
        if (config('tenancy.create_database_user')) {
            $dbUser = substr($database, 0, 32);
            $dbPassword = Str::password(32, symbols: false);
            $host = config('tenancy.database_user_host', 'localhost');
            $central->statement("CREATE USER IF NOT EXISTS '{$dbUser}'@'{$host}' IDENTIFIED BY '{$dbPassword}'");
            $central->statement("GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$dbUser}'@'{$host}'");
        }

        $status = $data['status'] ?? 'trial';
        $tenant = Tenant::query()->create([
            'slug' => $slug,
            'name' => $data['name'],
            'database' => $database,
            'db_username' => $dbUser,
            'db_password' => $dbPassword,
            'plan_id' => $data['plan_id'] ?? null,
            'status' => $status,
            'trial_ends_at' => $status === 'trial' ? now()->addDays((int) config('tenancy.trial_days', 14)) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);
        $tenant->domains()->create([
            'domain' => $data['domain'] ?? ($slug.'.'.config('tenancy.base_domain')),
            'is_primary' => true,
        ]);
        $tenant->load('domains', 'plan');

        foreach (['app/private', 'app/public', 'public/images', 'public/attachments'] as $dir) {
            @mkdir($tenant->storagePath($dir), 0755, true);
        }

        $username = $data['admin_username'] ?? 'admin';
        $password = $data['admin_password'] ?? Str::password(12, symbols: false);

        try {
            $this->migrate($tenant);

            // A database brought from an existing installation keeps its own users and settings.
            if (! $existing) {
                $this->tenancy->run($tenant, function () use ($data, $username, $password) {
                    Artisan::call('db:seed', ['--class' => config('tenancy.seeder'), '--force' => true]);

                    $admin = User::query()->orderBy('id')->first();
                    if ($admin) {
                        $admin->forceFill([
                            'username' => $username,
                            'name' => $data['admin_name'] ?? $admin->name,
                            'password' => Hash::make($password),
                        ])->save();
                    }
                    $setting = Setting::query()->first();
                    if ($setting) {
                        $setting->update([
                            'company_name' => $data['name'],
                            'contact' => $data['contact_phone'] ?? null,
                            'email' => null,
                            'default_language' => $data['locale'] ?? config('app.locale'),
                        ]);
                    }
                });
            }
        } catch (\Throwable $e) {
            // leave nothing half-made behind, but never drop a database we did not create
            $this->tenancy->end();
            $tenant->delete();
            if (! $existing) {
                $central->statement('DROP DATABASE IF EXISTS `'.$database.'`');
            }
            throw new RuntimeException('The Sarafi could not be created: '.$e->getMessage(), 0, $e);
        }

        return [$tenant, $username, $existing ? '' : $password];
    }

    /** Core migrations, then the migrations of the tenant's own override layer, on its database only. */
    public function migrate(Tenant $tenant): string
    {
        return $this->tenancy->run($tenant, function (Tenant $tenant) {
            $paths = [database_path('migrations/tenant')];
            if (is_dir($tenant->overridesPath().'/migrations')) {
                $paths[] = $tenant->overridesPath().'/migrations';
            }
            Artisan::call('migrate', ['--database' => 'tenant', '--path' => $paths, '--realpath' => true, '--force' => true]);

            return Artisan::output();
        });
    }

    /** Remove a Sarafi. The database is dropped only when asked for explicitly. */
    public function delete(Tenant $tenant, bool $dropDatabase = false): void
    {
        $this->tenancy->end();
        $database = $tenant->database;
        $dbUser = $tenant->db_username;
        foreach ($tenant->domains as $domain) {
            $this->tenancy->forgetDomain($domain->domain);
        }
        $tenant->delete();

        if ($dropDatabase) {
            $central = DB::connection('central');
            $central->statement('DROP DATABASE IF EXISTS `'.$database.'`');
            if ($dbUser) {
                $host = config('tenancy.database_user_host', 'localhost');
                $central->statement("DROP USER IF EXISTS '{$dbUser}'@'{$host}'");
            }
        }
    }
}
