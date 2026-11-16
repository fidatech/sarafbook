<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('renders a professional 80mm outgoing receipt with the destination Saraf address last', function () {
    Carbon::setTestNow('2026-11-03 15:30:00');

    $user = User::create([
        'name' => 'Tester', 'email' => 'receipt@example.test', 'username' => 'receipt-tester',
        'password' => 'password', 'account_type' => 'admin',
    ]);
    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    $subCategory = AccountSubCategory::create(['name' => 'Saraf', 'account_category_id' => $category->id]);
    $currency = Currency::create(['name' => 'افغانی', 'code' => 'AFN', 'is_active' => true]);

    Setting::create([
        'company_name' => 'شرکت صرافی و خدمات پولی بدخشان',
        'address' => 'کابل، شهر نو',
        'contact' => '0700000000',
        'currency' => $currency->id,
    ]);
    $source = Account::create([
        'name' => 'شعبه مرکزی', 'code' => '#SRC', 'address' => 'آدرس مبدأ',
        'account_sub_category_id' => $subCategory->id,
    ]);
    $destination = Account::create([
        'name' => 'صراف محمود', 'code' => '#DST', 'contact' => '0780642062',
        'address' => 'لغمان، مارکیت صرافی، منزل اول، دکان نمبر 469',
        'account_sub_category_id' => $subCategory->id,
    ]);
    $remittance = Remittance::create([
        'date' => '2026-11-03', 'type' => 'outgoing', 'from_account_id' => $source->id,
        'to_account_id' => $destination->id, 'hawala_number' => '5464',
        'sender' => 'یما', 'receiver' => 'جان آغا', 'currency_id' => $currency->id,
        'amount' => 1016800, 'remittance_mode' => 'treasure', 'commission_mode' => 'none',
        'status' => 'paid', 'description' => 'نقد', 'created_by' => $user->id,
    ]);

    $this->withSession(['locale' => 'fa'])
        ->actingAs($user)
        ->get(route('admin.remittances.print', $remittance))
        ->assertOk()
        ->assertSee('رسید حواله ارسالی')
        ->assertSee('کابل، شهر نو')
        ->assertSee('0700000000')
        ->assertSee('03:30 ب.ظ')
        ->assertSee('تاریخ چاپ')
        ->assertSee('5464')
        ->assertSee('1,016,800')
        ->assertSee('صراف محمود')
        ->assertSee('لغمان، مارکیت صرافی، منزل اول، دکان نمبر 469')
        ->assertSee('0780642062')
        ->assertSee('size:80mm auto', false)
        ->assertDontSee('آدرس مبدأ');

    $this->withSession(['locale' => 'en'])
        ->actingAs($user)
        ->get(route('admin.remittances.print', $remittance))
        ->assertOk()
        ->assertSee('03:30 PM');

    Carbon::setTestNow();
});
