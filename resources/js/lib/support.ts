type BadgeTone = 'green' | 'amber' | 'red' | 'gray' | 'blue';

/**
 * Badge tones for App\Enums\TicketStatus values. Kept in sync by hand.
 */
export function ticketStatusTone(status: string): BadgeTone {
    switch (status) {
        case 'New':
            return 'amber';
        case 'Open':
        case 'InProgress':
            return 'blue';
        case 'WaitingOnCustomer':
            return 'gray';
        case 'Resolved':
        case 'Closed':
            return 'green';
        default:
            return 'gray';
    }
}

/**
 * Badge tones for App\Enums\TicketPriority values.
 */
export function ticketPriorityTone(priority: string): BadgeTone {
    switch (priority) {
        case 'Urgent':
            return 'red';
        case 'High':
            return 'amber';
        case 'Low':
            return 'gray';
        default:
            return 'blue';
    }
}

/**
 * Badge tones for App\Enums\AppointmentStatus values.
 */
export function appointmentStatusTone(status: string): BadgeTone {
    switch (status) {
        case 'Scheduled':
            return 'blue';
        case 'Completed':
            return 'green';
        case 'NoShow':
            return 'amber';
        default:
            return 'gray';
    }
}

/** 135 → "2 h 15 min". */
export function formatMinutes(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} min`;
    }

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

export function formatMoney(amount: number | string, currency = 'USD'): string {
    return new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency,
    }).format(Number(amount));
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    return bytes < 1024 * 1024
        ? `${(bytes / 1024).toFixed(0)} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

export const weekdays: Record<number, string> = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo',
};

export const shortWeekdays: Record<number, string> = {
    1: 'Lun',
    2: 'Mar',
    3: 'Mié',
    4: 'Jue',
    5: 'Vie',
    6: 'Sáb',
    7: 'Dom',
};

/** A time range on an ISO weekday (1 = Monday), "HH:mm" in the app timezone. */
export interface WeeklyWindow {
    weekday: number;
    start: string;
    end: string;
}

/**
 * "Lun–Vie 08:00–17:00 · Sáb 09:00–13:00": consecutive days with the same
 * ranges are collapsed so a whole week reads in one line.
 */
export function summarizeWeek(windows: WeeklyWindow[]): string {
    const rangesByDay = new Map<number, string>();

    for (let day = 1; day <= 7; day++) {
        const ranges = windows
            .filter((window) => window.weekday === day)
            .sort((a, b) => a.start.localeCompare(b.start))
            .map((window) => `${window.start}–${window.end}`)
            .join(', ');

        if (ranges) {
            rangesByDay.set(day, ranges);
        }
    }

    const parts: string[] = [];
    let day = 1;

    while (day <= 7) {
        const ranges = rangesByDay.get(day);

        if (!ranges) {
            day++;
            continue;
        }

        let last = day;

        while (last < 7 && rangesByDay.get(last + 1) === ranges) {
            last++;
        }

        const days =
            last === day
                ? shortWeekdays[day]
                : `${shortWeekdays[day]}–${shortWeekdays[last]}`;
        parts.push(`${days} ${ranges}`);
        day = last + 1;
    }

    return parts.length > 0 ? parts.join(' · ') : 'Sin horario definido';
}

export interface TicketAttachment {
    id: string;
    name: string;
    size: number;
    url: string;
}

export interface TicketMessage {
    id: string;
    author_type: 'Staff' | 'Requester' | 'System';
    author_name: string;
    body: string;
    is_internal: boolean;
    created_at: string;
    attachments: TicketAttachment[];
}

export interface TicketAppointment {
    id: string;
    type_name: string;
    starts_at: string;
    ends_at: string;
    status: string;
    status_label: string;
    meeting_url: string | null;
    technician_name: string | null;
    cancellation_reason: string | null;
}

export interface TicketSummary {
    id: string;
    code: string;
    subject: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    category_label: string;
    requester_name: string;
    requester_email: string;
    tenant_id: string | null;
    tenant_name: string | null;
    assignee_name: string | null;
    channel_label: string;
    billing_mode: string;
    billing_mode_label: string;
    billing_status: string;
    billing_status_label: string;
    is_overdue: boolean;
    created_at: string;
    last_activity_at: string | null;
}

export interface BookingSlot {
    starts_at: string;
    available: number;
}
