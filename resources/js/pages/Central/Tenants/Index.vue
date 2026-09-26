<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { subscriptionStatusTone } from '@/lib/status';
import central from '@/routes/central';

interface TenantRow {
    id: string;
    company_name: string | null;
    domain: string | null;
    tenant_status: string;
    tenant_status_label: string;
    plan_name: string | null;
    status: string | null;
    status_label: string | null;
    trial_ends_at: string | null;
    created_at: string;
}

const props = defineProps<{
    tenants: {
        data: TenantRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    statuses: Record<string, string>;
    stats: {
        total: number;
        withSubscription: number;
        withoutSubscription: number;
    };
    can: {
        create: boolean;
    };
}>();

const status = ref('');

const { search, toggleSort, sortIndicator, reload } = useListingFilters(
    central.tenants.index().url,
    ['tenants'],
    {},
    () =>
        status.value
            ? { 'filter[status]': status.value }
            : ({} as Record<string, string>),
);
</script>

<template>
    <Head title="Tenants" />

    <CentralLayout title="Tenants">
        <div class="space-y-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Estado comercial
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Tenants provisionados
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Revisa dominio, plan contratado y estado de suscripción
                        de cada cuenta.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.tenants.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo tenant
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Total tenants"
                    :value="stats.total"
                    icon="building"
                    helper="Cuentas registradas"
                />
                <StatCard
                    label="Con suscripción"
                    :value="stats.withSubscription"
                    icon="check-circle"
                    helper="Plan asignado"
                />
                <StatCard
                    label="Sin suscripción"
                    :value="stats.withoutSubscription"
                    icon="x-circle"
                    helper="Requieren atención"
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
                            Directorio de tenants
                        </h3>
                        <p class="text-sm text-ink-500 dark:text-ink-400">
                            Acceso rápido a suscripción y entitlements.
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Buscar tenant…"
                            class="form-control w-full sm:w-56"
                        />
                        <select
                            v-model="status"
                            class="form-control w-full sm:w-48"
                            @change="reload"
                        >
                            <option value="">Todos los estados</option>
                            <option
                                v-for="(label, value) in statuses"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
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
                                        @click="toggleSort('id')"
                                    >
                                        Tenant
                                        <span v-if="sortIndicator('id')">{{
                                            sortIndicator('id') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3">Empresa</th>
                                <th class="px-4 py-3">Dominio</th>
                                <th class="px-4 py-3">Plan</th>
                                <th class="px-4 py-3">Operación</th>
                                <th class="px-4 py-3">Suscripción</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="tenant in props.tenants.data"
                                :key="tenant.id"
                                class="transition hover:bg-ink-50/80 dark:hover:bg-ink-800/40"
                            >
                                <td class="px-4 py-4">
                                    <Link
                                        :href="
                                            central.tenants.show(tenant.id).url
                                        "
                                        class="inline-flex items-center gap-2 font-medium text-ink-950 hover:text-ink-700 dark:text-white dark:hover:text-ink-200"
                                    >
                                        {{ tenant.id }}
                                        <Icon name="arrow-right" />
                                    </Link>
                                    <p class="mt-1 text-xs text-ink-500">
                                        Creado {{ tenant.created_at }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ tenant.company_name ?? 'Sin empresa' }}
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ tenant.domain ?? 'Sin dominio' }}
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{ tenant.plan_name ?? 'Sin suscripción' }}
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            tenant.tenant_status === 'Active'
                                                ? 'green'
                                                : 'red'
                                        "
                                    >
                                        {{ tenant.tenant_status_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        v-if="tenant.status_label"
                                        :tone="
                                            subscriptionStatusTone(
                                                tenant.status,
                                            )
                                        "
                                    >
                                        {{ tenant.status_label }}
                                    </Badge>
                                    <span v-else class="text-ink-400">
                                        Sin estado
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="props.tenants.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay tenants que coincidan con la
                                    búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.tenants.links"
                    :from="props.tenants.from"
                    :to="props.tenants.to"
                    :total="props.tenants.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
