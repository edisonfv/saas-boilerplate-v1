<?php

namespace Modules\Signatures\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Signatures\SignatureSettlementReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\SignatureSettlementRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tenant's settlement of signature sales for a period: revenue vs. cost
 * per signature and per product, what it owes the platform or holds in
 * prepaid units, and a CSV export for its accounting.
 */
class SignatureSettlementController extends Controller
{
    public function __construct(private SignatureSettlementReport $report) {}

    public function index(SignatureSettlementRequest $request): Response
    {
        $report = $this->report->build($this->tenant(), $request->from(), $request->to());

        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $report['rows']->forPage($page, 20)->map(fn (array $row) => collect($row)->except(['revenue_cents', 'cost_cents', 'product_id'])->all())->values(),
            $report['rows']->count(),
            20,
            $page,
            ['path' => $request->url()],
        );
        $rows->withQueryString();

        return Inertia::render('Signatures/Settlement/Index', [
            ...collect($report)->except('rows')->all(),
            'rows' => $rows,
            'filters' => [
                'desde' => $report['period']['from'],
                'hasta' => $report['period']['to'],
            ],
        ]);
    }

    public function export(SignatureSettlementRequest $request): StreamedResponse
    {
        $report = $this->report->build($this->tenant(), $request->from(), $request->to());
        $filename = "liquidacion-firmas-{$report['period']['from']}-a-{$report['period']['to']}.csv";

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM + ";" so Excel (es-EC) opens it with accents and columns right.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Solicitud', 'Cliente', 'Identificación', 'Producto', 'Canal', 'Enviada', 'Estado', 'Reversada', 'Venta', 'Costo', 'Margen'], ';');

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['customer'],
                    $row['document_number'],
                    $row['product_name'],
                    $row['source_label'],
                    $row['submitted_at'],
                    $row['status_label'],
                    $row['is_reversed'] ? 'Sí' : 'No',
                    $row['revenue'],
                    $row['cost'],
                    $row['margin'],
                ], ';');
            }

            $summary = $report['summary'];
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Firmas liquidadas', $summary['sold']], ';');
            fputcsv($handle, ['Ventas', $summary['revenue']], ';');
            fputcsv($handle, ['Costo', $summary['cost']], ';');
            fputcsv($handle, ['Margen', $summary['margin']], ';');
            fputcsv($handle, ['Margen %', $summary['margin_percent'] ?? ''], ';');

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function tenant(): Tenant
    {
        /** @var Tenant */
        return tenant();
    }
}
