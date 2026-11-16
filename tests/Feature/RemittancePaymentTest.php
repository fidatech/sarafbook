<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::create([
        'name' => 'Payment clerk', 'email' => 'payment@example.test', 'username' => 'payment-clerk',
        'password' => 'password', 'account_type' => 'admin',
    ]);
    Permission::findOrCreate('update remittances', 'web');
    $this->user->givePermissionTo('update remittances');
    $this->actingAs($this->user);
    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    foreach (['Customer', 'Saraf', 'Safe'] as $name) {
        AccountSubCategory::create(['name' => $name, 'account_category_id' => $category->id]);
    }
    foreach ([3, 2, 2, 3] as $i => $subcategory) {
        Account::create(['name' => 'Account '.$i, 'code' => '#'.$i, 'account_sub_category_id' => $subcategory]);
    }
    $currency = Currency::create(['name' => 'Dollar', 'code' => 'USD', 'is_active' => true]);
    Setting::create(['company_name' => 'Test Sarafi', 'currency' => $currency->id]);
    $this->remittance = Remittance::create([
        'date' => '2026-09-19', 'type' => 'incoming', 'remittance_mode' => 'no_track',
        'from_account_id' => 1, 'to_account_id' => 2,
        'hawala_number' => 'PAY-123', 'sender' => 'Sender name', 'receiver' => 'Receiver name',
        'currency_id' => $currency->id, 'amount' => 120, 'partner_currency_id' => $currency->id,
        'partner_amount' => 100, 'actual_currency_id' => $currency->id, 'actual_amount' => 100,
        'commission_mode' => 'none', 'status' => 'pending', 'created_by' => $this->user->id,
        'linked_id' => (string) Str::uuid(), 'description' => 'Payment test note',
    ]);
    $this->evidence = fn () => [
        'receiver_id_number' => 'ID-123',
        'receiver_id_copy' => UploadedFile::fake()->image('id.jpg'),
        'receiver_photo' => UploadedFile::fake()->image('photo.jpg'),
        'identity_confirmed' => '1',
    ];
});

it('renders the complete payment page in each language', function (string $locale) {
    app()->setLocale($locale);
    $this->withSession(['locale' => $locale])->get(route('admin.remittances.payment', $this->remittance))
        ->assertOk()->assertSee('lang="'.$locale.'"', false)->assertSee('PAY-123')->assertSee('Receiver name')->assertSee('Payment test note')
        ->assertSee('100.00 USD')->assertSee('120.00 USD')->assertSee('receiver_fingerprint')
        ->assertDontSee('remittance_payment.')->assertDontSee('ui.');
})->with(['en', 'fa', 'ps']);

it('requires identity confirmation on both payment endpoints', function (string $route) {
    $this->postJson(route($route, $this->remittance), [])
        ->assertUnprocessable()->assertJsonValidationErrors(['identity_confirmed'])
        ->assertJsonMissingValidationErrors(['receiver_id_copy', 'receiver_photo']);
    expect($this->remittance->fresh()->status)->toBe('pending');
    expect(Transaction::count())->toBe(0);
})->with(['admin.remittances.markPaid', 'admin.modal-remittances.markPaid']);

it('accepts optional evidence and preserves each supplied upload', function (string $route, ?string $field) {
    $data = ['identity_confirmed' => '1'];
    if ($field) {
        $data[$field] = UploadedFile::fake()->image('evidence.jpg');
    }
    $this->postJson(route($route, $this->remittance), $data)->assertOk();
    $r = $this->remittance->fresh();
    expect($r->status)->toBe('paid')->and($r->paid_by)->toBe($this->user->id)
        ->and(Transaction::count())->toBe(2);
    foreach (['receiver_id_copy', 'receiver_photo'] as $upload) {
        if ($upload === $field) {
            Storage::disk('local')->assertExists($r->{$upload.'_path'});
        } else {
            expect($r->{$upload.'_path'})->toBeNull();
        }
    }
    expect(Storage::disk('local')->allFiles())->toHaveCount($field ? 1 : 0);
})->with(['admin.remittances.markPaid', 'admin.modal-remittances.markPaid'])
    ->with([null, 'receiver_id_copy', 'receiver_photo']);

it('stores private evidence and posts the exact existing payout rows only once', function () {
    $this->post(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())
        ->assertRedirect(route('admin.remittances.payment-receipt', $this->remittance));
    $r = $this->remittance->fresh();
    expect($r->status)->toBe('paid')->and($r->paid_by)->toBe($this->user->id)
        ->and($r->paid_at)->not->toBeNull()->and($r->receiver_id_number)->toBe('ID-123');
    foreach (['receiver_id_copy_path', 'receiver_photo_path'] as $field) {
        Storage::disk('local')->assertExists($r->{$field});
        expect($r->toArray())->not->toHaveKey($field);
    }
    expect($r->receiver_fingerprint_path)->toBeNull();
    $rows = Transaction::where('linked_id', $r->linked_id.'-mark-paid')->orderBy('id')->get();
    expect($rows)->toHaveCount(2);
    expect($rows->map(fn ($t) => [(int) $t->account_id, $t->transaction_type, (float) $t->amount, (int) $t->currency_id, (int) $t->is_cash, (int) $t->table_row_id])->all())
        ->toBe([[4, 'debit', 100.0, 1, 0, $r->id], [1, 'debit', 100.0, 1, 1, $r->id]]);
    $this->get(route('admin.remittances.payment-evidence', [$r, 'photo']))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('admin.remittances.payment', $r))->assertOk()->assertSee('Payment clerk')->assertDontSee('id="payment-form"', false);
    $this->postJson(route('admin.modal-remittances.markPaid', $r), ($this->evidence)())->assertUnprocessable();
    expect(Transaction::count())->toBe(2)->and(Storage::disk('local')->allFiles())->toHaveCount(2);
    $this->putJson(route('admin.modal-remittances.update', $r), $r->toArray())->assertUnprocessable()->assertJsonValidationErrors('payment');
    $this->postJson(route('admin.remittances.reject', $r))->assertUnprocessable();
    $this->deleteJson(route('admin.modal-remittances.destroy', $r))->assertUnprocessable();
    expect($r->fresh()->status)->toBe('paid')->and(Transaction::count())->toBe(2);
});

it('denies access to payment and private evidence without permission', function () {
    $this->user->revokePermissionTo('update remittances');
    $this->get(route('admin.remittances.payment', $this->remittance))->assertForbidden();
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())->assertForbidden();
    $this->get(route('admin.remittances.payment-evidence', [$this->remittance, 'photo']))->assertForbidden();
    expect(Transaction::count())->toBe(0);
});

it('rejects invalid files and ineligible remittances without posting', function () {
    $data = ($this->evidence)();
    $data['receiver_photo'] = UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml');
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('receiver_photo');
    foreach (['paid', 'rejected'] as $status) {
        $this->remittance->update(['status' => $status]);
        $this->postJson(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())->assertUnprocessable();
    }
    $this->remittance->update(['status' => 'pending', 'type' => 'outgoing']);
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())->assertUnprocessable();
    expect(Transaction::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rolls back files and ledger changes if posting fails', function () {
    $attempts = 0;
    Transaction::creating(function () use (&$attempts) {
        if (++$attempts === 2) {
            throw new RuntimeException('Simulated ledger failure');
        }
    });
    $this->withoutExceptionHandling();
    try {
        $this->post(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)());
        $this->fail('The simulated failure was not raised.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated ledger failure');
    }
    expect($this->remittance->fresh()->status)->toBe('pending')
        ->and(Transaction::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('does not mark paid when payout configuration is missing', function () {
    $this->remittance->update(['partner_amount' => 0]);
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())
        ->assertUnprocessable()->assertJsonValidationErrors('payment');
    expect($this->remittance->fresh()->status)->toBe('pending')
        ->and(Transaction::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('keeps fingerprints disabled on the page and rejects new fingerprint uploads', function () {
    $response = $this->get(route('admin.remittances.payment', $this->remittance));
    $response->assertOk()->assertSee('id="receiver_fingerprint" disabled', false);
    $data = ($this->evidence)();
    $data['receiver_fingerprint'] = UploadedFile::fake()->image('fingerprint.png');
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('receiver_fingerprint');
    expect(Transaction::count())->toBe(0);
});

it('retains access to previously collected fingerprints', function () {
    Storage::disk('local')->put('remittance-payments/legacy.png', 'legacy evidence');
    $this->remittance->forceFill(['receiver_fingerprint_path' => 'remittance-payments/legacy.png', 'paid_at' => now(), 'status' => 'paid'])->save();
    $this->get(route('admin.remittances.payment-evidence', [$this->remittance, 'fingerprint']))->assertOk();
    $this->get(route('admin.remittances.payment', $this->remittance))->assertOk()
        ->assertSee(route('admin.remittances.payment-evidence', [$this->remittance, 'fingerprint']));
});

it('prints the paid amount and supports reprinting without posting again', function (string $locale) {
    $this->withSession(['locale' => $locale])->post(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())
        ->assertRedirect(route('admin.remittances.payment-receipt', $this->remittance));
    foreach ([1, 2] as $attempt) {
        $this->get(route('admin.remittances.payment-receipt', $this->remittance))
            ->assertOk()->assertSee('lang="'.$locale.'"', false)
            ->assertSee('100.00 USD')->assertDontSee('120.00 USD')
            ->assertSee('Receiver name')->assertSee('Sender name')->assertSee('Payment clerk')
            ->assertSee('remittance-payment-receipt.js')->assertDontSee('remittance_payment.')
            ->assertDontSee('receiver_photo_path')->assertDontSee('ID-123');
    }
    expect(Transaction::count())->toBe(2);
    $this->get(route('admin.remittances.payment', $this->remittance))->assertOk()
        ->assertSee(route('admin.remittances.payment-receipt', $this->remittance));
})->with(['en', 'fa', 'ps']);

it('does not provide a payment receipt for unpaid records or unauthorized users', function () {
    $this->get(route('admin.remittances.payment-receipt', $this->remittance))->assertNotFound();
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), [])->assertUnprocessable();
    $this->get(route('admin.remittances.payment-receipt', $this->remittance))->assertNotFound();
    $this->postJson(route('admin.remittances.markPaid', $this->remittance), ($this->evidence)())
        ->assertOk()->assertJsonPath('receipt_url', route('admin.remittances.payment-receipt', $this->remittance));
    $this->user->revokePermissionTo('update remittances');
    $this->get(route('admin.remittances.payment-receipt', $this->remittance))->assertForbidden();
});
