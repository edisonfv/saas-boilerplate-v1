<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import ProductFields from '@/components/signatures/ProductFields.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

defineProps<{
    validities: Record<string, string>;
    containers: Record<string, string>;
}>();
</script>

<template>
    <Head title="Nuevo producto de firma" />

    <CentralLayout title="Nuevo producto de firma">
        <Form
            autocomplete="off"
            v-bind="central.signatures.products.store.form()"
            #default="{ errors, processing, isDirty }"
            class="grid grid-cols-12 gap-6"
        >
            <Card title="Producto" class="col-span-12 xl:col-span-8">
                <ProductFields
                    :validities="validities"
                    :containers="containers"
                    :errors="errors"
                />
            </Card>
            <FormActions
                :processing="processing"
                :is-dirty="isDirty"
                submit-label="Crear producto"
                :cancel-href="central.signatures.products.index().url"
            />
        </Form>
    </CentralLayout>
</template>
