<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import { useDateTime } from '@/composables/useDateTime';
import { money, paymentReviewTone, paymentStatusTone } from '@/lib/signatures';
import type { PaymentRecord } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

/**
 * Payment of a signature request in the workspace: its status, the
 * customer's payment link (copy / WhatsApp / email), receipts to confirm
 * or reject, and registering a payment taken at the counter. The request
 * can only be sent to the provider once paid.
 */
const props = defineProps<{
    requestId: string;
    status: string;
    statusLabel: string;
    salePrice: string | null;
    payments: PaymentRecord[];
    paymentLink: string | null;
    whatsappUrl: string | null;
    methods: Record<string, string>;
    canManage: boolean;
}>();

const { dateTime } = useDateTime();
const copied = ref(false);
const rejecting = ref<string | null>(null);
const registering = ref(false);
const method = ref('Cash');
const pendingReceipt = computed(() =>
    props.payments.find((payment) => payment.review === 'Pending'),
);

async function copyLink() {
    if (props.paymentLink) {
        await navigator.clipboard?.writeText(props.paymentLink);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
}
</script>

<template>
    <Card title="Pago del cliente">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <Badge :tone="paymentStatusTone(status)">{{
                    statusLabel
                }}</Badge>
                <span
                    v-if="salePrice"
                    class="text-sm font-semibold text-ink-700 tabular-nums dark:text-ink-300"
                    >{{ money(salePrice) }}</span
                >
            </div>
            <p v-if="status !== 'Paid'" class="text-xs text-ink-500">
                Confirma el pago para poder enviarla a Uanataca.
            </p>
        </div>

        <!-- Receipt waiting for review -->
        <div
            v-if="pendingReceipt && canManage"
            class="mt-4 rounded-lg border border-primary-200 bg-primary-50/60 p-4 dark:border-primary-500/30 dark:bg-primary-500/10"
        >
            <p class="font-semibold text-ink-950 dark:text-white">
                Comprobante por revisar
            </p>
            <p class="mt-1 text-sm text-ink-700 dark:text-ink-300">
                {{ pendingReceipt.method_label }} ·
                {{ money(pendingReceipt.amount) }}
                <template v-if="pendingReceipt.reference">
                    · Ref. {{ pendingReceipt.reference }}</template
                >
                · {{ dateTime(pendingReceipt.created_at) }}
            </p>
            <a
                v-if="pendingReceipt.receipt_url"
                :href="pendingReceipt.receipt_url"
                target="_blank"
                class="mt-2 inline-flex items-center gap-1.5 text-sm"
                :class="ui.link"
            >
                <Icon name="paperclip" class="size-4" /> Ver comprobante
            </a>

            <div
                v-if="rejecting !== pendingReceipt.id"
                class="mt-3 flex flex-wrap gap-2"
            >
                <Form
                    v-bind="
                        tenant.signatures.requests.payments.approve.form({
                            signatureRequest: requestId,
                            payment: pendingReceipt.id,
                        })
                    "
                    #default="{ processing }"
                >
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="ui.buttonPrimary"
                    >
                        <Icon name="check-circle" class="size-4.5" /> Confirmar
                        pago
                    </button>
                </Form>
                <button
                    type="button"
                    :class="ui.buttonDanger"
                    @click="rejecting = pendingReceipt.id"
                >
                    Rechazar
                </button>
            </div>
            <Form
                v-else
                v-bind="
                    tenant.signatures.requests.payments.reject.form({
                        signatureRequest: requestId,
                        payment: pendingReceipt.id,
                    })
                "
                #default="{ processing, errors }"
                class="mt-3 space-y-2"
                @success="rejecting = null"
            >
                <label for="reject-reason" :class="ui.label"
                    >Motivo (lo verá el cliente)</label
                >
                <input
                    id="reject-reason"
                    name="reason"
                    required
                    maxlength="500"
                    placeholder="Ej.: el monto no coincide, comprobante ilegible"
                    :class="ui.input"
                />
                <p v-if="errors.reason" :class="ui.error">
                    {{ errors.reason }}
                </p>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="ui.buttonDanger"
                    >
                        Rechazar comprobante
                    </button>
                    <button
                        type="button"
                        :class="ui.buttonSecondary"
                        @click="rejecting = null"
                    >
                        Cancelar
                    </button>
                </div>
            </Form>
        </div>

        <!-- Payment link for the customer -->
        <div v-if="paymentLink && canManage" class="mt-4 space-y-2">
            <p class="text-sm font-semibold text-ink-950 dark:text-white">
                Enlace de pago del cliente
            </p>
            <div class="flex gap-2">
                <input
                    :value="paymentLink"
                    readonly
                    aria-label="Enlace de pago"
                    class="form-control min-w-0 flex-1 font-mono text-xs"
                    @focus="($event.target as HTMLInputElement).select()"
                />
                <button
                    type="button"
                    :class="ui.buttonSecondary"
                    @click="copyLink"
                >
                    {{ copied ? 'Copiado' : 'Copiar' }}
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    v-if="whatsappUrl"
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#25D366] px-3 text-sm font-semibold text-white hover:brightness-95"
                >
                    <Icon name="chat" class="size-4" /> Enviar por WhatsApp
                </a>
                <Form
                    v-bind="
                        tenant.signatures.requests.payments.link.form(requestId)
                    "
                    #default="{ processing }"
                >
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="[ui.buttonSecondary, 'h-9']"
                    >
                        Reenviar por correo
                    </button>
                </Form>
            </div>
        </div>

        <!-- Register a payment taken at the counter / seen in the bank -->
        <template v-if="status !== 'Paid' && canManage">
            <button
                v-if="!registering"
                type="button"
                class="mt-4 text-sm"
                :class="ui.link"
                @click="registering = true"
            >
                + Registrar pago recibido
            </button>
            <Form
                v-else
                v-bind="
                    tenant.signatures.requests.payments.store.form(requestId)
                "
                #default="{ processing, errors }"
                class="mt-4 grid gap-3 rounded-lg bg-ink-50 p-4 sm:grid-cols-2 dark:bg-ink-800/40"
                @success="registering = false"
            >
                <div>
                    <label for="payment-method" :class="ui.label"
                        >Medio de pago</label
                    >
                    <select
                        id="payment-method"
                        v-model="method"
                        name="method"
                        :class="ui.input"
                    >
                        <option
                            v-for="(label, value) in methods"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label for="payment-amount" :class="ui.label"
                        >Monto recibido</label
                    >
                    <input
                        id="payment-amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        :value="salePrice ?? ''"
                        :class="ui.input"
                    />
                </div>
                <div v-if="method !== 'Cash'" class="sm:col-span-2">
                    <label for="payment-reference" :class="ui.label"
                        >Número de comprobante</label
                    >
                    <input
                        id="payment-reference"
                        name="reference"
                        required
                        :class="ui.input"
                    />
                </div>
                <div class="sm:col-span-2">
                    <label for="payment-receipt" :class="ui.label"
                        >Comprobante (opcional)</label
                    >
                    <input
                        id="payment-receipt"
                        name="receipt"
                        type="file"
                        accept="image/*,application/pdf"
                        class="text-sm text-ink-600 dark:text-ink-300"
                    />
                </div>
                <p
                    v-if="errors.method || errors.amount || errors.reference"
                    :class="[ui.error, 'sm:col-span-2']"
                >
                    {{ errors.method ?? errors.amount ?? errors.reference }}
                </p>
                <div class="flex gap-2 sm:col-span-2">
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="ui.buttonPrimary"
                    >
                        Registrar pago
                    </button>
                    <button
                        type="button"
                        :class="ui.buttonSecondary"
                        @click="registering = false"
                    >
                        Cancelar
                    </button>
                </div>
            </Form>
        </template>

        <!-- History -->
        <ul
            v-if="payments.length"
            class="mt-5 divide-y divide-ink-100 border-t border-ink-100 dark:divide-ink-800 dark:border-ink-800"
        >
            <li
                v-for="payment in payments"
                :key="payment.id"
                class="flex items-start justify-between gap-3 py-3 text-sm"
            >
                <div class="min-w-0">
                    <p class="font-medium text-ink-950 dark:text-white">
                        {{ payment.method_label }} ·
                        {{ money(payment.amount) }}
                        <span
                            v-if="payment.reference"
                            class="font-normal text-ink-500"
                            >· Ref. {{ payment.reference }}</span
                        >
                    </p>
                    <p class="text-xs text-ink-500">
                        {{ dateTime(payment.created_at) }}
                        <template v-if="payment.reported_by_name">
                            · {{ payment.reported_by_name }}</template
                        >
                        <template v-if="payment.reviewed_by_name">
                            · revisado por
                            {{ payment.reviewed_by_name }}</template
                        >
                    </p>
                    <p
                        v-if="payment.rejection_reason"
                        class="mt-0.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ payment.rejection_reason }}
                    </p>
                    <a
                        v-if="
                            payment.receipt_url && payment.review !== 'Pending'
                        "
                        :href="payment.receipt_url"
                        target="_blank"
                        class="text-xs"
                        :class="ui.link"
                        >Ver comprobante</a
                    >
                </div>
                <Badge :tone="paymentReviewTone(payment.review)">{{
                    payment.review_label
                }}</Badge>
            </li>
        </ul>
    </Card>
</template>
