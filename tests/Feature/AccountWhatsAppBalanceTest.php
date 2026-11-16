<?php

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Carbon::setTestNow('2026-10-13 12:00:00');

    $this->user = User::create([
        'name' => 'Tester',
        'email' => 'account-whatsapp@example.test',
        'username' => 'account-whatsapp-tester',
        'password' => 'password',
        'account_type' => 'admin',
    ]);
    $this->user->givePermissionTo(Permission::create(['name' => 'view customers']));

    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    $subCategory = AccountSubCategory::create([
        'name' => 'Customer',
        'account_category_id' => $category->id,
    ]);

    $this->currencies = collect([
        ['name' => 'افغانی', 'code' => 'AFN'],
        ['name' => 'دالر', 'code' => 'USD'],
        ['name' => 'کلدار', 'code' => 'PKR'],
        ['name' => 'درهم', 'code' => 'AED'],
    ])->map(fn(array $currency) => Currency::create($currency + ['is_active' => true]));

    Setting::create([
        'company_name' => 'شرکت خدمات پولی امین الله قادری',
        'currency' => $this->currencies->first()->id,
    ]);

    $this->account = Account::create([
        'name' => 'حاجی جاوید لطیفی',
        'code' => '#000288',
        'contact' => '+93700123456',
        'address' => 'کابل',
        'account_sub_category_id' => $subCategory->id,
    ]);

    foreach ($this->currencies as $currency) {
        AccountBalance::create([
            'account_id' => $this->account->id,
            'currency_id' => $currency->id,
            'credit' => 0,
            'debit' => 0,
            'balance' => 0,
        ]);
    }
});

afterEach(function () {
    Carbon::setTestNow();
});

it('shows the address followed by the phone number in the fourth accounts-table column', function () {
    $response = $this->actingAs($this->user)->getJson(route('admin.accounts.fetch-accounts', [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]))->assertOk();

    $details = $response->json('data.0.address');

    expect($details)->toContain('کابل')
        ->toContain('+93700123456')
        ->and(strpos($details, 'کابل'))->toBeLessThan(strpos($details, '+93700123456'));
});

it('prepares the iconized account balance WhatsApp message for every active currency', function () {
    $response = $this->actingAs($this->user)
        ->getJson(route('admin.accounts.send-whatsapp', $this->account->id))
        ->assertOk();

    $url = $response->json('url');

    expect($url)->toStartWith('https://api.whatsapp.com/send/?')
        ->toContain('phone=93700123456')
        ->toContain('%F0%9F%93%85')
        ->toContain('%F0%9F%86%94')
        ->not->toContain('%EF%BF%BD');

    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query['text'])->toBe(
        "*شرکت خدمات پولی امین الله قادری*\n"
        . "\n"
        . "📅 تاریخ: ۲۱ - میزان - ۱۴۰۵\n"
        . "\u{200F}🆔 آیدی حساب: *\u{2066}0000{$this->account->id}\u{2069}*\n"
        . "👤 نام حساب: *حاجی جاوید لطیفی*\n\n"
        . "📊 *خلاصه بیلانس حساب*\n"
        . "━━━━━━━━━━━━\n"
        . "⚪ بیلانس افغانی: *0*\n"
        . "⚪ بیلانس دالر: *0*\n"
        . "⚪ بیلانس کلدار: *0*\n"
        . "⚪ بیلانس درهم: *0*\n"
        . "━━━━━━━━━━━━"
    )->not->toContain('?');
});
