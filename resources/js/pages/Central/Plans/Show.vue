<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { toggleActive } from '@/actions/Modules/Central/Http/Controllers/PlanController';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import StatCard from '@/components/StatCard.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface PlanDetail {
    id: string;
    slug: string;
    name: string;
    is_active: boolean;
    trial_days: number | null;
    prices: { billing_period_label: string; price: string; currency: string }[];
    modules: {
        id: string;
        slug: string;
        name: string;
        sellable_as_addon: boolean;
    }[];
    features: { id: string; slug: string; name: string }[];
    limits: {
        id: string;
        key: string;
        name: string;
        unit: string | null;
        value: number;
    }[];
}

defineProps<{
    plan: PlanDetail;
    can: {
        update: boolean;
    };
}>();
</script>

<template>
    <Head :title="plan.name" />

    <CentralLayout :title="plan.name">
        <div class="space-y-6">
            <Link
                :href="central.plans.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a planes
            </Link>

            <section
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="text-xl font-semibold text-slate-950 dark:text-white"
                            >
                                {{ plan.name }}
                            </h2>
                            <Badge :tone="plan.is_active ? 'green' : 'gray'">
                                {{ plan.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ plan.slug }}
                        </p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="rounded-lg border border-slate-200 px-4 py-3 text-sm dark:border-slate-800"
                        >
                            <p class="text-slate-500 dark:text-slate-400">
                                Trial
                            </p>
                            <p
                                class="mt-1 font-semibold text-slate-950 dark:text-white"
                            >
                                {{
                                    plan.trial_days
                                        ? `${plan.trial_days} días`
                                        : 'Sin trial'
                                }}
                            </p>
                        </div>

                        <template v-if="can.update">
                            <Link
                                :href="central.plans.edit(plan.id).url"
                                class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <Icon name="pencil" />
                                Editar
                            </Link>

                            <Form
                                v-bind="toggleActive.form(plan.id)"
                                #default="{ processing }"
                            >
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <Icon name="power" />
                                    {{
                                        plan.is_active
                                            ? 'Desactivar'
                                            : 'Activar'
                                    }}
                                </button>
                            </Form>
                        </template>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Precios"
                    :value="plan.prices.length"
                    icon="currency"
                    helper="Periodos de facturación"
                />
                <StatCard
                    label="Módulos"
                    :value="plan.modules.length"
                    icon="cube"
                    helper="Incluidos por plan"
                />
                <StatCard
                    label="Features"
                    :value="plan.features.length"
                    icon="layers"
                    helper="Capacidades habilitadas"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card title="Precios">
                    <ul class="space-y-3">
                        <li
                            v-for="price in plan.prices"
                            :key="price.billing_period_label"
                            class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400">
                                {{ price.billing_period_label }}
                            </span>
                            <span
                                class="font-medium text-slate-950 dark:text-white"
                            >
                                {{ price.currency }} {{ price.price }}
                            </span>
                        </li>
                        <li
                            v-if="plan.prices.length === 0"
                            class="text-sm text-slate-400"
                        >
                            Sin precios configurados.
                        </li>
                    </ul>
                </Card>

                <Card title="Módulos incluidos">
                    <ul class="space-y-3">
                        <li
                            v-for="module in plan.modules"
                            :key="module.id"
                            class="rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-800"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-slate-950 dark:text-white"
                                    >
                                        {{ module.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ module.slug }}
                                    </p>
                                </div>
                                <Badge
                                    v-if="module.sellable_as_addon"
                                    tone="blue"
                                >
                                    Addon
                                </Badge>
                            </div>
                        </li>
                        <li
                            v-if="plan.modules.length === 0"
                            class="text-sm text-slate-400"
                        >
                            Sin módulos.
                        </li>
                    </ul>
                </Card>

                <Card title="Features y límites">
                    <p
                        class="mb-2 text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Features
                    </p>
                    <div class="mb-5 flex flex-wrap gap-2">
                        <Badge
                            v-for="feature in plan.features"
                            :key="feature.id"
                            tone="gray"
                        >
                            {{ feature.name }}
                        </Badge>
                        <span
                            v-if="plan.features.length === 0"
                            class="text-sm text-slate-400"
                        >
                            Sin features.
                        </span>
                    </div>

                    <p
                        class="mb-2 text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                    >
                        Límites
                    </p>
                    <ul class="space-y-2">
                        <li
                            v-for="limit in plan.limits"
                            :key="limit.id"
                            class="flex items-center justify-between gap-4 text-sm"
                        >
                            <span class="text-slate-700 dark:text-slate-300">
                                {{ limit.name }}
                            </span>
                            <span
                                class="font-medium text-slate-950 dark:text-white"
                            >
                                {{ limit.value
                                }}{{ limit.unit ? ` ${limit.unit}` : '' }}
                            </span>
                        </li>
                        <li
                            v-if="plan.limits.length === 0"
                            class="text-sm text-slate-400"
                        >
                            Sin límites.
                        </li>
                    </ul>
                </Card>
            </div>
        </div>
    </CentralLayout>
</template>
