<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SignatureApplicationForm from '@/components/signatures/SignatureApplicationForm.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import type { SignatureFormOptions } from '@/lib/signatures';
import tenant from '@/routes/tenant';

const props = defineProps<
    SignatureFormOptions & {
        request: Record<string, string | null> & {
            id: string;
            code: string;
            uploaded_documents: string[];
        };
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
    <Head :title="`Editar ${request.code}`" />

    <GeneralLayout :title="`Editar ${request.code}`">
        <SignatureApplicationForm
            :options="options"
            :action="tenant.signatures.requests.update(request.id).url"
            method="put"
            :initial="request"
            :uploaded-documents="request.uploaded_documents"
            show-sale-price
            submit-label="Guardar cambios"
            :cancel-href="tenant.signatures.requests.show(request.id).url"
        />
    </GeneralLayout>
</template>
