<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SignatureApplicationForm from '@/components/signatures/SignatureApplicationForm.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import type {
    SignatureAccount,
    SignatureFormOptions,
    SignatureProduct,
} from '@/lib/signatures';
import tenant from '@/routes/tenant';

const props = defineProps<
    Omit<SignatureFormOptions, 'products'> & {
        products: SignatureProduct[];
        account: SignatureAccount | null;
    }
>();
</script>

<template>
    <Head title="Nueva venta de firma" />

    <GeneralLayout title="Nueva venta de firma">
        <SignatureApplicationForm
            :options="props"
            :action="tenant.signatures.requests.store().url"
            show-sale-price
            submit-label="Guardar solicitud"
            :cancel-href="tenant.signatures.requests.index().url"
        />
    </GeneralLayout>
</template>
