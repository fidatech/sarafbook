<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::match(['GET', 'POST'], '/numeric-input-test', function (Request $request) {
        $request->validate([
            'amount' => 'nullable|numeric',
            'exchange_rate' => 'nullable|numeric',
            'entries.*.amount' => 'nullable|numeric',
            'rates.*.rate' => 'nullable|numeric',
        ]);
        return response()->json($request->all());
    });
});

it('normalizes grouped financial values before validation for every request format', function (string $method) {
    $data = [
        'amount' => '1,234,567.50', 'exchange_rate' => '۱٬۲۳۴٫۱۲۳۴۵۶',
        'entries' => [['amount' => '-12,345.25'], ['amount' => '٩٬٨٧٦']],
        'rates' => [1 => ['rate' => '1,234.000001']],
        'phone' => '001,234', 'hawala_number' => '1,234', 'description' => 'Paid 1,234',
        'account_id' => '1234', 'commission_partner_amount' => '',
    ];
    $response = match ($method) {
        'json' => $this->postJson('/numeric-input-test', $data),
        'form' => $this->post('/numeric-input-test', $data),
        'query' => $this->get('/numeric-input-test?'.http_build_query($data)),
    };
    $response->assertOk()->assertJsonPath('amount', '1234567.50')
        ->assertJsonPath('exchange_rate', '1234.123456')
        ->assertJsonPath('entries.0.amount', '-12345.25')
        ->assertJsonPath('entries.1.amount', '9876')
        ->assertJsonPath('rates.1.rate', '1234.000001')
        ->assertJsonPath('phone', '001,234')->assertJsonPath('hawala_number', '1,234')
        ->assertJsonPath('description', 'Paid 1,234')->assertJsonPath('account_id', '1234')
        ->assertJsonPath('commission_partner_amount', null);
})->with(['json', 'form', 'query']);

it('rejects malformed grouped values instead of changing their meaning', function (string $amount) {
    $this->postJson('/numeric-input-test', ['amount' => $amount])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');
})->with(['12,34', '1,23,456', '1,234.5.6', '1,234abc', '1,,234']);

it('stores formatted exchange rates without commas on create and update', function () {
    $user = \App\Models\User::create([
        'name' => 'Rate clerk', 'email' => 'rates@example.test', 'username' => 'rate-clerk',
        'password' => 'password', 'account_type' => 'admin',
    ]);
    $this->actingAs($user);
    $from = \App\Models\Currency::create(['name' => 'Dollar', 'code' => 'USD', 'is_active' => true]);
    $to = \App\Models\Currency::create(['name' => 'Afghani', 'code' => 'AFN', 'is_active' => true]);
    $data = ['date' => '2026-09-23', 'from_currency_id' => $from->id, 'to_currency_id' => $to->id];
    foreach (['1,234.125' => 1234.125, '2,345.25' => 2345.25] as $formatted => $expected) {
        $this->postJson(route('admin.exchange-rates.store'), $data + ['rate' => $formatted])->assertOk();
        expect((float) \App\Models\ExchangeRate::first()->rate)->toBe($expected)
            ->and((float) $to->fresh()->exchange_rate)->toBe($expected)
            ->and(\App\Models\ExchangeRate::count())->toBe(1);
    }
});
