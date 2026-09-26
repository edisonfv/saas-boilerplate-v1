<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { ticketPriorityTone, ticketStatusTone } from '@/lib/support';
import type { TicketSummary } from '@/lib/support';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

interface Filters {
    search: string | null;
    status: string | null;
    priority: string | null;
    assignee: string | null;
    billing: string | null;
    overdue: string | null;
}

const props = defineProps<{
    tickets: {
        data: TicketSummary[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Filters;
    statuses: Record<string, string>;
    priorities: Record<string, string>;
    staff: { id: string; name: string }[];
    stats: {
        open: number;
        unassigned: number;
        mine: number;
        pendingBilling: number;
    };
    can: { create: boolean };
}>();

const { dateTime } = useDateTime();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'open');
const priority = ref(props.filters.priority ?? '');
const assignee = ref(props.filters.assignee ?? '');

/** Saved views: one click to the queues staff check every day. */
const views = computed(() => [
    {
        key: 'open',
        label: 'Abiertos',
        count: props.stats.open,
        params: { status: 'open' },
    },
    {
        key: 'mine',
        label: 'Mis tickets',
        count: props.stats.mine,
        params: { status: 'open', assignee: 'me' },
    },
    {
        key: 'unassigned',
        label: 'Sin asignar',
        count: props.stats.unassigned,
        params: { status: 'open', assignee: 'none' },
    },
    {
        key: 'overdue',
        label: 'SLA vencido',
        count: null,
        params: { overdue: '1' },
    },
    {
        key: 'billing',
        label: 'Por facturar',
        count: props.stats.pendingBilling,
        params: { status: 'all', billing: 'pending' },
    },
    { key: 'all', label: 'Todos', count: null, params: { status: 'all' } },
]);

const activeView = computed(() => {
    const f = props.filters;

    if (f.overdue) {
        return 'overdue';
    }

    if (f.billing) {
        return 'billing';
    }

    if (f.assignee === 'me') {
        return 'mine';
    }

    if (f.assignee === 'none') {
        return 'unassigned';
    }

    return f.status === 'all' ? 'all' : 'open';
});

function visit(params: Record<string, string | null | undefined>): void {
    const filter: Record<string, string> = {};

    for (const [key, value] of Object.entries(params)) {
        if (value && !(key === 'status' && value === 'all')) {
            filter[key] = value;
        }
    }

    // "status=all" is expressed as the absence of a status filter, except
    // that the server defaults to "open" — send an explicit empty status.
    router.get(
        central.support.tickets.index().url,
        {
            filter: {
                ...filter,
                ...(params.status === 'all' ? { status: '' } : {}),
            },
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function applyFilters(): void {
    visit({
        search: search.value,
        status: status.value,
        priority: priority.value,
        assignee: assignee.value,
    });
}

let debounce: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(applyFilters, 300);
});
</script>

<template>
    <Head title="Tickets de soporte" />

    <CentralLayout title="Tickets de soporte">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Mesa de ayuda
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Solicitudes de los clientes
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Atiende, asigna y da seguimiento a los tickets que
                        llegan desde los tenants, el formulario público y el
                        staff.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.support.tickets.create().url"
                    :class="ui.buttonPrimary"
                >
                    <Icon name="plus" class="size-4.5" />
                    Registrar ticket
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    label="Abiertos"
                    :value="stats.open"
                    icon="chat"
                    helper="En curso"
                />
                <StatCard
                    label="Sin asignar"
                    :value="stats.unassigned"
                    icon="users"
                    helper="Necesitan responsable"
                />
                <StatCard
                    label="Asignados a mí"
                    :value="stats.mine"
                    icon="user"
                    helper="Tu cola"
                />
                <StatCard
                    label="Por facturar"
                    :value="stats.pendingBilling"
                    icon="currency"
                    helper="Horas o monto fijo"
                />
            </div>

            <Card>
                <nav
                    class="-mx-5 -mt-5 mb-4 no-scrollbar flex gap-1 overflow-x-auto border-b border-ink-200 px-3 dark:border-ink-800"
                    aria-label="Vistas de tickets"
                >
                    <button
                        v-for="view in views"
                        :key="view.key"
                        type="button"
                        :aria-current="
                            activeView === view.key ? 'page' : undefined
                        "
                        :class="[
                            'relative shrink-0 px-3 py-3 text-sm font-semibold transition',
                            activeView === view.key
                                ? 'text-primary-700 dark:text-primary-300'
                                : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white',
                        ]"
                        @click="visit(view.params)"
                    >
                        {{ view.label }}
                        <span
                            v-if="view.count !== null"
                            class="ml-1 rounded-full bg-ink-100 px-1.5 text-xs text-ink-600 dark:bg-ink-800 dark:text-ink-300"
                        >
                            {{ view.count }}
                        </span>
                        <span
                            v-if="activeView === view.key"
                            class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary-600"
                        />
                    </button>
                </nav>

                <div
                    class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por código, asunto, email o tenant…"
                        :class="[ui.input, 'lg:col-span-2']"
                    />
                    <select
                        v-model="priority"
                        :class="ui.input"
                        @change="applyFilters"
                    >
                        <option value="">Todas las prioridades</option>
                        <option
                            v-for="(label, value) in priorities"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <select
                        v-model="assignee"
                        :class="ui.input"
                        @change="applyFilters"
                    >
                        <option value="">Cualquier responsable</option>
                        <option value="me">Asignados a mí</option>
                        <option value="none">Sin asignar</option>
                        <option
                            v-for="member in staff"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }}
                        </option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                            >
                                <th class="px-4 py-3">Ticket</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Prioridad</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Responsable</th>
                                <th class="px-4 py-3">Última actividad</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="ticket in props.tickets.data"
                                :key="ticket.id"
                                class="transition hover:bg-ink-50/80 dark:hover:bg-ink-800/40"
                            >
                                <td class="px-4 py-3.5">
                                    <Link
                                        :href="
                                            central.support.tickets.show(
                                                ticket.id,
                                            ).url
                                        "
                                        class="font-semibold text-ink-950 hover:text-primary-700 dark:text-white dark:hover:text-primary-300"
                                    >
                                        {{ ticket.subject }}
                                    </Link>
                                    <p
                                        class="mt-0.5 flex items-center gap-2 text-xs text-ink-500"
                                    >
                                        <span class="font-mono">{{
                                            ticket.code
                                        }}</span>
                                        · {{ ticket.category_label }} ·
                                        {{ ticket.channel_label }}
                                        <span
                                            v-if="ticket.is_overdue"
                                            class="rounded bg-red-50 px-1.5 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300"
                                        >
                                            SLA vencido
                                        </span>
                                    </p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p
                                        class="font-medium text-ink-800 dark:text-ink-200"
                                    >
                                        {{ ticket.tenant_name ?? 'Sin tenant' }}
                                    </p>
                                    <p class="text-xs text-ink-500">
                                        {{ ticket.requester_name }}
                                    </p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <Badge
                                        :tone="
                                            ticketPriorityTone(ticket.priority)
                                        "
                                    >
                                        {{ ticket.priority_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5">
                                    <Badge
                                        :tone="ticketStatusTone(ticket.status)"
                                    >
                                        {{ ticket.status_label }}
                                    </Badge>
                                </td>
                                <td
                                    class="px-4 py-3.5 text-ink-700 dark:text-ink-300"
                                >
                                    {{ ticket.assignee_name ?? '—' }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-ink-500 dark:text-ink-400"
                                >
                                    {{
                                        dateTime(
                                            ticket.last_activity_at ??
                                                ticket.created_at,
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr v-if="props.tickets.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay tickets en esta vista.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.tickets.links"
                    :from="props.tickets.from"
                    :to="props.tickets.to"
                    :total="props.tickets.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
