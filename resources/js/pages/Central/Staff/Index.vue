<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface StaffRow {
    id: string;
    name: string;
    email: string;
    roles: string[];
    email_verified_at: string | null;
}

const props = defineProps<{
    staff: {
        data: StaffRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        superAdmins: number;
    };
    can: {
        create: boolean;
        update: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    central.staff.index().url,
    ['staff'],
);
</script>

<template>
    <Head title="Staff" />

    <CentralLayout title="Staff">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Control de acceso
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Equipo central
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Consulta el staff registrado y sus roles asignados.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.register().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo staff
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <StatCard
                    label="Total staff"
                    :value="stats.total"
                    icon="users"
                    helper="Cuentas registradas"
                />
                <StatCard
                    label="Super-admins"
                    :value="stats.superAdmins"
                    icon="shield"
                    helper="Con acceso total"
                />
            </div>

            <Card>
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-ink-950 dark:text-white"
                        >
                            Directorio de staff
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
                    <table class="w-full min-w-[720px] text-left text-sm">
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
                                <th class="px-4 py-3">Verificado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="member in props.staff.data"
                                :key="member.id"
                                class="transition hover:bg-ink-50/80 dark:hover:bg-ink-800/40"
                            >
                                <td
                                    class="px-4 py-4 font-medium text-ink-950 dark:text-white"
                                >
                                    {{ member.name }}
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ member.email }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <Badge
                                            v-for="role in member.roles"
                                            :key="role"
                                            tone="blue"
                                            >{{ role }}</Badge
                                        >
                                        <span
                                            v-if="member.roles.length === 0"
                                            class="text-ink-400"
                                            >Sin rol</span
                                        >
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            member.email_verified_at
                                                ? 'green'
                                                : 'amber'
                                        "
                                    >
                                        {{
                                            member.email_verified_at
                                                ? 'Verificado'
                                                : 'Pendiente'
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Link
                                        v-if="can.update"
                                        :href="
                                            central.staff.edit(member.id).url
                                        "
                                        class="text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                                        title="Editar roles"
                                    >
                                        <Icon name="pencil" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="props.staff.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay staff que coincida con la búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.staff.links"
                    :from="props.staff.from"
                    :to="props.staff.to"
                    :total="props.staff.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
