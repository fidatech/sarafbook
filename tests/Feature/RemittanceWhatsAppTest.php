<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function remittanceWhatsAppMessage($test, array $overrides = []): string
{
    $user = User::create([
        'name' => 'Tester',
        'email' => 'whatsapp@example.test',
        'username' => 'whatsapp-tester',
        'password' => 'password',
        'account_type' => 'admin',
    ]);

    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    $subCategory = AccountSubCategory::create([
        'name' => 'Saraf',
        'account_category_id' => $category->id,
    ]);

    $currency = Currency::create([
        'name' => 'دالر',
        'code' => 'USD',
        'is_active' => true,
    ]);
    $commissionCurrency = Currency::create([
        'name' => 'افغانی',
        'code' => 'AFN',
        'is_active' => true,
    ]);

    $account = Account::create([
        'name' => 'حاجی لعل محمد مرادی',
        'code' => '#KUNDUZ',
        'contact' => '+93700123456',
        'address' => 'کندز',
        'account_sub_category_id' => $subCategory->id,
    ]);

    Setting::create([
        'company_name' => 'شرکت صرافی و خدمات پولی بدخشان',
        'currency' => $currency->id,
    ]);

    $data = array_replace([
        'date' => '2026-11-05',
        'type' => 'outgoing',
        'to_account_id' => $account->id,
        'hawala_number' => '270',
        'sender' => 'حمدالله',
        'receiver' => 'حاجی جاوید',
        'currency_id' => $currency->id,
        'amount' => 500,
        'remittance_mode' => 'treasure',
        'commission_mode' => 'none',
        'commission_currency_id' => null,
        'commission_kabul_amount' => 0,
        'commission_partner_amount' => 0,
        'status' => 'paid',
        'created_by' => $user->id,
    ], $overrides);

    if ($data['commission_mode'] !== 'none' && !$data['commission_currency_id']) {
        $data['commission_currency_id'] = $commissionCurrency->id;
    }

    $remittance = Remittance::create($data);

    $response = $test->actingAs($user)
        ->getJson(route('admin.remittances.send-whatsapp', $remittance))
        ->assertOk();

    parse_str(parse_url($response->json('url'), PHP_URL_QUERY), $query);

    return $query['text'];
}

it('prepares the outgoing remittance WhatsApp message in the requested Dari format', function () {
    $this->withSession(['locale' => 'fa']);

    expect(remittanceWhatsAppMessage($this))->toBe(
        "*شرکت صرافی و خدمات پولی بدخشان*\n"
        . "👤 حساب : حاجی لعل محمد مرادی - کندز\n"
        . "📅 تاریخ : ۱۴ - عقرب - ۱۴۰۵\n\n"
        . "🔢 نمبر حواله : 270\n"
        . "🔰 فرستنده  : حمدالله\n"
        . "✅ گیرنده     : حاجی جاوید\n"
        . "📗 معلومات : بدون کمیشن\n\n"
        . "🟢 مبلغ : *500 دالر*\n"
        . "💬 به حروف : *پنج صد دالر*"
    );
});

it('shows the total commission amount and commission currency name', function () {
    $message = remittanceWhatsAppMessage($this, [
        'commission_mode' => 'company',
        'commission_kabul_amount' => 25,
    ]);

    expect($message)->toContain('📗 معلومات : 25 افغانی');
});
