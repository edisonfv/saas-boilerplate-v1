<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import QuotaCard from '@/components/signatures/QuotaCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { ledgerEntryTone, money, signatureStatusTone } from '@/lib/signatures';
import type { SignatureAccount } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

const props = defineProps<{
    tenant: { id: string; company_name: string | null };
    account: SignatureAccount | null;
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
                <Link
                    :href="central.signatures.accounts.index().url"
                    class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                >
                    <Icon name="arrow-left" /> Volver a cuentas
                </Link>
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-8">
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
