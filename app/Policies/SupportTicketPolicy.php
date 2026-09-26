<?php

namespace App\Policies;

use App\Models\CentralUser;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who can see a support ticket. Staff (CentralUser) need the central
 * permission; tenant users only see tickets of their own company — every
 * ticket with tenant.support-tickets.view-all (the tenant admin), otherwise
 * only the ones they opened. Tenant denials are 404 so ids can't be probed.
 */
class SupportTicketPolicy
{
    public function view(CentralUser|User $user, SupportTicket $ticket): Response
    {
        if ($user instanceof CentralUser) {
            return $user->can('central.support-tickets.view')
                ? Response::allow()
                : Response::deny();
        }

        return $this->belongsToTenantUser($user, $ticket)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Tenant users reply, rate and book on tickets they can see.
     */
    public function participate(User $user, SupportTicket $ticket): Response
    {
        return $this->belongsToTenantUser($user, $ticket)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function belongsToTenantUser(User $user, SupportTicket $ticket): bool
    {
        return $ticket->tenant_id !== null
            && $ticket->tenant_id === tenant()?->getTenantKey()
            && ($user->can('tenant.support-tickets.view-all')
                || $ticket->requester_tenant_user_id === (string) $user->getKey());
    }
}
