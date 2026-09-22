<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { toggleActive } from '@/actions/Modules/Central/Http/Controllers/ModuleController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface ModuleRow {
    id: string;
    slug: string;
    name: string;
    is_active: boolean;
    sellable_as_addon: boolean;
    permissions_count: number;
    features_count: number;
    prices: { billing_period_label: string; price: string; currency: string }[];
}

const props = defineProps<{
    modules: {
        data: ModuleRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        sellableAsAddon: number;
        permissionsCount: number;
    };
    can: {
        create: boolean;
        update: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    central.modules.index().url,
    ['modules'],
);
</script>

<template>
    <Head title="Módulos" />

    <CentralLayout title="Módulos">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Capacidades vendibles
                    </p>
                    <h2
                        class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                    >
                        Catálogo de módulos
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                    >
                        Los módulos declaran features, permisos blueprint y si
                        pueden contratarse como addons independientes.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.modules.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo módulo
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Total módulos"
                    :value="stats.total"
                    icon="cube"
                    helper="Registrados en catálogo"
                />
                <StatCard
                    label="Vendibles como addon"
                    :value="stats.sellableAsAddon"
                    icon="layers"
                    helper="Upsell modular"
                />
                <StatCard
                    label="Permisos blueprint"
                    :value="stats.permissionsCount"
                    icon="key"
                    helper="Sembrables en tenants"
                />
            </div>

            <Card>
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-950 dark:text-white"
                        >
                            Inventario de módulos
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Útil para revisar empaquetado, addons y permisos.
                        </p>
                    </div>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre..."
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 sm:w-64 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500 uppercase dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-400"
                            >
                                <th class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1"
                                        @click="toggleSort('name')"
                                    >
                                        Módulo
                                        <span v-if="sortIndicator('name')">{{
                                            sortIndicator('name') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-right">Permisos</th>
                                <th class="px-4 py-3 text-right">
                                    Features propias
                                </th>
                                <th class="px-4 py-3">Addon</th>
                                <th class="px-4 py-3">Precio addon</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <tr
                                v-for="module in props.modules.data"
                                :key="module.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="px-4 py-4">
                                    <p
                                        class="font-medium text-slate-950 dark:text-white"
                                    >
                                        {{ module.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ module.slug }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-slate-900 dark:text-white"
                                >
                                    {{ module.permissions_count }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-slate-900 dark:text-white"
                                >
                                    {{ module.features_count }}
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            module.sellable_as_addon
                                                ? 'blue'
                                                : 'gray'
                                        "
                                    >
                                        {{
                                            module.sellable_as_addon
                                                ? 'Sí'
                                                : 'No'
                                        }}
                                    </Badge>
                                </td>
                                <td
                                    class="px-4 py-4 text-slate-700 dark:text-slate-300"
                                >
                                    <template v-if="module.sellable_as_addon">
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="price in module.prices"
                                                :key="
                                                    price.billing_period_label
                                                "
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            >
                                                {{ price.currency }}
                                                {{ price.price }}
                                                <span class="text-slate-500">
                                                    /
                                                    {{
                                                        price.billing_period_label.toLowerCase()
                                                    }}
                                                </span>
                                            </span>
                                            <span
                                                v-if="
                                                    module.prices.length === 0
                                                "
                                                class="text-slate-400"
                                            >
                                                Sin precios
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else class="text-slate-400">
                                        No aplica
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            module.is_active ? 'green' : 'gray'
                                        "
                                    >
                                        {{
                                            module.is_active
                                                ? 'Activo'
                                                : 'Inactivo'
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div
                                        v-if="can.update"
                                        class="flex items-center justify-end gap-3"
                                    >
                                        <Link
                                            :href="
                                                central.modules.edit(module.id)
                                                    .url
                                            "
                                            class="text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
                                            title="Editar"
                                        >
                                            <Icon name="pencil" />
                                        </Link>
                                        <Form
                                            v-bind="
                                                toggleActive.form(module.id)
                                            "
                                            #default="{ processing }"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="text-slate-500 hover:text-slate-950 disabled:opacity-50 dark:text-slate-400 dark:hover:text-white"
                                                :title="
                                                    module.is_active
                                                        ? 'Desactivar'
                                                        : 'Activar'
                                                "
                                            >
                                                <Icon name="power" />
                                            </button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.modules.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400"
                                >
                                    No hay módulos que coincidan con la
                                    búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.modules.links"
                    :from="props.modules.from"
                    :to="props.modules.to"
                    :total="props.modules.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
