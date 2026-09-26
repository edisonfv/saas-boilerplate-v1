<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { formatMinutes, ticketStatusTone } from '@/lib/support';
import type { TicketSummary } from '@/lib/support';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

const props = defineProps<{
    tickets: {
        data: TicketSummary[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    status: 'open' | 'closed';
    seesAll: boolean;
    usage: {
        included_minutes: number;
        used_minutes: number;
        period_end: string;
    };
    can: { create: boolean };
}>();

const { dateTime, date } = useDateTime();
const usedPercent = props.usage.included_minutes
    ? Math.min(
          100,
          Math.round(
              (props.usage.used_minutes / props.usage.included_minutes) * 100,
          ),
      )
    : 0;
</script>

<template>
    <Head title="Soporte" />

    <GeneralLayout title="Soporte">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Centro de ayuda
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        {{
                            seesAll
                                ? 'Solicitudes de la empresa'
                                : 'Mis solicitudes'
                        }}
                    </h2>
                </div>
                <Link
                    v-if="can.create"
                    :href="tenant.support.tickets.create().url"
                    :class="ui.buttonPrimary"
                >
                    <Icon name="plus" class="size-4.5" /> Nueva solicitud
                </Link>
            </div>

            <Card>
                <nav
                    class="-mx-5 -mt-5 mb-2 flex gap-1 border-b border-ink-200 px-3 dark:border-ink-800"
                >
                    <Link
                        v-for="tab in [
                            { key: 'open', label: 'En curso' },
                            { key: 'closed', label: 'Resueltas' },
                        ]"
                        :key="tab.key"
                        :href="
                            tenant.support.tickets.index({
                                query: { status: tab.key },
                            }).url
                        "
                        preserve-scroll
                        :class="[
                            'relative px-3 py-3 text-sm font-semibold',
                            status === tab.key
                                ? 'text-primary-700 dark:text-primary-300'
                                : 'text-ink-500 hover:text-ink-900 dark:hover:text-white',
                        ]"
                    >
                        {{ tab.label }}
                        <span
                            v-if="status === tab.key"
                            class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary-600"
                        />
                    </Link>
                </nav>

                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    <li v-for="ticket in props.tickets.data" :key="ticket.id">
                        <Link
                            :href="tenant.support.tickets.show(ticket.id).url"
                            class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3.5 transition hover:bg-ink-50 dark:hover:bg-ink-800/40"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ ticket.subject }}
                                </p>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    <span class="font-mono">{{
                                        ticket.code
                                    }}</span>
                                    · {{ ticket.category_label }}
                                    <template v-if="seesAll">
                                        · {{ ticket.requester_name }}</template
                                    >
                                    ·
                                    {{
                                        dateTime(
                                            ticket.last_activity_at ??
                                                ticket.created_at,
                                        )
                                    }}
                                </p>
                            </div>
                            <Badge :tone="ticketStatusTone(ticket.status)">{{
                                ticket.status_label
                            }}</Badge>
                            <Icon
                                name="chevron-right"
                                class="size-4 text-ink-400"
                            />
                        </Link>
                    </li>
                    <li
                        v-if="props.tickets.data.length === 0"
                        class="py-12 text-center"
                    >
                        <Icon name="chat" class="mx-auto size-8 text-ink-300" />
                        <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            {{
                                status === 'open'
                                    ? 'No tienes solicitudes en curso.'
                                    : 'Aún no hay solicitudes resueltas.'
                            }}
                        </p>
                    </li>
                </ul>

                <Pagination
                    class="mt-4"
                    :links="props.tickets.links"
                    :from="props.tickets.from"
                    :to="props.tickets.to"
                    :total="props.tickets.total"
                />
            </Card>
        </div>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Horas de soporte del ciclo">
                <p
                    class="text-3xl font-extrabold tracking-tight text-ink-950 tabular-nums dark:text-white"
                >
                    {{ formatMinutes(usage.used_minutes) }}
                    <span class="text-base font-semibold text-ink-500"
                        >de {{ formatMinutes(usage.included_minutes) }}</span
                    >
                </p>
                <div
                    class="mt-3 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"
                >
                    <div
                        :class="[
                            'h-full rounded-full',
                            usedPercent >= 100
                                ? 'bg-accent-500'
                                : 'bg-primary-500',
                        ]"
                        :style="{ width: `${usedPercent}%` }"
                    />
                </div>
                <p class="mt-2 text-xs text-ink-500">
                    Se renuevan el {{ date(`${usage.period_end}T12:00:00Z`) }}.
                    Las horas adicionales se facturan aparte.
                </p>
            </Card>

            <Card title="¿Necesitas hablar con un técnico?">
                <p class="text-sm text-ink-600 dark:text-ink-400">
                    Abre una solicitud y, desde su detalle, agenda una sesión en
                    el horario que prefieras.
                </p>
            </Card>
        </aside>
    </GeneralLayout>
</template>
