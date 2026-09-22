type BadgeTone = 'green' | 'amber' | 'red' | 'gray' | 'blue';

/**
 * Maps a Subscription status enum value (App\Enums\SubscriptionStatus) to a
 * Badge tone. Kept in sync by hand with the enum's cases.
 */
export function subscriptionStatusTone(
    status: string | null | undefined,
): BadgeTone {
    switch (status) {
        case 'Active':
            return 'green';
        case 'Trialing':
            return 'blue';
        case 'PastDue':
            return 'amber';
        case 'Cancelled':
        case 'Expired':
            return 'red';
        default:
            return 'gray';
    }
}
