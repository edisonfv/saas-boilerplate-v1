<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import StorefrontShell from '@/components/signatures/StorefrontShell.vue';
import { money } from '@/lib/signatures';
import type { StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import { home } from '@/routes';

defineProps<{
    storefront: StorefrontInfo;
    code: string;
    isPaid: boolean;
    amount: string | null;
    email: string;
    paymentUrl: string | null;
    whatsappUrl: string | null;
}>();
</script>

<template>
    <Head :title="`Solicitud recibida · ${storefront.company_name}`" />

    <StorefrontShell :storefront="storefront" :show-navigation="false">
        <div class="mx-auto w-full max-w-xl px-4 py-16 text-center sm:px-6">
            <div
                class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10"
            >
                <Icon name="check-circle" class="size-9" />
            </div>
            <h1
                class="mt-6 text-3xl font-extrabold text-ink-950 dark:text-white"
            >
                ¡Recibimos tu solicitud!
            </h1>
            <p class="mt-3 text-ink-600 dark:text-ink-400">
                Tu número de solicitud es
            </p>
            <p
                class="mt-1 font-mono text-2xl font-bold tracking-wider text-primary-700 dark:text-primary-300"
            >
                {{ code }}
            </p>

            <template v-if="isPaid">
                <p class="mt-6 text-ink-600 dark:text-ink-400">
                    Tu firma ya está pagada. Revisaremos tus datos y la entidad
                    certificadora validará tu identidad; te enviaremos tu firma
                    a <strong>{{ email }}</strong
                    >.
                </p>
            </template>
            <template v-else>
                <div
                    class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-left text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    <p class="font-bold">
                        Último paso: el pago{{
                            amount ? ` de ${money(amount)}` : ''
                        }}
                    </p>
                    <p class="mt-1">
                        Realiza la transferencia o depósito y sube tu
                        comprobante. Tu firma se emite una vez confirmado el
                        pago. También te enviamos el enlace a
                        <strong>{{ email }}</strong> (válido por 7 días).
                    </p>
                </div>
            </template>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a
                    v-if="paymentUrl"
                    :href="paymentUrl"
                    :class="[ui.buttonPrimary, 'h-11']"
                >
                    Pagar y subir comprobante
                </a>
                <a
                    v-if="whatsappUrl"
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#25D366] px-5 font-bold text-white transition hover:brightness-95"
                >
                    <Icon name="chat" class="size-4.5" /> Escríbenos por
                    WhatsApp
                </a>
                <Link :href="home().url" :class="ui.buttonSecondary">
                    Volver al inicio
                </Link>
            </div>
        </div>
    </StorefrontShell>
</template>
