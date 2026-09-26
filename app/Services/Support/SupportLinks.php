<?php

namespace App\Services\Support;

use App\Enums\TicketChannel;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\URL;

/**
 * Builds links that appear in support emails. They must point at the right
 * host regardless of where the action happened: a reply sent from the
 * central console links a tenant user back to *their* workspace, while
 * guests always land on the central tracking page with their secret token.
 */
class SupportLinks
{
    /**
     * Absolute URL on the central domain for a named route.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function central(string $routeName, array $parameters = []): string
    {
        return $this->centralRoot().route($routeName, $parameters, absolute: false);
    }

    /**
     * Where the requester follows the conversation.
     */
    public function forRequester(SupportTicket $ticket): string
    {
        $tenantDomain = $ticket->channel->equals(TicketChannel::TenantPanel())
            ? $ticket->tenant?->domains()->value('domain')
            : null;

        if ($tenantDomain !== null) {
            return $this->scheme().'://'.$tenantDomain.route('tenant.support.tickets.show', $ticket, absolute: false);
        }

        return $this->central('support.public.tickets.show', [
            'ticket' => $ticket,
            'token' => $ticket->access_token,
        ]);
    }

    /**
     * Staff-side link to the ticket in the central console.
     */
    public function forStaff(SupportTicket $ticket): string
    {
        return $this->central('central.support.tickets.show', ['ticket' => $ticket]);
    }

    /**
     * Signed, expiring link to rate a resolved ticket (works without login).
     */
    public function rating(SupportTicket $ticket, int $validForDays): string
    {
        return $this->centralRoot().URL::temporarySignedRoute(
            'support.public.rating.show',
            now()->addDays($validForDays),
            ['ticket' => $ticket],
            absolute: false,
        );
    }

    private function centralRoot(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private function scheme(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
    }
}
