<?php

namespace Modules\Central\Http\Controllers\Signatures;

use App\Enums\SalesPeriod;
use App\Http\Controllers\Controller;
use App\Models\SignatureAccount;
use App\Models\SignatureProduct;
use App\Models\SignatureProviderRequest;
use App\Services\Signatures\Money;
use App\Services\Signatures\SignatureSalesReport;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\Signatures\SignatureSalesReportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The business owner's view of signature resale: what distributors sell,
 * at what prices, what central earns over the provider's cost, the money
 * collected and owed, and the accounts that need attention.
 */
class SalesController extends Controller
{
    public function __construct(private SignatureSalesReport $report) {}

    public function index(SignatureSalesReportRequest $request): Response
    {
        [$from, $to] = $request->range();
        $tenantId = $request->tenantId();
        $productId = $request->productId();

        return Inertia::render('Central/Signatures/Sales/Index', [
            'filters' => [
                'period' => $request->period()->value,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'tenant' => $tenantId,
                'product' => $productId,
            ],
            'summary' => $this->report->summary($from, $to, $tenantId, $productId),
            'cash' => $this->report->cash($from, $to, $tenantId),
            'trend' => $this->report->monthlyTrend($to, 12, $tenantId, $productId),
            'tenants' => $this->report->byTenant($from, $to, $productId)
                ->when($tenantId, fn ($rows) => $rows->where('tenant_id', $tenantId)->values()),
            'products' => $this->report->byProduct($from, $to, $tenantId)
                ->when($productId, fn ($rows) => $rows->where('product_id', $productId)->values()),
            'alerts' => $this->report->alerts()
                ->when($tenantId, fn ($rows) => $rows->where('tenant_id', $tenantId)->values()),
            'periods' => SalesPeriod::toArray(),
            'tenantOptions' => SignatureAccount::query()->with('tenant:id,company_name')->get()
                ->map(fn (SignatureAccount $account) => [
                    'id' => $account->tenant_id,
                    'name' => $account->tenant?->company_name ?? $account->tenant_id,
                ])
                ->sortBy('name')
                ->values(),
            'productOptions' => SignatureProduct::query()->orderBy('credit_unit_price')->get(['id', 'name']),
        ]);
    }

    /**
     * The period's sales, one row per signature, for the accountant.
     */
    public function export(SignatureSalesReportRequest $request): StreamedResponse
    {
        [$from, $to] = $request->range();

        $sales = SignatureProviderRequest::query()
            ->counted()
            ->with(['tenant:id,company_name', 'product:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->when($request->tenantId(), fn ($query, string $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($request->productId(), fn ($query, string $productId) => $query->where('signature_product_id', $productId))
            ->orderBy('created_at');

        return response()->streamDownload(function () use ($sales) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens accents correctly.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Fecha', 'Distribuidor', 'Producto', 'Estado', 'Costo Uanataca', 'Precio al distribuidor',
                'Precio al público', 'Utilidad central', 'Utilidad distribuidor', 'Token proveedor',
            ]);

            $sales->lazy()->each(function (SignatureProviderRequest $sale) use ($output) {
                fputcsv($output, [
                    $sale->created_at?->format('Y-m-d H:i'),
                    $sale->tenant?->company_name ?? $sale->tenant_id,
                    $sale->product?->name,
                    $sale->status->label,
                    $sale->unit_cost,
                    $sale->unit_price,
                    $sale->sale_price,
                    $sale->unit_cost === null ? null : Money::fromCents(Money::toCents($sale->unit_price) - Money::toCents($sale->unit_cost)),
                    $sale->sale_price === null ? null : Money::fromCents(Money::toCents($sale->sale_price) - Money::toCents($sale->unit_price)),
                    $sale->provider_token,
                ]);
            });

            fclose($output);
        }, "ventas-firmas-{$from->toDateString()}-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
