<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { destroy } from '@/actions/Modules/Central/Http/Controllers/RoleController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface RoleRow {
    id: string;
    name: string;
    permissions_count: number;
    users_count: number;
    is_protected: boolean;
}

const props = defineProps<{
    roles: {
        data: RoleRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        totalPermissions: number;
    };
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    central.roles.index().url,
    ['roles'],
);
</script>

<template>
    <Head title="Roles" />

    <CentralLayout title="Roles">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Control de acceso
                    </p>
                    <h2
                        class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                    >
                        Roles del equipo central
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                    >
                        Crea roles personalizados combinando los permisos
                        disponibles y asígnalos al staff.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.roles.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo rol
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <StatCard
                    label="Total roles"
                    :value="stats.total"
                    icon="shield"
                    helper="Configurados en central"
                />
                <StatCard
                    label="Permisos disponibles"
                    :value="stats.totalPermissions"
                    icon="key"
                    helper="Catálogo central"
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
                            Inventario de roles
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            El rol super-admin está protegido y no puede
                            editarse ni eliminarse.
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
                    <table class="w-full min-w-[640px] text-left text-sm">
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
                                        Rol
                                        <span v-if="sortIndicator('name')">{{
                                            sortIndicator('name') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-right">Permisos</th>
                                <th class="px-4 py-3 text-right">Usuarios</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <tr
                                v-for="role in props.roles.data"
                                :key="role.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-medium text-slate-950 dark:text-white"
                                            >{{ role.name }}</span
                                        >
                                        <Badge
                                            v-if="role.is_protected"
                                            tone="gray"
                                            >Protegido</Badge
                                        >
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-slate-900 dark:text-white"
                                >
                                    {{ role.permissions_count }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-slate-900 dark:text-white"
                                >
                                    {{ role.users_count }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-3"
                                    >
                                        <Link
                                            v-if="
                                                can.update && !role.is_protected
                                            "
                                            :href="
                                                central.roles.edit(role.id).url
                                            "
                                            class="text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
                                            title="Editar"
                                        >
                                            <Icon name="pencil" />
                                        </Link>
                                        <Form
                                            v-if="
                                                can.delete &&
                                                !role.is_protected &&
                                                role.users_count === 0
                                            "
                                            v-bind="destroy.form(role.id)"
                                            #default="{ processing }"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="text-slate-500 hover:text-red-600 disabled:opacity-50 dark:text-slate-400 dark:hover:text-red-400"
                                                title="Eliminar"
                                            >
                                                <Icon name="x-mark" />
                                            </button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.roles.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400"
                                >
                                    No hay roles que coincidan con la búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.roles.links"
                    :from="props.roles.from"
                    :to="props.roles.to"
                    :total="props.roles.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
