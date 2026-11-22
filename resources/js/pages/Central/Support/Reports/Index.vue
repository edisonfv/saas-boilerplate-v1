<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/components/Card.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { formatMinutes, formatMoney } from '@/lib/support';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

interface BillingRow {
    tenant_id: string;
    tenant_name: string;
    period_start: string;
    period_end: string;
    included_minutes: number;
    used_minutes: number;
    excess_minutes: number;
    hourly_minutes: number;
    fixed_amount: number;
    amount_due: number;
    currency: string;
}

defineProps<{
    billing: Paginated<BillingRow>;
    totalDue: number;
    pendingTickets: Paginated<{
        id: string;
        code: string;
        subject: string;
        tenant_name: string | null;
        billing_mode_label: string;
        fixed_amount: string | null;
        billable_minutes: number;
    }>;
    agents: Paginated<{
        id: string;
        name: string;
        resolved: number;
        ratings: number;
        average_stars: number;
        satisfied_percent: number;
    }>;
    overall: {
        ratings: number;
        average_stars: number;
        satisfied_percent: number;
    };
    can: { billing: boolean };
}>();

const selected = ref<string[]>([]);
const invoiceReference = ref('');

function usagePercent(row: BillingRow): number {
    return row.included_minutes === 0
        ? 0
        : Math.min(
              100,
              Math.round((row.used_minutes / row.included_minutes) * 100),
          );
}

function markInvoiced(): void {
    router.post(
        central.support.reports.invoiced().url,
        {
            ticket_ids: selected.value,
            invoice_reference: invoiceReference.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                invoiceReference.value = '';
            },
        },
    );
}
</script>

<template>
    <Head title="Reportes de soporte" />

    <CentralLayout title="Reportes de soporte">
        <div class="space-y-6">
            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Soporte
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Facturación y satisfacción
                </h2>
                <p
                    class="mt-2 max-w-3xl text-sm text-ink-600 dark:text-ink-400"
                >
                    Horas consumidas en el ciclo vigente de cada tenant, cobros
                    pendientes por ticket y la calidad de la atención (CSAT) por
                    agente.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    label="Por cobrar (ciclo actual)"
                    :value="formatMoney(totalDue)"
                    icon="currency"
                    helper="Excedentes + por horas + fijos"
                />
                <StatCard
                    label="Tickets por facturar"
                    :value="pendingTickets.total"
                    icon="tag"
                    helper="Por horas o monto fijo"
                />
                <StatCard
                    label="CSAT"
                    :value="`${overall.satisfied_percent}%`"
                    icon="star"
                    helper="Calificaciones de 4–5 ★"
                />
                <StatCard
                    label="Promedio"
                    :value="`${overall.average_stars} ★`"
                    icon="chart"
                    :helper="`${overall.ratings} calificaciones`"
                />
            </div>

            <Card title="Horas de soporte por tenant">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                            >
                                <th class="px-4 py-3">Tenant</th>
                                <th class="px-4 py-3">Ciclo</th>
                                <th class="px-4 py-3">Horas del plan</th>
                                <th class="px-4 py-3 text-right">Excedente</th>
                                <th class="px-4 py-3 text-right">Por horas</th>
                                <th class="px-4 py-3 text-right">Fijos</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="row in billing.data"
                                :key="row.tenant_id"
                            >
                                <td
                                    class="px-4 py-3 font-semibold text-ink-900 dark:text-white"
                                >
                                    {{ row.tenant_name }}
                                </td>
                                <td class="px-4 py-3 text-xs text-ink-500">
                                    {{ row.period_start }} →
                                    {{ row.period_end }}
                                </td>
                                <td class="px-4 py-3">
                                    <p
                                        class="text-xs text-ink-600 dark:text-ink-400"
                                    >
                                        {{ formatMinutes(row.used_minutes) }} de
                                        {{
                                            formatMinutes(row.included_minutes)
                                        }}
                                    </p>
                                    <div
                                        class="mt-1 h-1.5 w-40 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"
                                    >
                                        <div
                                            :class="[
                                                'h-full rounded-full',
                                                row.excess_minutes > 0
                                                    ? 'bg-accent-500'
                                                    : 'bg-primary-500',
                                            ]"
                                            :style="{
                                                width: `${usagePercent(row)}%`,
                                            }"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ formatMinutes(row.excess_minutes) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ formatMinutes(row.hourly_minutes) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{
                                        formatMoney(
                                            row.fixed_amount,
                                            row.currency,
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-bold text-ink-950 tabular-nums dark:text-white"
                                >
                                    {{
                                        formatMoney(
                                            row.amount_due,
                                            row.currency,
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr v-if="billing.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-10 text-center text-ink-500"
                                >
                                    Sin consumo de soporte registrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    class="mt-4"
                    :links="billing.links"
                    :from="billing.from"
                    :to="billing.to"
                    :total="billing.total"
                />
            </Card>

            <Card title="Tickets pendientes de facturar">
                <p
                    v-if="pendingTickets.total === 0"
                    class="text-sm text-ink-500"
                >
                    No hay cobros pendientes por ticket.
                </p>
                <template v-else>
                    <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                        <li
                            v-for="ticket in pendingTickets.data"
                            :key="ticket.id"
                            class="flex items-center gap-3 py-3"
                        >
                            <input
                                v-if="can.billing"
                                v-model="selected"
                                type="checkbox"
                                :value="ticket.id"
                                :class="ui.checkbox"
                                :aria-label="`Seleccionar ${ticket.code}`"
                            />
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="
                                        central.support.tickets.show(ticket.id)
                                            .url
                                    "
                                    class="font-semibold text-ink-900 hover:text-primary-700 dark:text-white"
                                >
                                    {{ ticket.code }} · {{ ticket.subject }}
                                </Link>
                                <p class="text-xs text-ink-500">
                                    {{ ticket.tenant_name ?? 'Sin tenant' }} ·
                                    {{ ticket.billing_mode_label }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums">
                                {{
                                    ticket.fixed_amount
                                        ? formatMoney(ticket.fixed_amount)
                                        : formatMinutes(ticket.billable_minutes)
                                }}
                            </p>
                        </li>
                    </ul>
                    <Pagination
                        class="mt-4"
                        :links="pendingTickets.links"
                        :from="pendingTickets.from"
                        :to="pendingTickets.to"
                        :total="pendingTickets.total"
                    />
                    <div
                        v-if="can.billing"
                        class="mt-4 flex flex-col gap-2 border-t border-ink-100 pt-4 sm:flex-row dark:border-ink-800"
                    >
                        <input
                            v-model="invoiceReference"
                            type="text"
                            placeholder="N.º de comprobante SRI (001-001-000000123)"
                            :class="ui.input"
                        />
                        <button
                            type="button"
                            :disabled="
                                selected.length === 0 || !invoiceReference
                            "
                            :class="[ui.buttonPrimary, 'shrink-0']"
                            @click="markInvoiced"
                        >
                            Marcar {{ selected.length || '' }} como facturado(s)
                        </button>
                    </div>
                </template>
            </Card>

            <Card title="Satisfacción por agente">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                            >
                                <th class="px-4 py-3">Agente</th>
                                <th class="px-4 py-3 text-right">Resueltos</th>
                                <th class="px-4 py-3 text-right">
                                    Calificaciones
                                </th>
                                <th class="px-4 py-3 text-right">Promedio</th>
                                <th class="px-4 py-3 text-right">CSAT</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="agent in agents.data" :key="agent.id">
                                <td
                                    class="px-4 py-3 font-semibold text-ink-900 dark:text-white"
                                >
                                    {{ agent.name }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ agent.resolved }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ agent.ratings }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{
                                        agent.ratings
                                            ? `${agent.average_stars} ★`
                                            : '—'
                                    }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-bold tabular-nums"
                                >
                                    {{
                                        agent.ratings
                                            ? `${agent.satisfied_percent}%`
                                            : '—'
                                    }}
                                </td>
                            </tr>
                            <tr v-if="agents.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-ink-500"
                                >
                                    Aún no hay tickets resueltos.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    class="mt-4"
                    :links="agents.links"
                    :from="agents.from"
                    :to="agents.to"
                    :total="agents.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
