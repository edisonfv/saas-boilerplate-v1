<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import PublicShell from '@/components/PublicShell.vue';
import { money } from '@/lib/signatures';
import type { StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

defineProps<{
    storefront: StorefrontInfo;
    products: {
        id: string;
        name: string;
        validity_label: string;
        container_label: string;
        price: string | null;
    }[];
}>();

const benefits = [
    {
        icon: 'shield',
        title: 'Validez legal',
        text: 'Emitida por Uanataca, entidad de certificación acreditada en Ecuador.',
    },
    {
        icon: 'clock',
        title: 'Trámite 100% en línea',
        text: 'Sube tus documentos desde el celular, sin filas ni papeles.',
    },
    {
        icon: 'check-circle',
        title: 'Factura y firma',
        text: 'Úsala para facturación electrónica, contratos y trámites públicos.',
    },
] as const;
</script>

<template>
    <Head :title="`Firma electrónica · ${storefront.company_name}`" />

    <PublicShell :context="storefront.company_name">
        <section class="col-span-12">
            <div
                v-if="storefront.received_code"
                class="mb-6 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                <Icon name="check-circle" class="size-5 shrink-0" />
                <p>
                    Recibimos tu solicitud
                    <strong class="font-mono">{{
                        storefront.received_code
                    }}</strong
                    >. Te contactaremos para confirmar el pago y continuar con
                    la validación de identidad.
                </p>
            </div>

            <div
                class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 px-6 py-12 text-white shadow-lg sm:px-10"
            >
                <p class="eyebrow text-primary-100">
                    {{ storefront.company_name }}
                </p>
                <h1
                    class="mt-3 max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl"
                >
                    {{ storefront.headline }}
                </h1>
                <p
                    v-if="storefront.description"
                    class="mt-4 max-w-2xl text-primary-50/90"
                >
                    {{ storefront.description }}
                </p>
                <Link
                    :href="tenant.signatures.storefront.create().url"
                    class="mt-8 inline-flex h-11 items-center gap-2 rounded-lg bg-white px-5 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50"
                >
                    Solicitar mi firma
                    <Icon name="arrow-right" class="size-4" />
                </Link>
            </div>
        </section>

        <section class="col-span-12 grid gap-4 md:grid-cols-3">
            <div
                v-for="benefit in benefits"
                :key="benefit.title"
                class="rounded-xl border border-ink-200 bg-white p-5 dark:border-ink-800 dark:bg-ink-900"
            >
                <Icon :name="benefit.icon" class="size-6 text-primary-600" />
                <h3 class="mt-3 font-bold text-ink-950 dark:text-white">
                    {{ benefit.title }}
                </h3>
                <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                    {{ benefit.text }}
                </p>
            </div>
        </section>

        <section class="col-span-12">
            <h2 class="text-xl font-extrabold text-ink-950 dark:text-white">
                Elige tu firma
            </h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="flex flex-col rounded-xl border border-ink-200 bg-white p-5 dark:border-ink-800 dark:bg-ink-900"
                >
                    <p class="font-bold text-ink-950 dark:text-white">
                        {{ product.name }}
                    </p>
                    <p class="text-sm text-ink-500">
                        Vigencia {{ product.validity_label }} ·
                        {{ product.container_label }}
                    </p>
                    <p
                        class="mt-4 text-3xl font-extrabold text-ink-950 tabular-nums dark:text-white"
                    >
                        {{ product.price ? money(product.price) : 'Consultar' }}
                    </p>
                    <Link
                        :href="tenant.signatures.storefront.create().url"
                        :class="[ui.buttonSecondary, 'mt-5']"
                    >
                        Solicitar
                    </Link>
                </div>
            </div>
        </section>

        <section
            v-if="
                storefront.contact_email ||
                storefront.contact_phone ||
                storefront.whatsapp
            "
            class="col-span-12 flex flex-wrap gap-6 rounded-xl border border-ink-200 bg-white p-5 text-sm dark:border-ink-800 dark:bg-ink-900"
        >
            <span class="font-semibold text-ink-950 dark:text-white"
                >¿Dudas? Escríbenos</span
            >
            <a
                v-if="storefront.whatsapp"
                :href="`https://wa.me/${storefront.whatsapp.replace('+', '')}`"
                target="_blank"
                rel="noopener"
                :class="ui.link"
                >WhatsApp</a
            >
            <a
                v-if="storefront.contact_email"
                :href="`mailto:${storefront.contact_email}`"
                :class="ui.link"
                >{{ storefront.contact_email }}</a
            >
            <span
                v-if="storefront.contact_phone"
                class="text-ink-600 dark:text-ink-400"
                >{{ storefront.contact_phone }}</span
            >
        </section>
    </PublicShell>
</template>
