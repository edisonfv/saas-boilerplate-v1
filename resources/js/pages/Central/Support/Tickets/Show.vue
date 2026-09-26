<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import SlotPicker from '@/components/support/SlotPicker.vue';
import TicketConversation from '@/components/support/TicketConversation.vue';
import TicketReplyForm from '@/components/support/TicketReplyForm.vue';
import { useDateTime } from '@/composables/useDateTime';
import CentralLayout from '@/layouts/CentralLayout.vue';
import {
    appointmentStatusTone,
    formatMinutes,
    formatMoney,
    ticketPriorityTone,
    ticketStatusTone,
} from '@/lib/support';
import type {
    BookingSlot,
    TicketAppointment,
    TicketAttachment,
    TicketMessage,
} from '@/lib/support';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

interface StaffTicket {
    id: string;
    code: string;
    subject: string;
    description: string;
    status: string;
    status_label: string;
    priority: string;
    priority_label: string;
    category_label: string;
    channel_label: string;
    requester_name: string;
    requester_email: string;
    requester_company: string | null;
    tenant_id: string | null;
    tenant_name: string | null;
    module_name: string | null;
    assigned_to: string | null;
    assignee_name: string | null;
    covered_by_plan: boolean;
    billing_mode: string;
    billing_mode_label: string;
    billing_status_label: string;
    fixed_amount: string | null;
    billing_reason: string | null;
    invoice_reference: string | null;
    first_response_due_at: string | null;
    resolution_due_at: string | null;
    first_responded_at: string | null;
    resolved_at: string | null;
    resolver_name: string | null;
    is_first_response_overdue: boolean;
    is_resolution_overdue: boolean;
    is_open: boolean;
    created_at: string;
    attachments: TicketAttachment[];
    messages: TicketMessage[];
    time_entries: {
        id: string;
        staff_name: string | null;
        minutes: number;
        description: string;
        is_billable: boolean;
        worked_on: string;
    }[];
    total_minutes: number;
    billable_minutes: number;
    appointments: TicketAppointment[];
    rating: {
        stars: number;
        was_resolved: boolean;
        comment: string | null;
        staff_name: string | null;
    } | null;
    events: {
        id: string;
        type_label: string;
        actor_name: string;
        data: Record<string, unknown> | null;
        created_at: string;
    }[];
}

const props = defineProps<{
    ticket: StaffTicket;
    staff: { id: string; name: string }[];
    statuses: Record<string, string>;
    priorities: Record<string, string>;
    billingModes: Record<string, string>;
    tenants: { id: string; name: string }[];
    booking?: {
        types: { id: string; name: string; duration_minutes: number }[];
        type_id: string | null;
        date: string;
        dates: string[];
        slots: BookingSlot[];
    };
    can: { update: boolean; assign: boolean; billing: boolean };
}>();

const { dateTime, date, time } = useDateTime();
const isBooking = ref(false);
const isLoadingSlots = ref(false);
const selectedSlot = ref<string | null>(null);
const billingMode = ref(props.ticket.billing_mode);
const today = new Date().toISOString().slice(0, 10);

const patchOptions = { preserveScroll: true };

function update(
    action: 'status' | 'priority' | 'assignee' | 'tenant',
    value: string | null,
): void {
    const routes = {
        status: [
            central.support.tickets.status(props.ticket.id).url,
            { status: value },
        ],
        priority: [
            central.support.tickets.priority(props.ticket.id).url,
            { priority: value },
        ],
        assignee: [
            central.support.tickets.assignee(props.ticket.id).url,
            { assigned_to: value || null },
        ],
        tenant: [
            central.support.tickets.tenant(props.ticket.id).url,
            { tenant_id: value },
        ],
    } as const;

    const [url, data] = routes[action];
    router.patch(url, data, patchOptions);
}

function loadBooking(
    payload: { typeId: string | null; date: string | null } = {
        typeId: null,
        date: null,
    },
): void {
    isBooking.value = true;
    isLoadingSlots.value = true;
    router.reload({
        only: ['booking'],
        data: {
            ...(payload.typeId ? { type: payload.typeId } : {}),
            ...(payload.date ? { date: payload.date } : {}),
        },
        onFinish: () => (isLoadingSlots.value = false),
    });
}

function book(): void {
    if (!selectedSlot.value || !props.booking?.type_id) {
        return;
    }

    router.post(
        central.support.tickets.appointments.store(props.ticket.id).url,
        {
            support_attendance_type_id: props.booking.type_id,
            starts_at: selectedSlot.value,
        },
        {
            ...patchOptions,
            onSuccess: () => {
                isBooking.value = false;
                selectedSlot.value = null;
            },
        },
    );
}

function saveMeetingUrl(appointment: TicketAppointment, url: string): void {
    router.patch(
        central.support.appointments.update(appointment.id).url,
        { meeting_url: url || null },
        patchOptions,
    );
}

function finish(
    appointment: TicketAppointment,
    status: 'Completed' | 'NoShow',
): void {
    router.patch(
        central.support.appointments.finish(appointment.id).url,
        { status },
        patchOptions,
    );
}

function cancel(appointment: TicketAppointment): void {
    const reason = window.prompt(
        'Motivo de la cancelación (se envía al cliente):',
    );

    if (reason !== null) {
        router.delete(
            central.support.appointments.destroy(appointment.id).url,
            { data: { reason }, ...patchOptions },
        );
    }
}

const slaItems = computed(() => [
    {
        label: 'Primera respuesta',
        due: props.ticket.first_response_due_at,
        done: props.ticket.first_responded_at,
        overdue: props.ticket.is_first_response_overdue,
    },
    {
        label: 'Resolución',
        due: props.ticket.resolution_due_at,
        done: props.ticket.resolved_at,
        overdue: props.ticket.is_resolution_overdue,
    },
]);

function describeEvent(event: StaffTicket['events'][number]): string {
    const data = event.data ?? {};

    if (data.from && data.to) {
        return `${data.from} → ${data.to}`;
    }

    if (data.to) {
        return String(data.to);
    }

    if (data.minutes) {
        return (
            formatMinutes(Number(data.minutes)) +
            (data.billable ? ' facturable' : ' no facturable')
        );
    }

    if (data.stars) {
        return `${data.stars} ★${data.was_resolved === false ? ' · no resuelto' : ''}`;
    }

    if (data.technician) {
        return `Técnico: ${data.technician}`;
    }

    if (data.mode) {
        return `${data.mode} · ${data.status}`;
    }

    if (data.tenant) {
        return String(data.tenant);
    }

    if (data.status) {
        return String(data.status);
    }

    return '';
}
</script>

<template>
    <Head :title="`${ticket.code} · ${ticket.subject}`" />

    <CentralLayout :title="ticket.code">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge :tone="ticketStatusTone(ticket.status)">{{
                        ticket.status_label
                    }}</Badge>
                    <Badge :tone="ticketPriorityTone(ticket.priority)">{{
                        ticket.priority_label
                    }}</Badge>
                    <Badge tone="gray">{{ ticket.category_label }}</Badge>
                    <span class="text-xs text-ink-500"
                        >vía {{ ticket.channel_label }}</span
                    >
                </div>
                <h2
                    class="mt-3 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    {{ ticket.subject }}
                </h2>
                <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                    {{ ticket.requester_name }} &lt;{{
                        ticket.requester_email
                    }}&gt;
                    <template v-if="ticket.tenant_name">
                        · {{ ticket.tenant_name }}</template
                    >
                    <template v-if="ticket.module_name">
                        · Módulo {{ ticket.module_name }}</template
                    >
                </p>
            </div>

            <TicketConversation
                :description="ticket.description"
                :requester-name="ticket.requester_name"
                :created-at="ticket.created_at"
                :attachments="ticket.attachments"
                :messages="ticket.messages"
            />

            <TicketReplyForm
                v-if="can.update && ticket.status !== 'Closed'"
                :form="central.support.tickets.reply.form(ticket.id)"
                allow-internal
            />
            <p
                v-else-if="ticket.status === 'Closed'"
                class="rounded-xl border border-dashed border-ink-300 p-4 text-sm text-ink-500 dark:border-ink-700"
            >
                Ticket cerrado. Si el cliente necesita más ayuda, debe abrir una
                nueva solicitud.
            </p>

            <Card title="Sesiones agendadas">
                <div class="space-y-3">
                    <p
                        v-if="ticket.appointments.length === 0 && !isBooking"
                        class="text-sm text-ink-500 dark:text-ink-400"
                    >
                        No hay sesiones para este ticket.
                    </p>

                    <div
                        v-for="appointment in ticket.appointments"
                        :key="appointment.id"
                        class="rounded-xl border border-ink-200 p-4 dark:border-ink-800"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="font-semibold text-ink-950 capitalize dark:text-white"
                                >
                                    {{ date(appointment.starts_at) }} ·
                                    {{ time(appointment.starts_at) }}–{{
                                        time(appointment.ends_at)
                                    }}
                                </p>
                                <p class="text-sm text-ink-500">
                                    {{ appointment.type_name }} ·
                                    {{ appointment.technician_name }}
                                </p>
                                <p
                                    v-if="appointment.cancellation_reason"
                                    class="mt-1 text-xs text-ink-500"
                                >
                                    Motivo:
                                    {{ appointment.cancellation_reason }}
                                </p>
                            </div>
                            <Badge
                                :tone="
                                    appointmentStatusTone(appointment.status)
                                "
                                >{{ appointment.status_label }}</Badge
                            >
                        </div>

                        <div
                            v-if="
                                appointment.status === 'Scheduled' && can.update
                            "
                            class="mt-3 flex flex-col gap-2 sm:flex-row"
                        >
                            <input
                                :value="appointment.meeting_url ?? ''"
                                type="url"
                                placeholder="https://meet.google.com/…"
                                :class="ui.input"
                                aria-label="Enlace de la videollamada"
                                @change="
                                    saveMeetingUrl(
                                        appointment,
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                            <div class="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    :class="ui.buttonSecondary"
                                    @click="finish(appointment, 'Completed')"
                                >
                                    Atendida
                                </button>
                                <button
                                    type="button"
                                    :class="ui.buttonSecondary"
                                    @click="finish(appointment, 'NoShow')"
                                >
                                    No asistió
                                </button>
                                <button
                                    type="button"
                                    :class="ui.buttonDanger"
                                    @click="cancel(appointment)"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                        <a
                            v-else-if="appointment.meeting_url"
                            :href="appointment.meeting_url"
                            target="_blank"
                            rel="noopener"
                            :class="[ui.link, 'mt-2 inline-block text-sm']"
                        >
                            {{ appointment.meeting_url }}
                        </a>
                    </div>

                    <template v-if="can.update && ticket.is_open">
                        <button
                            v-if="!isBooking"
                            type="button"
                            :class="ui.buttonSecondary"
                            @click="loadBooking()"
                        >
                            <Icon name="calendar" class="size-4" />
                            Agendar sesión para el cliente
                        </button>

                        <div
                            v-else
                            class="rounded-xl border border-primary-200 bg-primary-50/40 p-4 dark:border-primary-500/30 dark:bg-primary-500/5"
                        >
                            <p
                                v-if="!ticket.covered_by_plan"
                                class="mb-4 text-sm text-accent-700 dark:text-accent-300"
                            >
                                Este tenant no tiene el módulo de Soporte: el
                                agendamiento no está disponible.
                            </p>
                            <SlotPicker
                                v-if="booking"
                                v-model="selectedSlot"
                                :types="booking.types"
                                :type-id="booking.type_id"
                                :dates="booking.dates"
                                :date="booking.date"
                                :slots="booking.slots"
                                :loading="isLoadingSlots"
                                @change="loadBooking"
                            />
                            <div class="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    :disabled="!selectedSlot"
                                    :class="ui.buttonPrimary"
                                    @click="book"
                                >
                                    Confirmar sesión
                                </button>
                                <button
                                    type="button"
                                    :class="ui.buttonSecondary"
                                    @click="isBooking = false"
                                >
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </Card>

            <Card title="Tiempo registrado">
                <div class="space-y-4">
                    <p class="text-sm text-ink-600 dark:text-ink-400">
                        Total {{ formatMinutes(ticket.total_minutes) }} ·
                        facturable {{ formatMinutes(ticket.billable_minutes) }}
                    </p>
                    <ul
                        v-if="ticket.time_entries.length"
                        class="divide-y divide-ink-100 dark:divide-ink-800"
                    >
                        <li
                            v-for="entry in ticket.time_entries"
                            :key="entry.id"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <div>
                                <p
                                    class="font-medium text-ink-800 dark:text-ink-200"
                                >
                                    {{ entry.description }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{ entry.staff_name }} ·
                                    {{ entry.worked_on }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="font-semibold text-ink-900 tabular-nums dark:text-white"
                                >
                                    {{ formatMinutes(entry.minutes) }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{
                                        entry.is_billable
                                            ? 'Facturable'
                                            : 'No facturable'
                                    }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <Form
                        autocomplete="off"
                        v-if="can.update"
                        v-bind="
                            central.support.tickets.timeEntries.store.form(
                                ticket.id,
                            )
                        "
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        #default="{ errors, processing }"
                        class="grid grid-cols-12 gap-3 border-t border-ink-100 pt-4 dark:border-ink-800"
                    >
                        <div class="col-span-6 sm:col-span-2">
                            <label for="minutes" :class="ui.label"
                                >Minutos</label
                            >
                            <input
                                id="minutes"
                                name="minutes"
                                type="number"
                                min="1"
                                max="1440"
                                required
                                :class="ui.input"
                            />
                            <p v-if="errors.minutes" :class="ui.error">
                                {{ errors.minutes }}
                            </p>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="worked_on" :class="ui.label"
                                >Fecha</label
                            >
                            <input
                                id="worked_on"
                                name="worked_on"
                                type="date"
                                :value="today"
                                :max="today"
                                required
                                :class="ui.input"
                            />
                        </div>
                        <div class="col-span-12 sm:col-span-7">
                            <label for="time-description" :class="ui.label"
                                >Trabajo realizado</label
                            >
                            <input
                                id="time-description"
                                name="description"
                                type="text"
                                required
                                :class="ui.input"
                            />
                            <p v-if="errors.description" :class="ui.error">
                                {{ errors.description }}
                            </p>
                        </div>
                        <label
                            class="col-span-12 inline-flex items-center gap-2 text-sm text-ink-700 sm:col-span-8 dark:text-ink-300"
                        >
                            <input type="hidden" name="is_billable" value="0" />
                            <input
                                type="checkbox"
                                name="is_billable"
                                value="1"
                                checked
                                :class="ui.checkbox"
                            />
                            Facturable (consume horas del plan)
                        </label>
                        <div class="col-span-12 sm:col-span-4 sm:text-right">
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="ui.buttonSecondary"
                            >
                                Registrar tiempo
                            </button>
                        </div>
                    </Form>
                </div>
            </Card>
        </div>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Gestión">
                <div class="space-y-4">
                    <div>
                        <label for="status" :class="ui.label">Estado</label>
                        <select
                            id="status"
                            :value="ticket.status"
                            :disabled="!can.update"
                            :class="ui.input"
                            @change="
                                update(
                                    'status',
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <option
                                v-for="(label, value) in statuses"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="priority" :class="ui.label"
                            >Prioridad</label
                        >
                        <select
                            id="priority"
                            :value="ticket.priority"
                            :disabled="!can.update"
                            :class="ui.input"
                            @change="
                                update(
                                    'priority',
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <option
                                v-for="(label, value) in priorities"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="assignee" :class="ui.label"
                            >Responsable</label
                        >
                        <select
                            id="assignee"
                            :value="ticket.assigned_to ?? ''"
                            :disabled="!can.assign"
                            :class="ui.input"
                            @change="
                                update(
                                    'assignee',
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <option value="">Sin asignar</option>
                            <option
                                v-for="member in staff"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }}
                            </option>
                        </select>
                    </div>
                    <p
                        v-if="ticket.resolver_name"
                        class="text-sm text-ink-600 dark:text-ink-400"
                    >
                        Resuelto por
                        <strong class="text-ink-900 dark:text-white">{{
                            ticket.resolver_name
                        }}</strong>
                    </p>
                </div>
            </Card>

            <Card v-if="!ticket.tenant_id" title="Asociar a un tenant">
                <p class="mb-3 text-sm text-ink-600 dark:text-ink-400">
                    Llegó por el formulario público{{
                        ticket.requester_company
                            ? ` (${ticket.requester_company})`
                            : ''
                    }}. Asócialo para aplicar su plan, SLA y facturación.
                </p>
                <select
                    :disabled="!can.update"
                    :class="ui.input"
                    @change="
                        update(
                            'tenant',
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option value="" selected disabled>Elegir tenant…</option>
                    <option
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        :value="tenant.id"
                    >
                        {{ tenant.name }}
                    </option>
                </select>
            </Card>

            <Card title="SLA">
                <p
                    v-if="!ticket.covered_by_plan"
                    class="text-sm text-ink-500 dark:text-ink-400"
                >
                    Sin SLA: el cliente no tiene el módulo de Soporte
                    contratado.
                </p>
                <ul v-else class="space-y-3">
                    <li
                        v-for="item in slaItems"
                        :key="item.label"
                        class="flex items-start justify-between gap-3 text-sm"
                    >
                        <span class="text-ink-600 dark:text-ink-400">{{
                            item.label
                        }}</span>
                        <span class="text-right">
                            <span
                                :class="[
                                    'block font-semibold',
                                    item.done
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : item.overdue
                                          ? 'text-red-600 dark:text-red-400'
                                          : 'text-ink-900 dark:text-white',
                                ]"
                            >
                                {{
                                    item.done
                                        ? 'Cumplido'
                                        : item.overdue
                                          ? 'Vencido'
                                          : 'En plazo'
                                }}
                            </span>
                            <span class="text-xs text-ink-500"
                                >Límite {{ dateTime(item.due) }}</span
                            >
                        </span>
                    </li>
                </ul>
            </Card>

            <Card title="Facturación">
                <Form
                    autocomplete="off"
                    v-if="can.billing"
                    v-bind="central.support.tickets.billing.form(ticket.id)"
                    :options="{ preserveScroll: true }"
                    #default="{ errors, processing }"
                    class="space-y-4"
                >
                    <div>
                        <label for="billing_mode" :class="ui.label"
                            >Modalidad</label
                        >
                        <select
                            id="billing_mode"
                            v-model="billingMode"
                            name="billing_mode"
                            :class="ui.input"
                        >
                            <option
                                v-for="(label, value) in billingModes"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <p :class="ui.help">
                            Estado: {{ ticket.billing_status_label }}
                        </p>
                    </div>
                    <div v-if="billingMode === 'Fixed'">
                        <label for="fixed_amount" :class="ui.label"
                            >Monto fijo</label
                        >
                        <input
                            id="fixed_amount"
                            name="fixed_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :value="ticket.fixed_amount ?? ''"
                            :class="ui.input"
                        />
                        <p v-if="errors.fixed_amount" :class="ui.error">
                            {{ errors.fixed_amount }}
                        </p>
                    </div>
                    <div>
                        <label for="billing_reason" :class="ui.label"
                            >Motivo</label
                        >
                        <textarea
                            id="billing_reason"
                            name="billing_reason"
                            rows="2"
                            required
                            :value="ticket.billing_reason ?? ''"
                            :class="ui.input"
                        />
                        <p v-if="errors.billing_reason" :class="ui.error">
                            {{ errors.billing_reason }}
                        </p>
                    </div>
                    <div>
                        <label for="invoice_reference" :class="ui.label"
                            >N.º de comprobante</label
                        >
                        <input
                            id="invoice_reference"
                            name="invoice_reference"
                            type="text"
                            :value="ticket.invoice_reference ?? ''"
                            placeholder="001-001-000000123"
                            :class="ui.input"
                        />
                        <p :class="ui.help">
                            Al registrarlo, el cobro queda como facturado.
                        </p>
                    </div>
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="[ui.buttonSecondary, 'w-full']"
                    >
                        Guardar facturación
                    </button>
                </Form>
                <dl v-else class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Modalidad</dt>
                        <dd class="font-semibold">
                            {{ ticket.billing_mode_label }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Estado</dt>
                        <dd class="font-semibold">
                            {{ ticket.billing_status_label }}
                        </dd>
                    </div>
                    <div
                        v-if="ticket.fixed_amount"
                        class="flex justify-between"
                    >
                        <dt class="text-ink-500">Monto</dt>
                        <dd class="font-semibold">
                            {{ formatMoney(ticket.fixed_amount) }}
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card v-if="ticket.rating" title="Satisfacción">
                <p
                    class="text-2xl tracking-widest text-accent-500"
                    :aria-label="`${ticket.rating.stars} de 5 estrellas`"
                >
                    {{ '★'.repeat(ticket.rating.stars)
                    }}<span class="text-ink-200 dark:text-ink-700">{{
                        '★'.repeat(5 - ticket.rating.stars)
                    }}</span>
                </p>
                <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                    {{
                        ticket.rating.was_resolved ? 'Resuelto' : 'No resuelto'
                    }}
                    · atendido por {{ ticket.rating.staff_name ?? '—' }}
                </p>
                <p
                    v-if="ticket.rating.comment"
                    class="mt-2 text-sm text-ink-800 italic dark:text-ink-200"
                >
                    “{{ ticket.rating.comment }}”
                </p>
            </Card>

            <Card title="Historial">
                <ol
                    class="relative space-y-4 border-l border-ink-200 pl-4 dark:border-ink-800"
                >
                    <li
                        v-for="event in ticket.events"
                        :key="event.id"
                        class="relative"
                    >
                        <span
                            class="absolute top-1.5 -left-[21px] size-2.5 rounded-full border-2 border-white bg-primary-500 dark:border-ink-900"
                        />
                        <p
                            class="text-sm font-semibold text-ink-900 dark:text-white"
                        >
                            {{ event.type_label }}
                        </p>
                        <p
                            v-if="describeEvent(event)"
                            class="text-sm text-ink-600 dark:text-ink-400"
                        >
                            {{ describeEvent(event) }}
                        </p>
                        <p class="text-xs text-ink-500">
                            {{ event.actor_name }} ·
                            {{ dateTime(event.created_at) }}
                        </p>
                    </li>
                </ol>
            </Card>

            <Link
                :href="central.support.tickets.index().url"
                :class="[ui.link, 'inline-flex items-center gap-1 text-sm']"
            >
                <Icon name="arrow-left" class="size-4" /> Volver a la bandeja
            </Link>
        </aside>
    </CentralLayout>
</template>
