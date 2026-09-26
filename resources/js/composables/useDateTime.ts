import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Formats ISO dates in the app timezone (shared as `timezone` by
 * HandleInertiaRequests), not the browser's: support hours, SLAs and
 * bookings are all defined in the app timezone.
 */
export function useDateTime() {
    const page = usePage();
    const timeZone = computed(
        () => (page.props.timezone as string | undefined) ?? 'UTC',
    );

    function format(
        iso: string | null | undefined,
        options: Intl.DateTimeFormatOptions,
    ): string {
        if (!iso) {
            return '—';
        }

        return new Intl.DateTimeFormat('es-EC', {
            timeZone: timeZone.value,
            ...options,
        }).format(new Date(iso));
    }

    return {
        timeZone,
        dateTime: (iso: string | null | undefined) =>
            format(iso, { dateStyle: 'medium', timeStyle: 'short' }),
        date: (iso: string | null | undefined) =>
            format(iso, { dateStyle: 'medium' }),
        longDate: (iso: string | null | undefined) =>
            format(iso, { weekday: 'long', day: 'numeric', month: 'long' }),
        time: (iso: string | null | undefined) =>
            format(iso, { hour: '2-digit', minute: '2-digit' }),
        /** "YYYY-MM-DD" of an ISO instant, in the app timezone. */
        dayKey: (iso: string) =>
            new Intl.DateTimeFormat('en-CA', {
                timeZone: timeZone.value,
            }).format(new Date(iso)),
    };
}
