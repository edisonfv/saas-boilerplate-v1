<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { money } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

interface Totals {
    revenue: string;
    cost: string;
    margin: string;
    margin_percent: number | null;
}

const props = defineProps<{
    period: { from: string; to: string };
    filters: { desde: string; hasta: string };
    summary: Totals & {
        sold: number;
        reversed: number;
        average_ticket: string | null;
    };
    products: (Totals & {
        product_name: string;
        sold: number;
        average_price: string;
    })[];
    rows: {
        data: {
            id: string;
            code: string;
            customer: string;
            document_number: string;
            product_name: string;
            source_label: string;
            status_label: string;
            submitted_at: string | null;
            is_reversed: boolean;
            revenue: string;
            cost: string;
            margin: string;
        }[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    account: {
        affiliation_mode_label: string;
        is_credit: boolean;
        credit_limit: string;
        credit_used: string;
        credit_available: string;
        balances: {
            product_name: string;
            available_units: number;
            unit_cost: string;
            inventory_value: string;
        }[];
        inventory_value: string;
    } | null;
    pending: {
        to_send: number;
        to_send_amount: string;
        payments_under_review: number;
        payments_under_review_amount: string;
    };
}>();

const { date, dateTime } = useDateTime();
const from = ref(props.filters.desde);
const to = ref(props.filters.hasta);

const exportUrl = computed(
    () =>
        tenant.signatures.settlement.export({
            query: { desde: props.filters.desde, hasta: props.filters.hasta },
        }).url,
);

function percent(value: number | null): string {
    return value === null ? '—' : `${value}%`;
}

function applyPeriod(): void {
    router.get(
        tenant.signatures.settlement.index().url,
        { desde: from.value, hasta: to.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Liquidación de firmas" />

    <GeneralLayout title="Liquidación de firmas">
        <div class="col-span-12 space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Firmas electrónicas
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Liquidación y rentabilidad
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Firmas enviadas a la entidad certificadora entre el
                        {{ date(period.from) }} y el {{ date(period.to) }}: lo
                        cobrado a tus clientes frente a lo que te costó cada
                        firma.
                    </p>
                </div>

                <form
                    class="flex flex-wrap items-end gap-2"
                    @submit.prevent="applyPeriod"
                >
                    <div>
                        <label for="desde" :class="ui.label">Desde</label>
                        <input
                            id="desde"
                            v-model="from"
                            type="date"
                            :class="ui.input"
                        />
                    </div>
                    <div>
                        <label for="hasta" :class="ui.label">Hasta</label>
                        <input
                            id="hasta"
                            v-model="to"
                            type="date"
                            :min="from"
                            :class="ui.input"
                        />
                    </div>
                    <button type="submit" :class="ui.buttonSecondary">
                        Aplicar
                    </button>
                    <a :href="exportUrl" :class="ui.buttonPrimary">
                        Exportar CSV
                    </a>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    label="Firmas liquidadas"
                    :value="summary.sold"
                    icon="key"
                    :helper="
                        summary.reversed
                            ? `${summary.reversed} reversada(s) no cuentan`
                            : 'Enviadas y no reversadas'
                    "
                />
                <StatCard
                    label="Ventas cobradas"
                    :value="money(summary.revenue)"
                    icon="currency"
                    :helper="
                        summary.average_ticket
                            ? `Ticket promedio ${money(summary.average_ticket)}`
                            : 'Sin ventas en el periodo'
                    "
                />
                <StatCard
                    label="Costo de las firmas"
                    :value="money(summary.cost)"
                    icon="tag"
                    :helper="
                        account?.is_credit
                            ? 'Precio a crédito de la plataforma'
                            : 'Costo promedio de tus paquetes'
                    "
                />
                <StatCard
                    label="Margen bruto"
                    :value="money(summary.margin)"
                    icon="chart"
                    :helper="`Rentabilidad ${percent(summary.margin_percent)}`"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card title="Rentabilidad por producto" class="xl:col-span-2">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr
                                    class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                                >
                                    <th class="px-4 py-3">Producto</th>
                                    <th class="px-4 py-3 text-right">Firmas</th>
                                    <th class="px-4 py-3 text-right">
                                        Precio prom.
                                    </th>
                                    <th class="px-4 py-3 text-right">Ventas</th>
                                    <th class="px-4 py-3 text-right">Costo</th>
                                    <th class="px-4 py-3 text-right">Margen</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-ink-100 dark:divide-ink-800"
                            >
                                <tr
                                    v-for="product in products"
                                    :key="product.product_name"
                                >
                                    <td class="px-4 py-3 font-semibold">
                                        {{ product.product_name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ product.sold }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ money(product.average_price) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ money(product.revenue) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ money(product.cost) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-semibold tabular-nums"
                                    >
                                        {{ money(product.margin) }}
                                        <span
                                            class="block text-xs font-normal text-ink-500"
                                        >
                                            {{
                                                percent(product.margin_percent)
                                            }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="products.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-10 text-center text-ink-500"
                                    >
                                        No enviaste firmas en este periodo.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <div class="space-y-6">
                    <Card title="Tu cuenta con la plataforma">
                        <p
                            v-if="!account"
                            class="text-sm text-ink-500 dark:text-ink-400"
                        >
                            Aún no tienes una cuenta de distribuidor de firmas.
                        </p>
                        <dl v-else class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Modalidad
                                </dt>
                                <dd class="font-semibold">
                                    {{ account.affiliation_mode_label }}
                                </dd>
                            </div>
                            <template v-if="account.is_credit">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-ink-500 dark:text-ink-400">
                                        Saldo por pagar
                                    </dt>
                                    <dd class="font-semibold tabular-nums">
                                        {{ money(account.credit_used) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-ink-500 dark:text-ink-400">
                                        Crédito disponible
                                    </dt>
                                    <dd class="tabular-nums">
                                        {{ money(account.credit_available) }}
                                        de {{ money(account.credit_limit) }}
                                    </dd>
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    v-for="balance in account.balances"
                                    :key="balance.product_name"
                                    class="flex justify-between gap-4"
                                >
                                    <dt class="text-ink-500 dark:text-ink-400">
                                        {{ balance.product_name }}
                                    </dt>
                                    <dd class="text-right tabular-nums">
                                        {{ balance.available_units }} u. ·
                                        {{ money(balance.inventory_value) }}
                                    </dd>
                                </div>
                                <div
                                    class="flex justify-between gap-4 border-t border-ink-100 pt-3 dark:border-ink-800"
                                >
                                    <dt class="text-ink-500 dark:text-ink-400">
                                        Inventario prepagado
                                    </dt>
                                    <dd class="font-semibold tabular-nums">
                                        {{ money(account.inventory_value) }}
                                    </dd>
                                </div>
                            </template>
                        </dl>
                    </Card>

                    <Card title="Por concretar">
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Pagadas sin enviar
                                </dt>
                                <dd class="text-right tabular-nums">
                                    {{ pending.to_send }} ·
                                    {{ money(pending.to_send_amount) }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">
                                    Comprobantes por revisar
                                </dt>
                                <dd class="text-right tabular-nums">
                                    {{ pending.payments_under_review }} ·
                                    {{
                                        money(
                                            pending.payments_under_review_amount,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </Card>
                </div>
            </div>

            <Card title="Detalle de firmas enviadas">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                            >
                                <th class="px-4 py-3">Solicitud</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Enviada</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Venta</th>
                                <th class="px-4 py-3 text-right">Costo</th>
                                <th class="px-4 py-3 text-right">Margen</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="row in rows.data"
                                :key="row.id"
                                :class="row.is_reversed && 'opacity-60'"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="
                                            tenant.signatures.requests.show(
                                                row.id,
                                            ).url
                                        "
                                        :class="ui.link"
                                    >
                                        {{ row.code }}
                                    </Link>
                                    <span class="block text-xs text-ink-500">
                                        {{ row.source_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ row.customer }}
                                    <span class="block text-xs text-ink-500">
                                        {{ row.document_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ row.product_name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ dateTime(row.submitted_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        :tone="
                                            row.is_reversed ? 'amber' : 'gray'
                                        "
                                    >
                                        {{
                                            row.is_reversed
                                                ? 'Reversada'
                                                : row.status_label
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ money(row.revenue) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ money(row.cost) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-semibold tabular-nums"
                                >
                                    {{ money(row.margin) }}
                                </td>
                            </tr>
                            <tr v-if="rows.data.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-10 text-center text-ink-500"
                                >
                                    Sin firmas enviadas en el periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    class="mt-4"
                    :links="rows.links"
                    :from="rows.from"
                    :to="rows.to"
                    :total="rows.total"
                />
            </Card>
        </div>
    </GeneralLayout>
</template>
