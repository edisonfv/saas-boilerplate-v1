<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { toggleActive } from '@/actions/Modules/Central/Http/Controllers/LimitTypeController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface LimitTypeRow {
    id: string;
    key: string;
    name: string;
    unit: string | null;
    is_active: boolean;
    plans_count: number;
}

const props = defineProps<{
    limitTypes: {
        data: LimitTypeRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    can: {
        create: boolean;
        update: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    central.limitTypes.index().url,
    ['limitTypes'],
);
</script>

<template>
    <Head title="Límites" />

    <CentralLayout title="Límites">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Catálogo central
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Tipos de límite
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Contadores comerciales (usuarios, almacenamiento, etc.)
                        que un plan puede acotar con un valor.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.limitTypes.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo límite
                </Link>
            </div>

            <Card>
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-ink-950 dark:text-white"
                        >
                            Inventario de límites
                        </h3>
                    </div>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre..."
                        class="form-control w-full sm:w-64"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-y border-ink-200 bg-ink-50 text-xs font-semibold text-ink-500 uppercase dark:border-ink-800 dark:bg-ink-950/50 dark:text-ink-400"
                            >
                                <th class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1"
                                        @click="toggleSort('name')"
                                    >
                                        Límite
                                        <span v-if="sortIndicator('name')">{{
                                            sortIndicator('name') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1"
                                        @click="toggleSort('key')"
                                    >
                                        Key
                                        <span v-if="sortIndicator('key')">{{
                                            sortIndicator('key') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-right">Planes</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="limitType in props.limitTypes.data"
                                :key="limitType.id"
                                class="transition hover:bg-ink-50/80 dark:hover:bg-ink-800/40"
                            >
                                <td class="px-4 py-4">
                                    <p
                                        class="font-medium text-ink-950 dark:text-white"
                                    >
                                        {{ limitType.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-ink-500">
                                        {{ limitType.unit ?? '—' }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ limitType.key }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-ink-900 dark:text-white"
                                >
                                    {{ limitType.plans_count }}
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            limitType.is_active
                                                ? 'green'
                                                : 'gray'
                                        "
                                    >
                                        {{
                                            limitType.is_active
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
                                                central.limitTypes.edit(
                                                    limitType.id,
                                                ).url
                                            "
                                            class="text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                                            title="Editar"
                                        >
                                            <Icon name="pencil" />
                                        </Link>
                                        <Form
                                            autocomplete="off"
                                            v-bind="
                                                toggleActive.form(limitType.id)
                                            "
                                            #default="{ processing }"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="text-ink-500 hover:text-ink-950 disabled:opacity-50 dark:text-ink-400 dark:hover:text-white"
                                                :title="
                                                    limitType.is_active
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
                            <tr v-if="props.limitTypes.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay tipos de límite que coincidan con la
                                    búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.limitTypes.links"
                    :from="props.limitTypes.from"
                    :to="props.limitTypes.to"
                    :total="props.limitTypes.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
