<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import TicketConversation from '@/components/support/TicketConversation.vue';
import TicketRatingForm from '@/components/support/TicketRatingForm.vue';
import TicketReplyForm from '@/components/support/TicketReplyForm.vue';
import { useDateTime } from '@/composables/useDateTime';
import { appointmentStatusTone, ticketStatusTone } from '@/lib/support';
import type {
    TicketAppointment,
    TicketAttachment,
    TicketMessage,
} from '@/lib/support';
import { ui } from '@/lib/ui';
import type { RouteFormDefinition } from '@/wayfinder';

export interface RequesterTicket {
    id: string;
    code: string;
    subject: string;
    description: string;
    status: string;
    status_label: string;
    priority_label: string;
    category_label: string;
    requester_name: string;
    assignee_name: string | null;
    is_open: boolean;
    is_closed: boolean;
    created_at: string;
    resolved_at: string | null;
    attachments: TicketAttachment[];
    messages: TicketMessage[];
    appointments: TicketAppointment[];
    rating: {
        stars: number;
        was_resolved: boolean;
        comment: string | null;
    } | null;
    events: {
        id: string;
        type_label: string;
        data: Record<string, string>;
        created_at: string;
    }[];
}

/**
 * What the customer sees of a ticket, in their workspace or on the public
 * tracking page: conversation, reply box, sessions and the CSAT survey.
 */
const props = defineProps<{
    ticket: RequesterTicket;
    replyForm: RouteFormDefinition<'post'>;
    ratingForm?: RouteFormDefinition<'post'> | null;
    hiddenFields?: Record<string, string>;
    canReopen: boolean;
    bookHref?: string | null;
    cancelUrl?: ((appointment: TicketAppointment) => string) | null;
}>();

const { date, time } = useDateTime();

function cancel(appointment: TicketAppointment): void {
    if (props.cancelUrl && window.confirm('¿Cancelar esta sesión?')) {
        router.delete(props.cancelUrl(appointment), { preserveScroll: true });
    }
}
</script>

<template>
    <div class="col-span-12 grid grid-cols-12 gap-6">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge :tone="ticketStatusTone(ticket.status)">{{
                        ticket.status_label
                    }}</Badge>
                    <span class="font-mono text-xs text-ink-500">{{
                        ticket.code
                    }}</span>
                    <span class="text-xs text-ink-500"
                        >· {{ ticket.category_label }}</span
                    >
                </div>
                <h2
                    class="mt-3 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    {{ ticket.subject }}
                </h2>
                <p
                    v-if="ticket.assignee_name"
                    class="mt-1 text-sm text-ink-600 dark:text-ink-400"
                >
                    Te atiende
                    <strong class="text-ink-900 dark:text-white">{{
                        ticket.assignee_name
                    }}</strong>
                </p>
            </div>

            <TicketConversation
                :description="ticket.description"
                :requester-name="ticket.requester_name"
                :created-at="ticket.created_at"
                :attachments="ticket.attachments"
                :messages="ticket.messages"
            />

            <Card
                v-if="
                    ticket.status === 'Resolved' && !ticket.rating && ratingForm
                "
                title="¿Cómo te atendimos?"
            >
                <TicketRatingForm :form="ratingForm" />
            </Card>

            <TicketReplyForm
                v-if="ticket.is_open || canReopen"
                :form="replyForm"
                :hidden-fields="hiddenFields"
                :placeholder="
                    ticket.is_open
                        ? 'Escribe tu respuesta…'
                        : 'Si el problema continúa, escríbenos y reabriremos la solicitud…'
                "
            />
            <p
                v-else
                class="rounded-xl border border-dashed border-ink-300 p-4 text-sm text-ink-500 dark:border-ink-700"
            >
                Esta solicitud está cerrada. Si necesitas más ayuda, abre una
                nueva.
            </p>
        </div>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Sesiones con un técnico">
                <ul v-if="ticket.appointments.length" class="space-y-3">
                    <li
                        v-for="appointment in ticket.appointments"
                        :key="appointment.id"
                        class="rounded-lg border border-ink-200 p-3 dark:border-ink-800"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p
                                    class="text-sm font-semibold text-ink-900 capitalize dark:text-white"
                                >
                                    {{ date(appointment.starts_at) }}
                                </p>
                                <p
                                    class="text-sm text-ink-600 dark:text-ink-400"
                                >
                                    {{ time(appointment.starts_at) }}–{{
                                        time(appointment.ends_at)
                                    }}
                                    · {{ appointment.type_name }}
                                </p>
                            </div>
                            <Badge
                                :tone="
                                    appointmentStatusTone(appointment.status)
                                "
                                >{{ appointment.status_label }}</Badge
                            >
                        </div>
                        <a
                            v-if="
                                appointment.status === 'Scheduled' &&
                                appointment.meeting_url
                            "
                            :href="appointment.meeting_url"
                            target="_blank"
                            rel="noopener"
                            :class="[ui.buttonPrimary, 'mt-3 h-9 w-full']"
                        >
                            Unirse a la videollamada
                        </a>
                        <p
                            v-else-if="appointment.status === 'Scheduled'"
                            class="mt-2 text-xs text-ink-500"
                        >
                            El enlace de la videollamada aparecerá aquí antes de
                            la sesión.
                        </p>
                        <button
                            v-if="
                                appointment.status === 'Scheduled' && cancelUrl
                            "
                            type="button"
                            :class="[ui.buttonDanger, 'mt-2 h-8 px-2 text-xs']"
                            @click="cancel(appointment)"
                        >
                            Cancelar sesión
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm text-ink-500 dark:text-ink-400">
                    Aún no hay sesiones agendadas.
                </p>

                <Link
                    v-if="bookHref && ticket.is_open"
                    :href="bookHref"
                    :class="[ui.buttonSecondary, 'mt-4 w-full']"
                >
                    <Icon name="calendar" class="size-4" /> Agendar una sesión
                </Link>
            </Card>

            <Card v-if="ticket.rating" title="Tu calificación">
                <p class="text-2xl tracking-widest text-accent-500">
                    {{ '★'.repeat(ticket.rating.stars)
                    }}<span class="text-ink-200 dark:text-ink-700">{{
                        '★'.repeat(5 - ticket.rating.stars)
                    }}</span>
                </p>
                <p
                    v-if="ticket.rating.comment"
                    class="mt-2 text-sm text-ink-700 italic dark:text-ink-300"
                >
                    “{{ ticket.rating.comment }}”
                </p>
            </Card>

            <Card title="Seguimiento">
                <ol
                    class="relative space-y-3 border-l border-ink-200 pl-4 dark:border-ink-800"
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
                            {{ event.type_label
                            }}<template v-if="event.data.to"
                                >: {{ event.data.to }}</template
                            >
                        </p>
                        <p class="text-xs text-ink-500">
                            {{ date(event.created_at) }}
                            {{ time(event.created_at) }}
                        </p>
                    </li>
                </ol>
            </Card>
        </aside>
    </div>
</template>
