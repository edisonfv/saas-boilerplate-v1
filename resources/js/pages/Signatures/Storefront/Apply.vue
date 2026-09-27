<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SignatureApplicationForm from '@/components/signatures/SignatureApplicationForm.vue';
import StorefrontShell from '@/components/signatures/StorefrontShell.vue';
import type { SignatureFormOptions, StorefrontInfo } from '@/lib/signatures';
import { home } from '@/routes';
import tenant from '@/routes/tenant';

const props = defineProps<
    SignatureFormOptions & {
        storefront: StorefrontInfo;
        selectedProductId: string | null;
    }
>();

const options = computed<SignatureFormOptions>(() => ({
    products: props.products,
    applicantTypes: props.applicantTypes,
    documentTypes: props.documentTypes,
    genders: props.genders,
    documentKinds: props.documentKinds,
    requiredDocuments: props.requiredDocuments,
}));
</script>

<template>
    <Head :title="`Solicita tu firma · ${storefront.company_name}`" />

    <StorefrontShell :storefront="storefront" :show-navigation="false">
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
            <div class="app-grid">
                <div class="col-span-12 lg:col-span-8 lg:col-start-3">
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Solicitud en línea
                    </p>
                    <h1
                        class="mt-2 text-3xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Empecemos
                    </h1>
                    <p class="mt-1 text-ink-600 dark:text-ink-400">
                        Completa tus datos tal como constan en tu cédula. Te
                        tomará unos minutos.
                    </p>
                </div>

                <SignatureApplicationForm
                    :options="options"
                    :action="tenant.signatures.storefront.store().url"
                    :selected-product-id="selectedProductId"
                    :whatsapp-url="storefront.whatsapp_url"
                    is-public
                    wizard
                    submit-label="Enviar solicitud"
                    :cancel-href="home().url"
                />
            </div>
        </div>
    </StorefrontShell>
</template>
