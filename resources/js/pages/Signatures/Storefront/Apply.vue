<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import SignatureApplicationForm from '@/components/signatures/SignatureApplicationForm.vue';
import type { SignatureFormOptions, StorefrontInfo } from '@/lib/signatures';
import tenant from '@/routes/tenant';

const props = defineProps<
    SignatureFormOptions & { storefront: StorefrontInfo }
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
    <Head :title="`Solicitar firma · ${storefront.company_name}`" />

    <PublicShell :context="storefront.company_name">
        <div class="col-span-12">
            <p class="eyebrow text-primary-600 dark:text-primary-400">
                Solicitud en línea
            </p>
            <h1
                class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
            >
                Solicita tu firma electrónica
            </h1>
            <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">
                Completa tus datos tal como constan en tu cédula y sube tus
                documentos. Revisaremos tu solicitud y te contactaremos.
            </p>
        </div>

        <SignatureApplicationForm
            :options="options"
            :action="tenant.signatures.storefront.store().url"
            is-public
            submit-label="Enviar solicitud"
            :cancel-href="tenant.signatures.storefront.show().url"
        />
    </PublicShell>
</template>
