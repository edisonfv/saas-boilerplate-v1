<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import TenantHeader from '@/components/central/TenantHeader.vue';
import type { TenantHeaderData } from '@/components/central/TenantHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import QuotaCard from '@/components/signatures/QuotaCard.vue';
import StatCard from '@/components/StatCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { ledgerEntryTone, money, signatureStatusTone } from '@/lib/signatures';
import type { SignatureAccount } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

interface Performance {
    units: number;
    revenue: string;
    central_profit: string;
    central_margin: number | null;
    retail: string;
    distributor_profit: string;
    refunded: number;
}

const props = defineProps<{
    header: TenantHeaderData;
    account: SignatureAccount | null;
    performance: { month: Performance; year: Performance } | null;
    pricing: {
        product_id: string;
        product_name: string;
        provider_cost: string | null;
        unit_price: string;
        central_margin: string | null;
        retail_price: string | null;
        uses_own_price: boolean;
        suggested_retail_price: string | null;
        min_retail_price: string | null;
        distributor_margin: string | null;
    }[];
    ledger: {
        data: {
            id: string;
            type: string;
            type_label: string;
            product_name: string | null;
            units: number;
            amount: string;
            reference: string | null;
            description: string | null;
            author: string | null;
            is_refundable: boolean;
            created_at: string;
        }[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    } | null;
    sales: {
        id: string;
        product_name: string | null;
        unit_price: string | null;
        sale_price: string | null;
        status: string;
        status_label: string;
        provider_token: string | null;
        submitted_at: string | null;
        last_event_at: string | null;
    }[];
    packages: {
        id: string;
        name: string;
        product_name: string;
        quantity: number;
        price: string;
    }[];
    products: { id: string; name: string }[];
    affiliationModes: Record<string, string>;
    can: { update: boolean; transactions: boolean };
}>();

const tenant = props.header;
const { dateTime } = useDateTime();
const mode = ref(props.account?.affiliation_mode ?? 'Prepaid');

const confirmRefund = () =>
    window.confirm('¿Reversar este consumo y devolver la firma al tenant?');
</script>

<template>
    <Head :title="`Firmas · ${tenant.company_name ?? tenant.id}`" />

    <CentralLayout :title="`Firmas · ${tenant.company_name ?? tenant.id}`">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12">
                <TenantHeader :tenant="header" active="signatures" />
            </div>

            <div
                v-if="performance"
                class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <StatCard
                    label="Firmas vendidas (mes)"
                    :value="performance.month.units"
                    icon="key"
                    :helper="`${performance.year.units} en el año · ${performance.month.refunded} reversadas`"
                />
                <StatCard
                    label="Ingresos de central (mes)"
                    :value="money(performance.month.revenue)"
                    icon="currency"
                    :helper="`${money(performance.year.revenue)} en el año`"
                />
                <StatCard
                    label="Utilidad de central (mes)"
                    :value="money(performance.month.central_profit)"
                    icon="chart"
                    :helper="
                        performance.month.central_margin === null
                            ? 'Registra el costo Uanataca'
                            : `Margen ${performance.month.central_margin}%`
                    "
                />
                <StatCard
                    label="Venta al público (mes)"
                    :value="money(performance.month.retail)"
                    icon="users"
                    :helper="`Gana el distribuidor ${money(performance.month.distributor_profit)}`"
                />
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-8">
                <Card v-if="pricing.length" title="Precios del distribuidor">
                    <p class="mb-4 text-sm text-ink-500 dark:text-ink-400">
                        Lo que paga a central por cada firma y el precio que
                        publica a sus clientes. El precio mínimo se define en el
                        catálogo: el distribuidor no puede vender por debajo.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead class="text-xs text-ink-500 uppercase">
                                <tr>
                                    <th class="py-2 pr-3 font-semibold">
                                        Producto
                                    </th>
                                    <th
                                        class="py-2 pr-3 text-right font-semibold"
                                    >
                                        Costo
                                    </th>
                                    <th
                                        class="py-2 pr-3 text-right font-semibold"
                                    >
                                        Paga a central
                                    </th>
                                    <th
                                        class="py-2 pr-3 text-right font-semibold"
                                    >
                                        Vende al público
                                    </th>
                                    <th class="py-2 text-right font-semibold">
                                        Gana el distribuidor
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-ink-100 dark:divide-ink-800"
                            >
                                <tr
                                    v-for="row in pricing"
                                    :key="row.product_id"
                                >
                                    <td
                                        class="py-2.5 pr-3 text-ink-950 dark:text-white"
                                    >
                                        {{ row.product_name }}
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 text-right text-ink-500 tabular-nums"
                                    >
                                        {{
                                            row.provider_cost
                                                ? money(row.provider_cost)
                                                : '—'
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 text-right tabular-nums"
                                    >
                                        {{ money(row.unit_price) }}
                                        <p
                                            v-if="row.central_margin"
                                            class="text-xs text-emerald-600 dark:text-emerald-400"
                                        >
                                            +{{ money(row.central_margin) }}
                                            central
                                        </p>
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 text-right tabular-nums"
                                    >
                                        {{
                                            row.retail_price
                                                ? money(row.retail_price)
                                                : '—'
                                        }}
                                        <p class="text-xs text-ink-500">
                                            {{
                                                row.uses_own_price
                                                    ? 'Precio propio'
                                                    : 'PVP sugerido'
                                            }}<template
                                                v-if="row.min_retail_price"
                                            >
                                                · mín.
                                                {{
                                                    money(row.min_retail_price)
                                                }}</template
                                            >
                                        </p>
                                    </td>
                                    <td class="py-2.5 text-right tabular-nums">
                                        {{
                                            row.distributor_margin
                                                ? money(row.distributor_margin)
                                                : '—'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card
                    v-if="account && can.transactions"
                    title="Movimientos manuales"
                >
                    <div class="grid gap-6 lg:grid-cols-2">
                        <Form
                            v-if="!account.is_credit"
                            v-bind="
                                central.signatures.accounts.packages.store.form(
                                    tenant.id,
                                )
                            "
                            reset-on-success
                            #default="{ errors, processing }"
                            class="space-y-3"
                        >
                            <p
                                class="text-sm font-semibold text-ink-950 dark:text-white"
                            >
                                Vender paquete prepago
                            </p>
                            <select
                                name="signature_package_id"
                                :class="ui.input"
                            >
                                <option
                                    v-for="pack in packages"
                                    :key="pack.id"
                                    :value="pack.id"
                                >
                                    {{ pack.name }} ({{ pack.product_name }}) —
                                    {{ money(pack.price) }}
                                </option>
                            </select>
                            <input
                                name="reference"
                                placeholder="N.º de factura o comprobante"
                                :class="ui.input"
                            />
                            <p
                                v-if="errors.signature_package_id"
                                :class="ui.error"
                            >
                                {{ errors.signature_package_id }}
                            </p>
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="ui.buttonPrimary"
                            >
                                Acreditar paquete
                            </button>
                        </Form>

                        <Form
                            v-else
                            v-bind="
                                central.signatures.accounts.payments.store.form(
                                    tenant.id,
                                )
                            "
                            reset-on-success
                            #default="{ errors, processing }"
                            class="space-y-3"
                        >
                            <p
                                class="text-sm font-semibold text-ink-950 dark:text-white"
                            >
                                Registrar abono al crédito
                            </p>
                            <input
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="Monto (USD)"
                                :class="ui.input"
                            />
                            <input
                                name="reference"
                                placeholder="N.º de transferencia o recibo"
                                :class="ui.input"
                            />
                            <p v-if="errors.amount" :class="ui.error">
                                {{ errors.amount }}
                            </p>
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="ui.buttonPrimary"
                            >
                                Registrar abono
                            </button>
                        </Form>

                        <Form
                            v-bind="
                                central.signatures.accounts.adjustments.store.form(
                                    tenant.id,
                                )
                            "
                            reset-on-success
                            #default="{ errors, processing }"
                            class="space-y-3"
                        >
                            <p
                                class="text-sm font-semibold text-ink-950 dark:text-white"
                            >
                                Ajuste de firmas prepago
                            </p>
                            <div class="grid grid-cols-3 gap-2">
                                <select
                                    name="signature_product_id"
                                    :class="[ui.input, 'col-span-2']"
                                >
                                    <option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }}
                                    </option>
                                </select>
                                <input
                                    name="units"
                                    type="number"
                                    placeholder="±"
                                    :class="ui.input"
                                />
                            </div>
                            <input
                                name="description"
                                placeholder="Motivo (ej. cortesía, corrección)"
                                :class="ui.input"
                            />
                            <p
                                v-if="errors.units || errors.description"
                                :class="ui.error"
                            >
                                {{ errors.units ?? errors.description }}
                            </p>
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="ui.buttonSecondary"
                            >
                                Registrar ajuste
                            </button>
                        </Form>
                    </div>
                </Card>

                <Card v-if="ledger" title="Libro de movimientos">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs text-ink-500 uppercase">
                                <tr>
                                    <th class="py-2 pr-3 font-semibold">
                                        Fecha
                                    </th>
                                    <th class="py-2 pr-3 font-semibold">
                                        Tipo
                                    </th>
                                    <th class="py-2 pr-3 font-semibold">
                                        Detalle
                                    </th>
                                    <th
                                        class="py-2 pr-3 text-right font-semibold"
                                    >
                                        Firmas
                                    </th>
                                    <th
                                        class="py-2 pr-3 text-right font-semibold"
                                    >
                                        Monto
                                    </th>
                                    <th class="py-2" />
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-ink-100 dark:divide-ink-800"
                            >
                                <tr
                                    v-for="entry in ledger.data"
                                    :key="entry.id"
                                >
                                    <td
                                        class="py-2.5 pr-3 whitespace-nowrap text-ink-500"
                                    >
                                        {{ dateTime(entry.created_at) }}
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <Badge
                                            :tone="ledgerEntryTone(entry.type)"
                                            >{{ entry.type_label }}</Badge
                                        >
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <p class="text-ink-950 dark:text-white">
                                            {{ entry.description }}
                                        </p>
                                        <p class="text-xs text-ink-500">
                                            {{ entry.product_name }}
                                            <template v-if="entry.reference">
                                                ·
                                                {{ entry.reference }}</template
                                            >
                                            <template v-if="entry.author">
                                                · {{ entry.author }}</template
                                            >
                                        </p>
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 text-right tabular-nums"
                                    >
                                        {{
                                            entry.units > 0
                                                ? `+${entry.units}`
                                                : entry.units || '—'
                                        }}
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 text-right tabular-nums"
                                    >
                                        {{
                                            Number(entry.amount) !== 0
                                                ? money(entry.amount)
                                                : '—'
                                        }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <Form
                                            v-if="
                                                entry.is_refundable &&
                                                can.transactions
                                            "
                                            v-bind="
                                                central.signatures.accounts.ledger.refund.form(
                                                    {
                                                        tenant: tenant.id,
                                                        entry: entry.id,
                                                    },
                                                )
                                            "
                                            :on-before="confirmRefund"
                                            #default="{ processing }"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                :class="ui.link"
                                                class="text-xs"
                                            >
                                                Reversar
                                            </button>
                                        </Form>
                                    </td>
                                </tr>
                                <tr v-if="!ledger.data.length">
                                    <td
                                        colspan="6"
                                        class="py-8 text-center text-ink-500"
                                    >
                                        Sin movimientos todavía.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        class="mt-4"
                        :links="ledger.links"
                        :from="ledger.from"
                        :to="ledger.to"
                        :total="ledger.total"
                    />
                </Card>

                <Card v-if="sales.length" title="Últimas firmas vendidas">
                    <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                        <li
                            v-for="sale in sales"
                            :key="sale.id"
                            class="flex items-center gap-3 py-2.5 text-sm"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-ink-950 dark:text-white">
                                    {{ sale.product_name }}
                                </p>
                                <p class="font-mono text-xs text-ink-500">
                                    {{ sale.provider_token ?? 'sin token' }} ·
                                    {{ dateTime(sale.submitted_at) }}
                                </p>
                            </div>
                            <div class="text-right text-xs tabular-nums">
                                <p class="text-ink-950 dark:text-white">
                                    {{
                                        sale.sale_price
                                            ? money(sale.sale_price)
                                            : '—'
                                    }}
                                </p>
                                <p class="text-ink-500">
                                    central
                                    {{
                                        sale.unit_price
                                            ? money(sale.unit_price)
                                            : '—'
                                    }}
                                </p>
                            </div>
                            <Badge :tone="signatureStatusTone(sale.status)">{{
                                sale.status_label
                            }}</Badge>
                        </li>
                    </ul>
                </Card>
            </div>

            <aside class="col-span-12 space-y-6 xl:col-span-4">
                <QuotaCard v-if="account" :account="account" />

                <Card title="Afiliación">
                    <Form
                        v-if="can.update"
                        v-bind="
                            central.signatures.accounts.configure.form(
                                tenant.id,
                            )
                        "
                        #default="{ errors, processing }"
                        class="space-y-4"
                    >
                        <div>
                            <label for="affiliation_mode" :class="ui.label"
                                >Modalidad</label
                            >
                            <select
                                id="affiliation_mode"
                                v-model="mode"
                                name="affiliation_mode"
                                :class="ui.input"
                            >
                                <option
                                    v-for="(label, value) in affiliationModes"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <p :class="ui.help">
                                Crédito: cada firma se carga a su precio contra
                                un cupo. Prepago: vende solo los paquetes
                                comprados.
                            </p>
                        </div>
                        <div v-if="mode === 'Credit'">
                            <label for="credit_limit" :class="ui.label"
                                >Cupo de crédito (USD)</label
                            >
                            <input
                                id="credit_limit"
                                name="credit_limit"
                                type="number"
                                step="0.01"
                                min="0"
                                :value="account?.credit_limit ?? ''"
                                :class="ui.input"
                            />
                            <p v-if="errors.credit_limit" :class="ui.error">
                                {{ errors.credit_limit }}
                            </p>
                        </div>
                        <div>
                            <input type="hidden" name="is_active" value="0" />
                            <label
                                class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                            >
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    :checked="account?.is_active ?? true"
                                    :class="ui.checkbox"
                                />
                                Puede vender firmas
                            </label>
                        </div>
                        <div>
                            <label for="notes" :class="ui.label">Notas</label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                :value="account?.notes ?? ''"
                                :class="ui.input"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="processing"
                            :class="[ui.buttonPrimary, 'w-full']"
                        >
                            {{
                                account
                                    ? 'Guardar afiliación'
                                    : 'Afiliar tenant'
                            }}
                        </button>
                    </Form>
                    <p v-else class="text-sm text-ink-600 dark:text-ink-400">
                        {{
                            account
                                ? account.affiliation_mode_label
                                : 'Tenant sin afiliar.'
                        }}
                    </p>
                </Card>
            </aside>
        </div>
    </CentralLayout>
</template>
