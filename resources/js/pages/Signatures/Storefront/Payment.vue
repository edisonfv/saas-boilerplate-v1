<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import DocumentCapture from '@/components/signatures/DocumentCapture.vue';
import StorefrontShell from '@/components/signatures/StorefrontShell.vue';
import { money } from '@/lib/signatures';
import type { BankAccount, StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';

const props = defineProps<{
    storefront: StorefrontInfo;
    request: {
        code: string;
        applicant_name: string;
        product_name: string;
        amount: string | null;
        payment_status: 'Pending' | 'UnderReview' | 'Paid';
        payment_status_label: string;
        rejection_reason: string | null;
    };
    bankAccounts: BankAccount[];
    methods: Record<string, string>;
    /** The signed URL itself: posting to it keeps the signature valid. */
    action: string;
}>();

const form = useForm<{
    method: string;
    reference: string;
    receipt: File | null;
}>({
    method: Object.keys(props.methods)[0] ?? 'BankTransfer',
    reference: '',
    receipt: null,
});

const receiptSlot = {
    kind: 'Receipt',
    label: 'Comprobante de pago',
    accept: '.jpg,.jpeg,.png,.pdf',
    accepts_images: true,
    accepts_pdf: true,
    capture: 'environment' as const,
};

async function copy(text: string) {
    await navigator.clipboard?.writeText(text);
}

function submit() {
    form.post(props.action, { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <Head :title="`Pago de ${request.code} · ${storefront.company_name}`" />

    <StorefrontShell :storefront="storefront" :show-navigation="false">
        <div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-10 sm:px-6">
            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Solicitud {{ request.code }}
                </p>
                <h1
                    class="mt-2 text-3xl font-extrabold text-ink-950 dark:text-white"
                >
                    Pago de tu firma electrónica
                </h1>
                <p class="mt-1 text-ink-600 dark:text-ink-400">
                    {{ request.applicant_name }} · {{ request.product_name }}
                </p>
            </div>

            <!-- Paid / under review: nothing else to do here. -->
            <Card v-if="request.payment_status !== 'Pending'">
                <div class="flex items-start gap-4">
                    <div
                        :class="[
                            'flex size-12 shrink-0 items-center justify-center rounded-full',
                            request.payment_status === 'Paid'
                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10'
                                : 'bg-primary-50 text-primary-600 dark:bg-primary-500/10',
                        ]"
                    >
                        <Icon
                            :name="
                                request.payment_status === 'Paid'
                                    ? 'check-circle'
                                    : 'clock'
                            "
                            class="size-6"
                        />
                    </div>
                    <div>
                        <p class="font-bold text-ink-950 dark:text-white">
                            {{
                                request.payment_status === 'Paid'
                                    ? 'Pago confirmado'
                                    : 'Estamos verificando tu comprobante'
                            }}
                        </p>
                        <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                            {{
                                request.payment_status === 'Paid'
                                    ? 'Tu solicitud continúa con la validación de identidad. Te enviaremos tu firma por correo.'
                                    : 'Te avisaremos por correo cuando confirmemos tu pago.'
                            }}
                        </p>
                    </div>
                </div>
            </Card>

            <template v-else>
                <div
                    v-if="request.rejection_reason"
                    class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                >
                    <Icon name="exclamation-triangle" class="size-5 shrink-0" />
                    <p>
                        No pudimos confirmar tu comprobante anterior:
                        <strong>{{ request.rejection_reason }}</strong
                        >. Sube uno nuevo.
                    </p>
                </div>

                <Card title="1. Realiza el pago">
                    <p class="text-sm text-ink-600 dark:text-ink-400">
                        Valor a pagar
                    </p>
                    <p
                        class="text-4xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                    >
                        {{ request.amount ? money(request.amount) : '—' }}
                    </p>

                    <ul v-if="bankAccounts.length" class="mt-5 space-y-3">
                        <li
                            v-for="account in bankAccounts"
                            :key="account.number"
                            class="rounded-lg border border-ink-200 p-4 text-sm dark:border-ink-700"
                        >
                            <p class="font-bold text-ink-950 dark:text-white">
                                {{ account.bank }}
                            </p>
                            <p class="text-ink-600 dark:text-ink-400">
                                Cuenta {{ account.account_type }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="font-mono text-lg font-bold text-ink-950 dark:text-white"
                                    >{{ account.number }}</span
                                >
                                <button
                                    type="button"
                                    class="text-xs"
                                    :class="ui.link"
                                    @click="copy(account.number)"
                                >
                                    Copiar
                                </button>
                            </div>
                            <p class="mt-1 text-ink-600 dark:text-ink-400">
                                {{ account.holder }} · {{ account.holder_id }}
                            </p>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="mt-4 text-sm text-ink-600 dark:text-ink-400"
                    >
                        Escríbenos para recibir los datos de pago.
                    </p>
                </Card>

                <form @submit.prevent="submit">
                    <Card title="2. Envíanos tu comprobante">
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="(label, value) in methods"
                                    :key="value"
                                    :class="[
                                        'cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition',
                                        form.method === value
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                                            : 'border-ink-200 text-ink-700 dark:border-ink-700 dark:text-ink-200',
                                    ]"
                                >
                                    <input
                                        v-model="form.method"
                                        type="radio"
                                        :value="value"
                                        class="sr-only"
                                    />
                                    {{ label }}
                                </label>
                            </div>
                            <div>
                                <label for="reference" :class="ui.label"
                                    >Número de comprobante</label
                                >
                                <input
                                    id="reference"
                                    v-model="form.reference"
                                    maxlength="100"
                                    :class="ui.input"
                                />
                            </div>
                            <DocumentCapture
                                :document="receiptSlot"
                                :required="true"
                                :already-uploaded="false"
                                :file="form.receipt"
                                :error="form.errors.receipt"
                                @select="(file) => (form.receipt = file)"
                            />
                            <button
                                type="submit"
                                :disabled="form.processing || !form.receipt"
                                :class="[ui.buttonPrimary, 'h-11 w-full']"
                            >
                                {{
                                    form.processing
                                        ? 'Enviando…'
                                        : 'Enviar comprobante'
                                }}
                            </button>
                        </div>
                    </Card>
                </form>
            </template>

            <a
                v-if="storefront.whatsapp_url"
                :href="storefront.whatsapp_url"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 text-sm"
                :class="ui.link"
            >
                <Icon name="chat" class="size-4" /> ¿Dudas con el pago?
                Escríbenos por WhatsApp
            </a>
        </div>
    </StorefrontShell>
</template>
