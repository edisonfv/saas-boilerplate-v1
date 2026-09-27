<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { money } from '@/lib/signatures';
import type { CentralSignatureProduct } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

defineProps<{
    products: (CentralSignatureProduct & {
        packages: {
            id: string;
            name: string;
            quantity: number;
            price: string;
            unit_price: string;
            is_active: boolean;
        }[];
    })[];
    can: { create: boolean; update: boolean };
}>();
</script>

<template>
    <Head title="Catálogo de firmas" />

    <CentralLayout title="Catálogo de firmas">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Firmas electrónicas · Uanataca
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Productos y paquetes
                    </h2>
                    <p class="mt-1 text-sm text-ink-500">
                        El precio de crédito se cobra por firma emitida a los
                        tenants en crédito; los paquetes se venden a los tenants
                        prepago.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.signatures.products.create().url"
                    :class="ui.buttonPrimary"
                >
                    <Icon name="plus" class="size-4.5" /> Nuevo producto
                </Link>
            </div>

            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-ink-500 uppercase">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">
                                    Producto
                                </th>
                                <th class="py-2 pr-4 font-semibold">
                                    Costo Uanataca
                                </th>
                                <th class="py-2 pr-4 font-semibold">
                                    Precio crédito
                                </th>
                                <th class="py-2 pr-4 font-semibold">
                                    PVP sugerido / mínimo
                                </th>
                                <th class="py-2 pr-4 font-semibold">
                                    Paquetes prepago
                                </th>
                                <th class="py-2 font-semibold" />
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="product in products" :key="product.id">
                                <td class="py-3 pr-4">
                                    <p
                                        class="font-semibold text-ink-950 dark:text-white"
                                    >
                                        {{ product.name }}
                                        <Badge
                                            v-if="!product.is_active"
                                            tone="red"
                                            >Inactivo</Badge
                                        >
                                    </p>
                                    <p class="text-xs text-ink-500">
                                        {{ product.validity_label }} ·
                                        {{ product.container_label }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4 tabular-nums">
                                    <template v-if="product.provider_cost">
                                        {{ money(product.provider_cost) }}
                                    </template>
                                    <Badge v-else tone="amber">Sin costo</Badge>
                                </td>
                                <td class="py-3 pr-4 tabular-nums">
                                    {{ money(product.credit_unit_price) }}
                                    <p
                                        v-if="product.credit_unit_margin"
                                        class="text-xs text-ink-500"
                                    >
                                        Utilidad
                                        {{ money(product.credit_unit_margin) }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4 tabular-nums">
                                    {{
                                        product.suggested_retail_price
                                            ? money(
                                                  product.suggested_retail_price,
                                              )
                                            : '—'
                                    }}
                                    <p
                                        v-if="product.min_retail_price"
                                        class="text-xs text-ink-500"
                                    >
                                        Mín.
                                        {{ money(product.min_retail_price) }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        v-for="pack in product.packages"
                                        :key="pack.id"
                                        :class="[
                                            'mr-1.5 mb-1 inline-block rounded-md px-2 py-0.5 text-xs',
                                            pack.is_active
                                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                                                : 'bg-ink-100 text-ink-400 line-through dark:bg-ink-800',
                                        ]"
                                        >{{ pack.quantity }} ×
                                        {{ money(pack.price) }}</span
                                    >
                                    <span
                                        v-if="!product.packages.length"
                                        class="text-ink-400"
                                        >—</span
                                    >
                                </td>
                                <td class="py-3 text-right">
                                    <Link
                                        v-if="can.update"
                                        :href="
                                            central.signatures.products.edit(
                                                product.id,
                                            ).url
                                        "
                                        :class="ui.link"
                                        >Editar</Link
                                    >
                                </td>
                            </tr>
                            <tr v-if="!products.length">
                                <td
                                    colspan="6"
                                    class="py-10 text-center text-ink-500"
                                >
                                    Aún no hay productos de firma.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </CentralLayout>
</template>
