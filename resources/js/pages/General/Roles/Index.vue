<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { destroy } from '@/actions/Modules/General/Http/Controllers/RoleController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import tenant from '@/routes/tenant';

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
    tenant.roles.index().url,
    ['roles'],
);
</script>

<template>
    <Head title="Roles" />

    <GeneralLayout title="Roles">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <h2
                        class="text-xl font-semibold text-ink-900 dark:text-white"
                    >
                        Roles
                    </h2>
                    <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                        Crea roles combinando los permisos disponibles ({{
                            stats.totalPermissions
                        }}) y asígnalos a tus usuarios.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="tenant.roles.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    Nuevo rol
                </Link>
            </div>

            <Card>
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-ink-500 dark:text-ink-400">
                        El rol owner está protegido y no puede editarse ni
                        eliminarse.
                    </p>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre..."
                        class="form-control w-full sm:w-64"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
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
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="role in props.roles.data" :key="role.id">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-medium text-ink-900 dark:text-white"
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
                                    class="px-4 py-4 text-right font-medium text-ink-900 dark:text-white"
                                >
                                    {{ role.permissions_count }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-ink-900 dark:text-white"
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
                                                tenant.roles.edit(role.id).url
                                            "
                                            class="text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white"
                                        >
                                            Editar
                                        </Link>
                                        <Form
                                            autocomplete="off"
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
                                                class="text-ink-500 hover:text-red-600 disabled:opacity-50 dark:text-ink-400 dark:hover:text-red-400"
                                            >
                                                Eliminar
                                            </button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.roles.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
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
    </GeneralLayout>
</template>
