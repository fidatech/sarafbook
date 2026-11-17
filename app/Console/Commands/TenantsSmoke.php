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
    protected $signature = 'tenants:smoke {tenant : Short name} {paths?* : Paths to open; default the pages of the menu}
        {--balances : Compare the stored balance of every account with the sum of its transactions}
        {--form=* : "METHOD path field=value&field=value" sent after the pages, inside one database transaction that is rolled back}';

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

        // forms: sent as the administrator and undone afterwards, so nothing stays in the books
        if ($this->option('form')) {
            $this->laravel->bind(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, fn () => new class
            {
                public function handle($request, $next)
                {
                    return $next($request);
                }
            });
            $tenancy->initialize($tenant);
            $db = \Illuminate\Support\Facades\DB::connection();
            $db->beginTransaction();
            try {
                foreach ($this->option('form') as $form) {
                    [$method, $path, $fields] = array_pad(preg_split('/\s+/', trim($form), 3), 3, '');
                    parse_str($fields, $data);
                    // {last} in a path is the id of the newest account, for example the one just added
                    $path = str_replace('{last}', (string) \App\Models\Account::query()->max('id'), $path);
                    $tenancy->initialize($tenant);
                    Auth::guard('web')->setUser($user);
                    $request = Request::create($tenant->url($path), strtoupper($method), $data);
                    $request->headers->set('referer', $tenant->url('admin/accounts'));
                    $response = $kernel->handle($request);
                    $status = $response->getStatusCode();
                    $session = $request->hasSession() ? $request->session() : null;
                    $flash = $session ? ($session->get('success') ?? $session->get('error')) : null;
                    $errors = $session?->get('errors')?->all() ?? [];
                    $e = $response->exception ?? null;
                    if ($status >= 400 || $errors) {
                        $failed++;
                    }
                    $this->line(sprintf('%s  %s /%s  %s%s%s', $status, strtoupper($method), $path,
                        $flash ? '"'.$flash.'"' : '',
                        $errors ? ' errors: '.implode(' | ', $errors) : '',
                        $e ? ' '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 200) : ''));
                    $kernel->terminate($request, $response);
                }
            } finally {
                $tenancy->initialize($tenant);
                \Illuminate\Support\Facades\DB::connection()->rollBack();
                $this->line('(the forms were undone)');
            }
        }

        if ($this->option('balances')) {
            $tenancy->initialize($tenant);
            $ledger = \Illuminate\Support\Facades\DB::table('transactions')->where('status', 'active')
                ->groupBy('account_id', 'currency_id')
                ->selectRaw("account_id, currency_id, ROUND(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END), 2) AS balance")
                ->get()->keyBy(fn ($r) => $r->account_id.'-'.$r->currency_id);
            $stored = \Illuminate\Support\Facades\DB::table('account_balances')->get(['account_id', 'currency_id', 'balance'])
                ->keyBy(fn ($r) => $r->account_id.'-'.$r->currency_id);
            $wrong = [];
            foreach ($ledger->keys()->merge($stored->keys())->unique() as $key) {
                $a = round((float) ($ledger[$key]->balance ?? 0), 2);
                $b = round((float) ($stored[$key]->balance ?? 0), 2);
                if (abs($a - $b) >= 0.01) {
                    $wrong[] = "account-currency {$key}: transactions {$a}, stored {$b}";
                }
            }
            $this->line('balances: '.$ledger->count().' account-currency pairs, '.count($wrong).' different');
            foreach (array_slice($wrong, 0, 15) as $line) {
                $this->line('  '.$line);
            }
            $failed += count($wrong) ? 1 : 0;
        }

        $this->line($failed ? "$failed page(s) failed." : 'Every page opened.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
