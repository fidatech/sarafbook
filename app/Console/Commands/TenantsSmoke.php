<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Opens the main pages of a Sarafi as its administrator, inside this process (nothing is sent
 * over the network and nothing is saved), and reports which of them fail. Only GET pages.
 */
class TenantsSmoke extends Command
{
    protected $signature = 'tenants:smoke {tenant : Short name} {paths?* : Paths to open; default the pages of the menu}';

    protected $description = 'Open the main pages of a Sarafi as its administrator and report errors';

    protected array $default = [
        'admin/dashboard', 'admin/accounts', 'admin/safe', 'admin/transactions', 'admin/journal',
        'admin/exchanges/create', 'admin/exchanges', 'admin/exchange-rates',
        'admin/remittances/create-new', 'admin/remittances/outgoing', 'admin/remittances/incoming-paid', 'admin/remittances/pending-incoming',
        'admin/expenses', 'admin/reports/balance', 'admin/reports/customer-exchange-report', 'admin/reports/currency-exchange-report',
        'admin/reports/transactions', 'admin/reports/profit-loss', 'admin/reports/currency', 'admin/reports/aed',
        'admin/reports/customers', 'admin/reports/creditors', 'admin/reports/debtors',
        'admin/users', 'admin/roles', 'admin/permissions', 'admin/currencies', 'admin/expense-categories',
        'admin/account-categories', 'admin/account-sub-categories', 'admin/audit-logs', 'admin/settings', 'profile',
    ];

    public function handle(Tenancy $tenancy, Kernel $kernel): int
    {
        $tenant = Tenant::query()->with('domains', 'plan')->where('slug', $this->argument('tenant'))->first();
        if (! $tenant) {
            $this->error('Unknown Sarafi.');

            return self::FAILURE;
        }

        config(['session.driver' => 'array']);
        $tenancy->initialize($tenant);
        $user = User::query()->where('is_active', true)->orderBy('id')->first();
        if (! $user) {
            $this->error('This Sarafi has no active user.');

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($this->argument('paths') ?: $this->default as $path) {
            $tenancy->initialize($tenant);
            Auth::guard('web')->setUser($user);

            $request = Request::create($tenant->url($path), 'GET');
            $started = microtime(true);
            $response = $kernel->handle($request);
            $ms = (int) round((microtime(true) - $started) * 1000);
            $status = $response->getStatusCode();

            $note = '';
            if ($status >= 400) {
                $failed++;
                $e = $response->exception ?? null;
                if ($e) {
                    $frame = collect($e->getTrace())->first(fn ($t) => isset($t['file']) && ! str_contains($t['file'], '/vendor/'));
                    $where = str_contains($e->getFile(), '/vendor/') && $frame ? $frame['file'].':'.$frame['line'] : $e->getFile().':'.$e->getLine();
                    $note = class_basename($e).': '.mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 260).'  @ '.str_replace(base_path().'/', '', $where);
                }
            } elseif ($status >= 300) {
                $note = '-> '.str_replace($tenant->url(), '', (string) $response->headers->get('Location'));
            } else {
                $note = round(strlen((string) $response->getContent()) / 1024).' KB';
            }
            $this->line(sprintf('%s  %5d ms  /%s  %s', $status, $ms, $path, $note));
            $kernel->terminate($request, $response);
        }

        $this->line($failed ? "$failed page(s) failed." : 'Every page opened.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
