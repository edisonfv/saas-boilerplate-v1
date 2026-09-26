<?php

namespace Modules\Central\Http\Controllers\Support;

use App\Enums\TicketBillingStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\SupportRating;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportTicketManager;
use App\Services\Support\SupportUsage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Billing (hours used vs. included, pending charges per tenant) and service
 * quality (CSAT and first-response time per agent).
 */
class ReportController extends Controller
{
    public function index(Request $request, SupportUsage $usage): Response
    {
        $tenantIds = SupportTicket::query()->whereNotNull('tenant_id')->distinct()->pluck('tenant_id');

        $billing = Tenant::query()->whereIn('id', $tenantIds)->orderBy('company_name')->get()
            ->map(function (Tenant $tenant) use ($usage): array {
                $summary = $usage->forTenant($tenant);

                return [
                    'tenant_id' => $tenant->getTenantKey(),
                    'tenant_name' => $tenant->company_name ?? $tenant->getTenantKey(),
                    'period_start' => $summary['period_start']->toDateString(),
                    'period_end' => $summary['period_end']->toDateString(),
                    'included_minutes' => $summary['included_minutes'],
                    'used_minutes' => $summary['used_minutes'],
                    'excess_minutes' => $summary['excess_minutes'],
                    'hourly_minutes' => $summary['hourly_minutes'],
                    'fixed_amount' => $summary['fixed_amount'],
                    'amount_due' => $summary['amount_due'],
                    'currency' => $summary['currency'],
                ];
            })
            ->values();

        $pendingTickets = SupportTicket::query()
            ->where('billing_status', TicketBillingStatus::Pending()->value)
            ->with('tenant')
            ->withSum(['timeEntries as billable_minutes' => fn ($query) => $query->where('is_billable', true)], 'minutes')
            ->orderBy('created_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code(),
                'subject' => $ticket->subject,
                'tenant_name' => $ticket->tenant?->company_name ?? $ticket->tenant_id ?? $ticket->requester_company,
                'billing_mode_label' => $ticket->billing_mode->label,
                'fixed_amount' => $ticket->fixed_amount,
                'billable_minutes' => (int) $ticket->billable_minutes,
            ]);

        $agents = CentralUser::query()
            ->whereIn('id', SupportTicket::query()->whereNotNull('resolved_by')->distinct()->select('resolved_by'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (CentralUser $agent): array {
                $ratings = SupportRating::query()->where('central_user_id', $agent->id);
                $resolved = SupportTicket::query()->where('resolved_by', $agent->id);

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'resolved' => (clone $resolved)->count(),
                    'ratings' => (clone $ratings)->count(),
                    'average_stars' => round((float) (clone $ratings)->avg('stars'), 2),
                    'satisfied_percent' => $this->satisfiedPercent(clone $ratings),
                ];
            });

        return Inertia::render('Central/Support/Reports/Index', [
            'billing' => $billing,
            'pendingTickets' => $pendingTickets,
            'agents' => $agents,
            'overall' => [
                'ratings' => SupportRating::query()->count(),
                'average_stars' => round((float) SupportRating::query()->avg('stars'), 2),
                'satisfied_percent' => $this->satisfiedPercent(SupportRating::query()),
            ],
            'can' => [
                'billing' => $request->user()->can('central.support-tickets.billing'),
            ],
        ]);
    }

    /**
     * Marks per-ticket charges (hourly/fixed) as invoiced with a reference.
     */
    public function markInvoiced(Request $request, SupportTicketManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['uuid', Rule::exists(SupportTicket::class, 'id')->where('billing_status', TicketBillingStatus::Pending()->value)],
            'invoice_reference' => ['required', 'string', 'max:100'],
        ]);

        $actor = SupportActor::staff($request->user());

        SupportTicket::query()->whereIn('id', $validated['ticket_ids'])->each(
            fn (SupportTicket $ticket) => $manager->updateBilling(
                $ticket,
                $actor,
                $ticket->billing_mode,
                $ticket->fixed_amount,
                $ticket->billing_reason,
                $validated['invoice_reference'],
            ),
        );

        return back()->with('status', 'support-invoiced');
    }

    /**
     * Share of 4–5 star ratings (the usual CSAT definition).
     */
    /**
     * @param  Builder<SupportRating>  $ratings
     */
    private function satisfiedPercent(Builder $ratings): float
    {
        $total = (clone $ratings)->count();

        return $total === 0 ? 0.0 : round(((clone $ratings)->where('stars', '>=', 4)->count() / $total) * 100, 1);
    }
}
