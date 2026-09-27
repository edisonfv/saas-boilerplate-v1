<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import ProductFields from '@/components/signatures/ProductFields.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { money } from '@/lib/signatures';
import type { SignatureProduct } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

defineProps<{
    validities: Record<string, string>;
    containers: Record<string, string>;
    product: SignatureProduct & {
        packages: {
            id: string;
            name: string;
            quantity: number;
            price: string;
            unit_price: string;
            is_active: boolean;
        }[];
    };
}>();
</script>

<template>
    <Head :title="product.name" />

    <CentralLayout :title="product.name">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12">
                <Link
                    :href="central.signatures.products.index().url"
                    class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                >
                    <Icon name="arrow-left" /> Volver al catálogo
                </Link>
            </div>

            <Form
                autocomplete="off"
                v-bind="central.signatures.products.update.form(product.id)"
                #default="{ errors, processing, isDirty }"
                class="col-span-12 grid grid-cols-12 gap-6 xl:col-span-7"
            >
                <Card title="Producto" class="col-span-12">
                    <ProductFields
                        :validities="validities"
                        :containers="containers"
                        :errors="errors"
                        :product="product"
                    />
                </Card>
                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Guardar cambios"
                >
                    <template #secondary>
                        <Link
                            :href="
                                central.signatures.products.toggleActive(
                                    product.id,
                                ).url
                            "
                            method="patch"
                            as="button"
                            :class="ui.buttonSecondary"
                        >
                            {{ product.is_active ? 'Desactivar' : 'Activar' }}
                        </Link>
                    </template>
                </FormActions>
            </Form>

            <div class="col-span-12 space-y-6 xl:col-span-5">
                <Card title="Paquetes prepago">
                    <ul
                        class="mb-5 divide-y divide-ink-100 dark:divide-ink-800"
                    >
                        <li
                            v-for="pack in product.packages"
                            :key="pack.id"
                            class="flex items-center gap-3 py-3 text-sm"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-semibold text-ink-950 dark:text-white"
                                >
                                    {{ pack.name }}
                                    <Badge v-if="!pack.is_active" tone="red"
                                        >Inactivo</Badge
                                    >
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{ pack.quantity }} firmas ·
                                    {{ money(pack.unit_price) }} c/u
                                </p>
                            </div>
                            <span class="font-bold tabular-nums">{{
                                money(pack.price)
                            }}</span>
                            <Link
                                :href="
                                    central.signatures.products.packages.toggleActive(
                                        {
                                            product: product.id,
                                            package: pack.id,
                                        },
                                    ).url
                                "
                                method="patch"
                                as="button"
                                preserve-scroll
                                :class="ui.link"
                            >
                                {{ pack.is_active ? 'Desactivar' : 'Activar' }}
                            </Link>
                        </li>
                        <li
                            v-if="!product.packages.length"
                            class="py-3 text-sm text-ink-500"
                        >
                            Sin paquetes todavía.
                        </li>
                    </ul>

                    <Form
                        v-bind="
                            central.signatures.products.packages.store.form(
                                product.id,
                            )
                        "
                        reset-on-success
                        #default="{ errors, processing }"
                        class="grid gap-3 rounded-lg bg-ink-50 p-4 sm:grid-cols-3 dark:bg-ink-800/40"
                    >
                        <div class="sm:col-span-3">
                            <label for="package-name" :class="ui.label"
                                >Nombre</label
                            >
                            <input
                                id="package-name"
                                name="name"
                                required
                                placeholder="10 firmas de 1 año"
                                :class="ui.input"
                            />
                        </div>
                        <div>
                            <label for="package-quantity" :class="ui.label"
                                >Firmas</label
                            >
                            <input
                                id="package-quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                required
                                :class="ui.input"
                            />
                        </div>
                        <div>
                            <label for="package-price" :class="ui.label"
                                >Precio total</label
                            >
                            <input
                                id="package-price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                :class="ui.input"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="[ui.buttonPrimary, 'w-full']"
                            >
                                Agregar
                            </button>
                        </div>
                        <p
                            v-if="
                                errors.quantity || errors.price || errors.name
                            "
                            :class="[ui.error, 'sm:col-span-3']"
                        >
                            {{ errors.name ?? errors.quantity ?? errors.price }}
                        </p>
                    </Form>
                </Card>
            </div>
        </div>
    </CentralLayout>
</template>
