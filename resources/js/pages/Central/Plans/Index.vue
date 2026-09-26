<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { toggleActive } from '@/actions/Modules/Central/Http/Controllers/PlanController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import { useListingFilters } from '@/composables/useListingFilters';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface PlanPrice {
    billing_period_label: string;
    price: string;
    currency: string;
}

interface PlanRow {
    id: string;
    slug: string;
    name: string;
    is_active: boolean;
    trial_days: number | null;
    modules_count: number;
    features_count: number;
    prices: PlanPrice[];
}

const props = defineProps<{
    plans: {
        data: PlanRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        active: number;
        withTrial: number;
    };
    can: {
        create: boolean;
        update: boolean;
    };
}>();

const { search, toggleSort, sortIndicator } = useListingFilters(
    central.plans.index().url,
    ['plans'],
);
</script>

<template>
    <Head title="Planes" />

    <CentralLayout title="Planes">
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
                        Planes, precios y límites
                    </h2>
                    <p class="mt-2 text-sm text-ink-600 dark:text-ink-400">
                        Cada plan habilita módulos, features y límites
                        comerciales para los tenants.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="central.plans.create().url"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="size-4.5" />
                    Nuevo plan
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Total planes"
                    :value="stats.total"
                    icon="tag"
                    helper="Configurados en central"
                />
                <StatCard
                    label="Planes activos"
                    :value="stats.active"
                    icon="check-circle"
                    helper="Disponibles para contratar"
                />
                <StatCard
                    label="Con trial"
                    :value="stats.withTrial"
                    icon="clock"
                    helper="Prueba inicial definida"
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
                            Inventario de planes
                        </h3>
                        <p class="text-sm text-ink-500 dark:text-ink-400">
                            Vista resumida para comparar empaquetado comercial.
                        </p>
                    </div>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre..."
                        class="form-control w-full sm:w-64"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
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
                                        Plan
                                        <span v-if="sortIndicator('name')">{{
                                            sortIndicator('name') === 'asc'
                                                ? '↑'
                                                : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="px-4 py-3">Precios</th>
                                <th class="px-4 py-3 text-right">Módulos</th>
                                <th class="px-4 py-3 text-right">Features</th>
                                <th class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1"
                                        @click="toggleSort('trial_days')"
                                    >
                                        Trial
                                        <span
                                            v-if="sortIndicator('trial_days')"
                                            >{{
                                                sortIndicator('trial_days') ===
                                                'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </button>
                                </th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-ink-100 dark:divide-ink-800"
                        >
                            <tr
                                v-for="plan in props.plans.data"
                                :key="plan.id"
                                class="transition hover:bg-ink-50/80 dark:hover:bg-ink-800/40"
                            >
                                <td class="px-4 py-4">
                                    <Link
                                        :href="central.plans.show(plan.id).url"
                                        class="inline-flex items-center gap-2 font-medium text-ink-950 hover:text-ink-700 dark:text-white dark:hover:text-ink-200"
                                    >
                                        {{ plan.name }}
                                        <Icon name="arrow-right" />
                                    </Link>
                                    <p class="mt-1 text-xs text-ink-500">
                                        {{ plan.slug }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="price in plan.prices"
                                            :key="price.billing_period_label"
                                            class="rounded-lg bg-ink-100 px-2.5 py-1 text-xs font-medium text-ink-700 dark:bg-ink-800 dark:text-ink-200"
                                        >
                                            {{ price.currency }}
                                            {{ price.price }}
                                            <span class="text-ink-500">
                                                /
                                                {{
                                                    price.billing_period_label.toLowerCase()
                                                }}
                                            </span>
                                        </span>
                                        <span
                                            v-if="plan.prices.length === 0"
                                            class="text-ink-400"
                                        >
                                            Sin precios
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-ink-900 dark:text-white"
                                >
                                    {{ plan.modules_count }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-medium text-ink-900 dark:text-white"
                                >
                                    {{ plan.features_count }}
                                </td>
                                <td
                                    class="px-4 py-4 text-ink-700 dark:text-ink-300"
                                >
                                    {{
                                        plan.trial_days
                                            ? `${plan.trial_days} días`
                                            : 'Sin trial'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :tone="
                                            plan.is_active ? 'green' : 'gray'
                                        "
                                    >
                                        {{
                                            plan.is_active
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
                                                central.plans.edit(plan.id).url
                                            "
                                            class="text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
                                            title="Editar"
                                        >
                                            <Icon name="pencil" />
                                        </Link>
                                        <Form
                                            autocomplete="off"
                                            v-bind="toggleActive.form(plan.id)"
                                            #default="{ processing }"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="text-ink-500 hover:text-ink-950 disabled:opacity-50 dark:text-ink-400 dark:hover:text-white"
                                                :title="
                                                    plan.is_active
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
                            <tr v-if="props.plans.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-10 text-center text-sm text-ink-500 dark:text-ink-400"
                                >
                                    No hay planes que coincidan con la búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    class="mt-4"
                    :links="props.plans.links"
                    :from="props.plans.from"
                    :to="props.plans.to"
                    :total="props.plans.total"
                />
            </Card>
        </div>
    </CentralLayout>
</template>
