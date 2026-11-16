<?php

use App\Http\Controllers\Admin\RemittanceModalController;
use App\Models\Remittance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('continues outgoing track numbering across customers including blank and repeated legacy numbers', function () {
    for ($i = 1; $i <= 100; $i++) {
        Remittance::create([
            'date' => '2026-09-23', 'type' => 'outgoing', 'remittance_mode' => 'track',
            'from_account_id' => 1, 'to_account_id' => $i + 10, 'track_account_id' => 2,
            'track_hawala_number' => $i % 2 ? '1' : null,
            'hawala_number' => (string) $i, 'currency_id' => 1, 'amount' => 10,
            'created_by' => 1,
        ]);
    }

    $next = fn ($account, $type = 'outgoing', $column = 'track_hawala_number') =>
        app(RemittanceModalController::class)->nextHawala(Request::create('/', 'GET', [
            'account_id' => $account, 'type' => $type, 'column' => $column,
        ]))->getData(true)['number'];

    expect($next(2))->toBe(101)
        ->and($next(3))->toBe(1)
        ->and($next(2, 'incoming'))->toBe(1)
        ->and($next(11, 'outgoing', 'hawala_number'))->toBe(2);

    $row = Remittance::first();
    $row->update(['track_hawala_number' => '150']);
    expect($next(2))->toBe(151);

    // Other track accounts and incoming remittances have separate sequences.
    foreach ([['outgoing', 3], ['incoming', 2]] as [$type, $trackAccount]) {
        $other = $row->replicate();
        $other->type = $type;
        $other->track_account_id = $trackAccount;
        $other->track_hawala_number = '900';
        $other->save();
    }
    expect($next(2))->toBe(151);
});

it('shares the saraf sequence between direct outgoing and customer track outgoing remittances', function () {
    // Ghazni = 2, Ahmad = 3. Ghazni already has three direct outgoing remittances.
    for ($number = 1; $number <= 3; $number++) {
        Remittance::create([
            'date' => '2026-09-23', 'type' => 'outgoing', 'remittance_mode' => 'treasure',
            'from_account_id' => 1, 'to_account_id' => 2,
            'hawala_number' => (string) $number, 'currency_id' => 1,
            'amount' => 10, 'created_by' => 1,
        ]);
    }

    $assertNext = function ($expected) {
        foreach ([RemittanceModalController::class, \App\Http\Controllers\Admin\RemittanceController::class] as $controller) {
            foreach (['hawala_number', 'track_hawala_number'] as $column) {
                $response = app($controller)->nextHawala(Request::create('/', 'GET', [
                    'account_id' => 2, 'type' => 'outgoing', 'column' => $column,
                ]));
                expect($response->getData(true)['number'])->toBe($expected);
            }
        }
    };
    $assertNext(4);

    $tracked = Remittance::create([
        'date' => '2026-09-23', 'type' => 'outgoing', 'remittance_mode' => 'track',
        'from_account_id' => 1, 'to_account_id' => 3, 'track_account_id' => 2,
        // Ahmad's own number must not affect Ghazni's sequence.
        'hawala_number' => '999', 'track_hawala_number' => '4',
        'currency_id' => 1, 'amount' => 10, 'created_by' => 1,
    ]);
    $assertNext(5);
    $tracked->update(['track_hawala_number' => '150']);
    $assertNext(151);

    $unrelated = $tracked->replicate();
    $unrelated->to_account_id = 8;
    $unrelated->track_account_id = 9;
    $unrelated->save();
    $incoming = $tracked->replicate();
    $incoming->type = 'incoming';
    $incoming->track_hawala_number = '900';
    $incoming->save();
    $assertNext(151);
});
