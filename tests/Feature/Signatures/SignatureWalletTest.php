<?php

use App\Enums\SignatureLedgerEntryType;
use App\Exceptions\SignatureQuotaExceeded;
use App\Models\SignaturePackage;
use App\Services\Signatures\SignatureWallet;

beforeEach(function () {
    seedSignatures();
    $this->wallet = app(SignatureWallet::class);
});

afterEach(function () {
    tenancy()->end();
});

test('a prepaid tenant sells only the units it bought', function () {
    [$tenant] = signatureTenant('Prepaid');
    $account = $tenant->signatureAccount;
    $product = signatureProduct();
    $package = SignaturePackage::query()->where('signature_product_id', $product->id)->where('quantity', 10)->sole();

    expect(fn () => $this->wallet->consume($account, $product, 'FE-000001'))
        ->toThrow(SignatureQuotaExceeded::class);

    $purchase = $this->wallet->purchasePackage($account, $package, 'FAC-001');

    expect($purchase->type->equals(SignatureLedgerEntryType::PackagePurchase()))->toBeTrue()
        ->and($purchase->units)->toBe(10)
        ->and($purchase->amount)->toBe('130.00')
        ->and($this->wallet->sellableUnits($account, $product))->toBe(10);

    foreach (range(1, 10) as $sale) {
        $this->wallet->consume($account, $product, "FE-{$sale}");
    }

    expect($this->wallet->sellableUnits($account, $product))->toBe(0)
        ->and(fn () => $this->wallet->consume($account, $product, 'FE-11'))->toThrow(SignatureQuotaExceeded::class)
        // Units are per product: another validity has its own balance.
        ->and($this->wallet->sellableUnits($account, signatureProduct('TwoYears')))->toBe(0);
});

test('a credit tenant sells until its credit line is used and payments free it up', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 40);
    $account = $tenant->signatureAccount;
    $product = signatureProduct(); // $15 per signature on credit

    expect($this->wallet->sellableUnits($account, $product))->toBe(2);

    $this->wallet->consume($account, $product, 'FE-1');
    $this->wallet->consume($account, $product, 'FE-2');

    expect($account->fresh()->credit_used)->toBe('30.00')
        ->and(fn () => $this->wallet->consume($account, $product, 'FE-3'))
        ->toThrow(SignatureQuotaExceeded::class, 'Tu cupo de crédito disponible ($10.00)');

    $this->wallet->recordPayment($account, 30, 'Transferencia 123');

    expect($account->fresh()->credit_used)->toBe('0.00')
        ->and($this->wallet->sellableUnits($account->fresh(), $product))->toBe(2);
});

test('packages only apply to prepaid accounts', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);

    $this->wallet->purchasePackage($tenant->signatureAccount, SignaturePackage::query()->first());
})->throws(DomainException::class, 'prepago');

test('an inactive account cannot sell', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $tenant->signatureAccount->update(['is_active' => false]);

    $this->wallet->assertCanSell($tenant->signatureAccount->fresh(), signatureProduct());
})->throws(SignatureQuotaExceeded::class, 'suspendida');

test('a refund gives the unit back exactly once', function () {
    [$tenant] = signatureTenant('Prepaid');
    $account = $tenant->signatureAccount;
    $product = signatureProduct();
    $this->wallet->adjustUnits($account, $product, 1, 'Cortesía');

    $consumption = $this->wallet->consume($account, $product, 'FE-1');
    expect($this->wallet->sellableUnits($account, $product))->toBe(0);

    $refund = $this->wallet->refund($consumption, 'Rechazada');

    expect($refund->units)->toBe(1)
        ->and($refund->reverses_entry_id)->toBe($consumption->id)
        ->and($this->wallet->refund($consumption, 'Otra vez'))->toBeNull()
        ->and($this->wallet->sellableUnits($account, $product))->toBe(1);
});

test('a refund on credit returns the charged amount', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    $account = $tenant->signatureAccount;

    $consumption = $this->wallet->consume($account, signatureProduct(), 'FE-1');
    $this->wallet->refund($consumption, 'Cancelada');

    expect($account->fresh()->credit_used)->toBe('0.00')
        ->and($account->ledgerEntries()->count())->toBe(2);
});

test('manual adjustments cannot leave a negative balance', function () {
    [$tenant] = signatureTenant('Prepaid');

    $this->wallet->adjustUnits($tenant->signatureAccount, signatureProduct(), -1, 'Corrección');
})->throws(DomainException::class, 'negativo');
