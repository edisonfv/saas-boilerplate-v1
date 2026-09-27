<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { money } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

/** Shape of SignatureSalesReport::totals(). */
interface Totals {
    units: number;
    revenue: string;
    provider_cost: string;
    central_profit: string;
    central_margin: number | null;
    retail: string;
    distributor_profit: string;
    distributor_margin: number | null;
    average_sale_price: string | null;
    average_unit_price: string | null;
    without_cost: number;
    without_retail: number;
}

interface Filters {
    period: string;
    from: string;
    to: string;
    tenant: string | null;
    product: string | null;
}

const props = defineProps<{
    filters: Filters;
    summary: Totals & { issued: number; in_progress: number; refunded: number };
    cash: {
        packages_sold: string;
        payments_received: string;
        credit_outstanding: string;
        prepaid_units_held: number;
    };
    trend: {
        month: string;
        label: string;
        units: number;
        revenue: string;
        central_profit: string;
    }[];
    tenants: (Totals & {
        tenant_id: string;
        tenant_name: string;
        share: number;
    })[];
    products: (Totals & {
        product_id: string;
        product_name: string;
        min_retail_price: string | null;
        suggested_retail_price: string | null;
        min_sale_price: string | null;
        max_sale_price: string | null;
    })[];
    alerts: {
        tenant_id: string;
        tenant_name: string;
        kind: string;
        tone: 'red' | 'amber' | 'gray';
        message: string;
    }[];
    periods: Record<string, string>;
    tenantOptions: { id: string; name: string }[];
    productOptions: { id: string; name: string }[];
}>();

const form = reactive({
    period: props.filters.period,
    from: props.filters.from,
    to: props.filters.to,
    tenant: props.filters.tenant ?? '',
    product: props.filters.product ?? '',
});

function query(): Record<string, string> {
    const params: Record<string, string> = { period: form.period };

    if (form.period === 'Custom') {
        params.from = form.from;
        params.to = form.to;
    }

    if (form.tenant) {
        params.tenant = form.tenant;
    }

    if (form.product) {
        params.product = form.product;
    }

    return params;
}

function apply(): void {
    router.get(central.signatures.sales.index().url, query(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

const exportUrl = computed(
    () => central.signatures.sales.export({ query: query() }).url,
);

const maxUnits = computed(() =>
    Math.max(1, ...props.trend.map((month) => month.units)),
);
const hovered = ref<number | null>(null);

const alertsToShow = computed(() =>
    props.alerts.filter((alert) => alert.tone !== 'gray'),
);
const idleAlerts = computed(() =>
    props.alerts.filter((alert) => alert.tone === 'gray'),
);

const percent = (value: number | null) =>
    value === null ? '—' : `${value.toLocaleString('es-EC')}%`;
</script>

<template>
    <Head title="Ventas de firmas" />

    <CentralLayout title="Ventas de firmas">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Firmas electrónicas · Uanataca
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Ventas y utilidad
                    </h2>
                    <p
                        class="mt-2 max-w-3xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Lo que venden tus distribuidores, a qué precio, cuánto
                        ganas sobre el costo de Uanataca y qué cuentas necesitan
                        atención. Las firmas rechazadas o reversadas no cuentan.
                    </p>
                </div>
                <a :href="exportUrl" :class="ui.buttonSecondary">
                    <Icon name="arrow-right" class="size-4 rotate-90" />
                    Exportar CSV
                </a>
            </div>

            <Card>
                <form
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    @submit.prevent="apply"
                >
                    <div>
                        <label for="period" :class="ui.label">Periodo</label>
                        <select
                            id="period"
                            v-model="form.period"
                            :class="ui.input"
                            @change="form.period !== 'Custom' && apply()"
                        >
                            <option
                                v-for="(label, value) in periods"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <template v-if="form.period === 'Custom'">
                        <div>
                            <label for="from" :class="ui.label">Desde</label>
                            <input
                                id="from"
                                v-model="form.from"
                                type="date"
                                :class="ui.input"
                            />
                        </div>
                        <div>
                            <label for="to" :class="ui.label">Hasta</label>
                            <input
                                id="to"
                                v-model="form.to"
                                type="date"
                                :class="ui.input"
                            />
                        </div>
                    </template>
                    <div>
                        <label for="tenant" :class="ui.label"
                            >Distribuidor</label
                        >
                        <select
                            id="tenant"
                            v-model="form.tenant"
                            :class="ui.input"
                            @change="apply"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="option in tenantOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="product" :class="ui.label">Producto</label>
                        <select
                            id="product"
                            v-model="form.product"
                            :class="ui.input"
                            @change="apply"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="option in productOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="form.period === 'Custom'" class="flex items-end">
                        <button
                            type="submit"
                            :class="[ui.buttonPrimary, 'w-full']"
                        >
                            Aplicar
                        </button>
                    </div>
                </form>
                <p class="mt-3 text-xs text-ink-500">
                    Del {{ filters.from }} al {{ filters.to }}
                </p>
            </Card>

            <div
                v-if="summary.without_cost > 0"
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
            >
                <Icon
                    name="exclamation-triangle"
                    class="mt-0.5 size-5 shrink-0"
                />
                <p>
                    {{ summary.without_cost }} de {{ summary.units }} firmas no
                    tienen registrado el costo de Uanataca, así que la utilidad
                    mostrada es parcial.
                    <Link
                        :href="central.signatures.products.index().url"
                        class="font-semibold underline"
                        >Registra el costo en el catálogo</Link
                    >
                    para que las próximas ventas lo incluyan.
                </p>
            </div>

            <!-- Value chain: provider cost → central → distributor → customer -->
            <Card title="Cadena de valor del periodo">
                <div
                    class="grid grid-cols-1 items-stretch gap-3 md:grid-cols-[1fr_auto_1fr_auto_1fr]"
                >
                    <div class="rounded-lg bg-ink-50 p-4 dark:bg-ink-800/60">
                        <p class="text-xs font-medium text-ink-500">
                            Pagas a Uanataca
                        </p>
                        <p
                            class="mt-1 text-xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                        >
                            {{ money(summary.provider_cost) }}
                        </p>
                        <p class="text-xs text-ink-500">Costo de las firmas</p>
                    </div>
                    <div
                        class="flex flex-col items-center justify-center px-2 text-center"
                    >
                        <Icon
                            name="arrow-right"
                            class="hidden text-ink-300 md:block"
                        />
                        <p
                            class="text-sm font-bold text-emerald-700 tabular-nums dark:text-emerald-400"
                        >
                            +{{ money(summary.central_profit) }}
                        </p>
                        <p class="text-xs text-ink-500">
                            tu utilidad · {{ percent(summary.central_margin) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-primary-50 p-4 dark:bg-primary-500/10"
                    >
                        <p class="text-xs font-medium text-ink-500">
                            Te pagan los distribuidores
                        </p>
                        <p
                            class="mt-1 text-xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                        >
                            {{ money(summary.revenue) }}
                        </p>
                        <p class="text-xs text-ink-500">
                            Promedio
                            {{ money(summary.average_unit_price) }} por firma
                        </p>
                    </div>
                    <div
                        class="flex flex-col items-center justify-center px-2 text-center"
                    >
                        <Icon
                            name="arrow-right"
                            class="hidden text-ink-300 md:block"
                        />
                        <p
                            class="text-sm font-bold text-ink-700 tabular-nums dark:text-ink-200"
                        >
                            +{{ money(summary.distributor_profit) }}
                        </p>
                        <p class="text-xs text-ink-500">
                            ganan ellos ·
                            {{ percent(summary.distributor_margin) }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-ink-50 p-4 dark:bg-ink-800/60">
                        <p class="text-xs font-medium text-ink-500">
                            Pagan los clientes finales
                        </p>
                        <p
                            class="mt-1 text-xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                        >
                            {{ money(summary.retail) }}
                        </p>
                        <p class="text-xs text-ink-500">
                            PVP promedio
                            {{ money(summary.average_sale_price) }}
                        </p>
                    </div>
                </div>
                <p
                    v-if="summary.without_retail > 0"
                    class="mt-3 text-xs text-ink-500"
                >
                    {{ summary.without_retail }} ventas sin precio al público
                    registrado no se incluyen en la ganancia de los
                    distribuidores.
                </p>
            </Card>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Firmas vendidas"
                    :value="summary.units"
                    icon="key"
                    :helper="`${summary.issued} emitidas · ${summary.in_progress} en trámite · ${summary.refunded} reversadas`"
                />
                <StatCard
                    label="Cobrado en el periodo"
                    :value="
                        money(
                            Number(cash.packages_sold) +
                                Number(cash.payments_received),
                        )
                    "
                    icon="currency"
                    :helper="`${money(cash.packages_sold)} paquetes · ${money(cash.payments_received)} abonos`"
                />
                <StatCard
                    label="Crédito por cobrar"
                    :value="money(cash.credit_outstanding)"
                    icon="clock"
                    helper="Saldo actual de distribuidores a crédito"
                />
                <StatCard
                    label="Firmas prepagadas sin usar"
                    :value="cash.prepaid_units_held"
                    icon="layers"
                    helper="Ya cobradas, pendientes de emitir"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card title="Firmas vendidas por mes" class="xl:col-span-2">
                    <div
                        class="relative flex h-56 items-end gap-0.5"
                        role="img"
                        :aria-label="`Firmas vendidas en los últimos ${trend.length} meses`"
                        @mouseleave="hovered = null"
                    >
                        <div
                            v-for="(month, index) in trend"
                            :key="month.month"
                            class="group relative flex h-full flex-1 flex-col items-center justify-end"
                            @mouseenter="hovered = index"
                        >
                            <div
                                v-if="hovered === index"
                                class="pointer-events-none absolute bottom-full z-10 mb-1 w-44 rounded-lg border border-ink-200 bg-white p-2.5 text-xs shadow-lg dark:border-ink-700 dark:bg-ink-900"
                                :class="
                                    index > trend.length - 3
                                        ? 'right-0'
                                        : index < 2
                                          ? 'left-0'
                                          : ''
                                "
                            >
                                <p
                                    class="font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ month.label }}
                                </p>
                                <p class="mt-1 flex justify-between gap-2">
                                    <span class="text-ink-500">Firmas</span>
                                    <span class="tabular-nums">{{
                                        month.units
                                    }}</span>
                                </p>
                                <p class="flex justify-between gap-2">
                                    <span class="text-ink-500">Ingresos</span>
                                    <span class="tabular-nums">{{
                                        money(month.revenue)
                                    }}</span>
                                </p>
                                <p class="flex justify-between gap-2">
                                    <span class="text-ink-500">Utilidad</span>
                                    <span class="tabular-nums">{{
                                        money(month.central_profit)
                                    }}</span>
                                </p>
                            </div>
                            <span
                                v-if="month.units > 0"
                                class="mb-1 text-[11px] text-ink-500 tabular-nums"
                                >{{ month.units }}</span
                            >
                            <div
                                class="w-full max-w-10 rounded-t transition-colors"
                                :class="
                                    hovered === index
                                        ? 'bg-primary-700 dark:bg-primary-300'
                                        : 'bg-primary-500 dark:bg-primary-400'
                                "
                                :style="{
                                    height: `${(month.units / maxUnits) * 85}%`,
                                    minHeight: month.units > 0 ? '2px' : '0',
                                }"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 flex gap-0.5 border-t border-ink-200 pt-2 dark:border-ink-800"
                    >
                        <span
                            v-for="month in trend"
                            :key="month.month"
                            class="flex-1 truncate text-center text-[11px] text-ink-500"
                            >{{ month.label }}</span
                        >
                    </div>
                </Card>

                <Card title="Requieren atención">
                    <ul
                        v-if="alertsToShow.length"
                        class="divide-y divide-ink-100 dark:divide-ink-800"
                    >
                        <li
                            v-for="alert in alertsToShow"
                            :key="`${alert.tenant_id}-${alert.kind}`"
                            class="flex items-start gap-3 py-2.5 text-sm"
                        >
                            <Badge :tone="alert.tone">
                                {{ alert.tone === 'red' ? 'Urgente' : 'Aviso' }}
                            </Badge>
                            <div class="min-w-0">
                                <Link
                                    :href="
                                        central.tenants.signatures(
                                            alert.tenant_id,
                                        ).url
                                    "
                                    class="font-medium text-ink-950 hover:text-primary-700 dark:text-white"
                                >
                                    {{ alert.tenant_name }}
                                </Link>
                                <p class="text-xs text-ink-500">
                                    {{ alert.message }}
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-ink-500">
                        Ningún distribuidor tiene el crédito o el saldo al
                        límite.
                    </p>
                    <details
                        v-if="idleAlerts.length"
                        class="mt-4 text-sm text-ink-600 dark:text-ink-400"
                    >
                        <summary class="cursor-pointer font-medium">
                            {{ idleAlerts.length }} sin ventas recientes
                        </summary>
                        <ul class="mt-2 space-y-1.5">
                            <li
                                v-for="alert in idleAlerts"
                                :key="alert.tenant_id"
                            >
                                <Link
                                    :href="
                                        central.tenants.signatures(
                                            alert.tenant_id,
                                        ).url
                                    "
                                    :class="ui.link"
                                    >{{ alert.tenant_name }}</Link
                                >
                                <span class="text-xs text-ink-500">
                                    · {{ alert.message }}</span
                                >
                            </li>
                        </ul>
                    </details>
                </Card>
            </div>

            <Card title="Ranking de distribuidores">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="text-xs text-ink-500 uppercase">
                            <tr>
                                <th class="py-2 pr-3 font-semibold">
                                    Distribuidor
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Firmas
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Te pagó
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Tu utilidad
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Vendió al público
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    PVP promedio
                                </th>
                                <th class="py-2 text-right font-semibold">
                                    Ganó el distribuidor
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="row in tenants" :key="row.tenant_id">
                                <td class="py-2.5 pr-3">
                                    <Link
                                        :href="
                                            central.tenants.signatures(
                                                row.tenant_id,
                                            ).url
                                        "
                                        class="font-medium text-ink-950 hover:text-primary-700 dark:text-white"
                                        >{{ row.tenant_name }}</Link
                                    >
                                    <div
                                        class="mt-1 h-1 w-full max-w-40 rounded-full bg-ink-100 dark:bg-ink-800"
                                    >
                                        <div
                                            class="h-1 rounded-full bg-primary-500"
                                            :style="{ width: `${row.share}%` }"
                                        />
                                    </div>
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ row.units }}
                                    <span class="text-xs text-ink-500"
                                        >({{ row.share }}%)</span
                                    >
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ money(row.revenue) }}
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ money(row.central_profit) }}
                                    <p class="text-xs text-ink-500">
                                        {{ percent(row.central_margin) }}
                                    </p>
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ money(row.retail) }}
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{
                                        row.average_sale_price
                                            ? money(row.average_sale_price)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-2.5 text-right tabular-nums">
                                    {{ money(row.distributor_profit) }}
                                    <p class="text-xs text-ink-500">
                                        {{ percent(row.distributor_margin) }}
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="!tenants.length">
                                <td
                                    colspan="7"
                                    class="py-8 text-center text-ink-500"
                                >
                                    Sin ventas en el periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <Card title="Por producto">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="text-xs text-ink-500 uppercase">
                            <tr>
                                <th class="py-2 pr-3 font-semibold">
                                    Producto
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Firmas
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Ingresos
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    Tu utilidad
                                </th>
                                <th class="py-2 pr-3 text-right font-semibold">
                                    PVP cobrado (mín – máx)
                                </th>
                                <th class="py-2 text-right font-semibold">
                                    Sugerido / mínimo
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="row in products" :key="row.product_id">
                                <td
                                    class="py-2.5 pr-3 font-medium text-ink-950 dark:text-white"
                                >
                                    {{ row.product_name }}
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ row.units }}
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ money(row.revenue) }}
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    {{ money(row.central_profit) }}
                                    <p class="text-xs text-ink-500">
                                        {{ percent(row.central_margin) }}
                                    </p>
                                </td>
                                <td class="py-2.5 pr-3 text-right tabular-nums">
                                    <template v-if="row.min_sale_price">
                                        {{ money(row.min_sale_price) }} –
                                        {{ money(row.max_sale_price) }}
                                    </template>
                                    <template v-else>—</template>
                                </td>
                                <td
                                    class="py-2.5 text-right text-ink-500 tabular-nums"
                                >
                                    {{
                                        row.suggested_retail_price
                                            ? money(row.suggested_retail_price)
                                            : '—'
                                    }}
                                    /
                                    {{
                                        row.min_retail_price
                                            ? money(row.min_retail_price)
                                            : 'libre'
                                    }}
                                </td>
                            </tr>
                            <tr v-if="!products.length">
                                <td
                                    colspan="6"
                                    class="py-8 text-center text-ink-500"
                                >
                                    Sin ventas en el periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </CentralLayout>
</template>
