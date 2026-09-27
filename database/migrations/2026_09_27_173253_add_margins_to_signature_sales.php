<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Economics of the resale chain (Uanataca → central → distributor →
     * end customer), so central can see what it earns and what its
     * distributors sell:
     *
     * - products: `provider_cost` (what Uanataca charges central per
     *   signature) and `min_retail_price` (the floor distributors may not
     *   sell below, to protect the channel);
     * - each sale snapshots its `unit_cost`, `unit_price` (what the
     *   distributor paid central for that unit) and `sale_price` (what the
     *   end customer paid the distributor — an amount, never personal data).
     */
    public function up(): void
    {
        Schema::table('signature_products', function (Blueprint $table) {
            $table->decimal('provider_cost', 10, 2)->nullable()->after('container');
            $table->decimal('min_retail_price', 10, 2)->nullable()->after('suggested_retail_price');
        });

        Schema::table('signature_provider_requests', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('consumption_entry_id');
            $table->decimal('unit_price', 10, 2)->nullable()->after('unit_cost');
            $table->decimal('sale_price', 10, 2)->nullable()->after('unit_price');

            $table->index('created_at');
        });

        $this->backfillUnitPrices();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_provider_requests', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['unit_cost', 'unit_price', 'sale_price']);
        });

        Schema::table('signature_products', function (Blueprint $table) {
            $table->dropColumn(['provider_cost', 'min_retail_price']);
        });
    }

    /**
     * Existing sales: credit sales carry their price in the consumption
     * entry; prepaid ones get the average price the distributor paid for
     * that product's packages. Retail prices live in each tenant's database
     * and stay unknown for past sales.
     */
    private function backfillUnitPrices(): void
    {
        DB::table('signature_provider_requests as sale')
            ->leftJoin('signature_ledger_entries as consumption', 'consumption.id', '=', 'sale.consumption_entry_id')
            ->select(['sale.id', 'sale.signature_account_id', 'sale.signature_product_id', 'consumption.amount'])
            ->orderBy('sale.id')
            ->each(function (object $sale) {
                $price = (float) $sale->amount !== 0.0 ? $sale->amount : $this->averagePackagePrice($sale);

                if ($price !== null) {
                    DB::table('signature_provider_requests')->where('id', $sale->id)->update(['unit_price' => $price]);
                }
            });
    }

    private function averagePackagePrice(object $sale): ?string
    {
        $totals = DB::table('signature_ledger_entries')
            ->where('signature_account_id', $sale->signature_account_id)
            ->where('signature_product_id', $sale->signature_product_id)
            ->where('type', 'PackagePurchase')
            ->selectRaw('sum(amount) as amount, sum(units) as units')
            ->first();

        return $totals !== null && (int) $totals->units > 0
            ? number_format((float) $totals->amount / (int) $totals->units, 2, '.', '')
            : null;
    }
};
