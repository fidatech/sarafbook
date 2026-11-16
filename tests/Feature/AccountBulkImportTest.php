<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\AccountBalance;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
    $this->actingAs(User::create([
        'name' => 'Import Tester',
        'email' => 'import@example.test',
        'username' => 'import-tester',
        'password' => 'password',
        'account_type' => 'admin',
    ]));
    $category = AccountCategory::create(['name' => 'Client', 'code_prefix' => '#']);
    AccountSubCategory::create(['name' => 'Customer', 'account_category_id' => $category->id]);
    AccountSubCategory::create(['name' => 'Saraf', 'account_category_id' => $category->id]);
    Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'is_active' => true]);
    Currency::create(['name' => 'Afghani', 'code' => 'AFN', 'is_active' => true]);
});

it('imports customer and saraf accounts with signed opening balances', function () {
    $csv = implode("\n", [
        'account_sub_category_id,name,code,phone,address,note,opening_USD,opening_AFN',
        '1,Ali Customer,#C100,0700000000,Kabul,VIP,125.50,-300',
        '2,Hamid Saraf,#S100,0799999999,Herat,Partner,0,500',
    ]);

    $response = $this->post(route('admin.accounts.bulk-import'), [
        'file' => UploadedFile::fake()->createWithContent('accounts.csv', $csv),
    ]);

    $response->assertSessionHasNoErrors()->assertSessionHas('success');
    expect(Account::whereIn('code', ['#C100', '#S100'])->count())->toBe(2);
    expect(Account::where('code', '#C100')->value('company'))->toBe('VIP');
    expect(Transaction::where('category', 'opening_balance')->count())->toBe(3);

    $customer = Account::where('code', '#C100')->firstOrFail();
    $usd = Currency::where('code', 'USD')->firstOrFail();
    $afn = Currency::where('code', 'AFN')->firstOrFail();
    expect((float) AccountBalance::whereBelongsTo($customer)->whereBelongsTo($usd)->value('balance'))->toBe(125.5)
        ->and((float) AccountBalance::whereBelongsTo($customer)->whereBelongsTo($afn)->value('balance'))->toBe(-300.0);
});

it('rejects the complete file when a code is duplicated', function () {
    Account::create([
        'name' => 'Existing', 'code' => '#EXISTING',
        'account_sub_category_id' => AccountSubCategory::where('name', 'Customer')->value('id'),
    ]);
    $csv = implode("\n", [
        'account_sub_category_id,name,code,phone,address,note,opening_USD',
        '1,Valid,#NEW,,,,10',
        '1,Duplicate,#EXISTING,,,,20',
    ]);

    $this->post(route('admin.accounts.bulk-import'), [
        'file' => UploadedFile::fake()->createWithContent('accounts.csv', $csv),
    ])->assertSessionHas('error');

    expect(Account::where('code', '#NEW')->exists())->toBeFalse();
});

it('downloads an excel template containing every active currency', function () {
    $response = $this->get(route('admin.accounts.bulk-import.template'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('accounts-opening-balances-template.xlsx');
    $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
    $sheet = $workbook->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('account_sub_category_id')
        ->and($sheet->getCell('A2')->getDataValidation()->getFormula1())->toBe('"1,2"');
});

it('auto-generates blank codes and skips blank or zero opening balances', function () {
    Account::create([
        'name' => 'Previous', 'code' => '#41',
        'account_sub_category_id' => 1,
    ]);
    $csv = implode("\n", [
        'account_sub_category_id,name,code,opening_USD,opening_AFN',
        '1,No Balance,,,0',
        '2,Has Balance,,25,',
    ]);

    $this->post(route('admin.accounts.bulk-import'), [
        'file' => UploadedFile::fake()->createWithContent('minimal.csv', $csv),
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $first = Account::where('name', 'No Balance')->firstOrFail();
    $second = Account::where('name', 'Has Balance')->firstOrFail();
    expect($first->code)->toBe('#42')
        ->and($second->code)->toBe('#43')
        ->and($first->transactions()->count())->toBe(0)
        ->and($second->transactions()->count())->toBe(1)
        ->and(AccountBalance::where('account_id', $first->id)->exists())->toBeFalse();
});
