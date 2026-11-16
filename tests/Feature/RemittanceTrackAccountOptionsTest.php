<?php

use Illuminate\Support\Facades\Blade;

it('limits remittance track options to subcategories one and two and excludes the current account', function (string $type) {
    $source = file_get_contents(resource_path('views/admin/accounts/show.blade.php'));
    preg_match('/<select name="track_account_id" id="'.$type.'_track_account_id".*?<\/select>/s', $source, $matches);
    expect($matches)->not->toBeEmpty();

    $account = (object) ['id' => 42, 'name' => 'Current track account', 'account_sub_category_id' => 1];
    $other = (object) ['id' => 7, 'name' => 'Other track account', 'account_sub_category_id' => 2];
    $allowed = (object) ['id' => 8, 'name' => 'Category one', 'account_sub_category_id' => 1];
    $excluded = (object) ['id' => 9, 'name' => 'Category three', 'account_sub_category_id' => 3];
    foreach ([collect([$other, $allowed, $excluded]), collect([$other, $allowed, $excluded, $account])] as $accounts) {
        $html = Blade::render($matches[0], compact('account', 'accounts'));
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($document);

        // Account-page remittances must select a different track account.
        expect($xpath->query('//option[@value="42"]')->length)->toBe(0)
            ->and($xpath->query('//option[@value="7"]')->length)->toBe(1)
            ->and($xpath->query('//option[@value="8"]')->length)->toBe(1)
            ->and($xpath->query('//option[@value="9"]')->length)->toBe(0)
            ->and($xpath->query('//option[@value=""]')->length)->toBe(1);
    }
})->with(['incoming', 'outgoing']);


it('limits the remittance creation track dropdown to subcategories one and two', function () {
    $source = file_get_contents(resource_path('views/admin/remittances/create.blade.php'));
    preg_match('/<select name="track_account_id".*?<\/select>/s', $source, $matches);
    expect($matches)->not->toBeEmpty();
    $accounts = collect([1, 2, 3, 4])->map(fn ($id) => (object) [
        'id' => $id, 'name' => 'Account '.$id, 'account_sub_category_id' => $id,
    ]);
    $html = Blade::render($matches[0], compact('accounts'));
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    foreach ([1, 2, 3, 4] as $id) {
        expect($xpath->query('//option[@value="'.$id.'"]')->length)->toBe($id <= 2 ? 1 : 0);
    }
});
