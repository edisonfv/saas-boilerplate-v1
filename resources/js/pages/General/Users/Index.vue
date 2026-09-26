<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import tenant from '@/routes/tenant';

interface UserRow {
    id: string;
    name: string;
    email: string;
    roles: string[];
}

const props = defineProps<{
    users: {
        data: UserRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        owners: number;
    };
    can: {
        update: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    tenant.users.index().url,
    ['users'],
);
</script>

<template>
    <Head title="Usuarios" />

    <GeneralLayout title="Usuarios">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-ink-900 dark:text-white">
                    Usuarios
                </h2>
                <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                    {{ stats.total }} usuarios, {{ stats.owners }} con rol
                    owner.
                </p>
            </div>

            <Card>
                <div class="mb-4 flex justify-end">
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
                                        Nombre
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
                                        @click="toggleSort('email')"
                                    >
                                        Email
                                        <span v-if="sortIndicator('email')">{{
                                            sortIndicator('email') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3">Roles</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr v-for="user in props.users.data" :key="user.id">
                                <td
                                    class="px-4 py-4 font-medium text-ink-900 dark:text-white"
                                >
                                    {{ user.name }}
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ user.email }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <Badge
                                            v-for="role in user.roles"
                                            :key="role"
                                            tone="blue"
                                            >{{ role }}</Badge
                                        >
                                        <span
                                            v-if="user.roles.length === 0"
                                            class="text-ink-400"
                                            >Sin rol</span
                                        >
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Link
                                        v-if="can.update"
                                        :href="tenant.users.edit(user.id).url"
                                        class="text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="props.users.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay usuarios que coincidan con la
                                    búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.users.links"
                    :from="props.users.from"
                    :to="props.users.to"
                    :total="props.users.total"
                />
            </Card>
        </div>
    </GeneralLayout>
</template>
