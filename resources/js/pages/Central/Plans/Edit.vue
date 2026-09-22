<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/PlanController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

const props = defineProps<{
    modules: { id: string; name: string }[];
    features: { id: string; name: string }[];
    limitTypes: {
        id: string;
        key: string;
        name: string;
        unit: string | null;
    }[];
    billingPeriods: Record<string, string>;
    plan: {
        id: string;
        slug: string;
        name: string;
        trial_days: number | null;
        prices: Record<string, { price: string; currency: string }>;
        module_ids: string[];
        feature_ids: string[];
        limits: Record<string, number>;
    };
}>();
</script>

<template>
    <Head :title="`Editar ${props.plan.name}`" />

    <CentralLayout :title="`Editar ${props.plan.name}`">
        <div class="space-y-6">
            <Link
                :href="central.plans.show(props.plan.id).url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver al plan
            </Link>

            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Catálogo central
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Editar plan
                </h2>
            </div>

            <Form
                :action="update(props.plan.id).url"
                method="patch"
                #default="{ errors, processing }"
                class="space-y-6"
            >
                <Card title="Datos generales">
                    <div
                        class="grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                for="name"
                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Nombre
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autofocus
                                :value="props.plan.name"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <p
                                v-if="errors.name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="slug"
                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Slug
                            </label>
                            <input
                                id="slug"
                                type="text"
                                name="slug"
                                required
                                :value="props.plan.slug"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <p
                                v-if="errors.slug"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.slug }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="trial_days"
                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Días de trial (opcional)
                            </label>
                            <input
                                id="trial_days"
                                type="number"
                                name="trial_days"
                                min="0"
                                :value="props.plan.trial_days"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <p
                                v-if="errors.trial_days"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.trial_days }}
                            </p>
                        </div>
                    </div>
                </Card>

                <Card title="Precios por periodo">
                    <div class="space-y-4">
                        <div
                            v-for="(label, period) in billingPeriods"
                            :key="period"
                            class="flex flex-col gap-3 rounded-lg border border-slate-200 p-3 sm:flex-row sm:items-center dark:border-slate-800"
                        >
                            <label
                                class="flex w-40 shrink-0 items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                <input
                                    type="checkbox"
                                    :name="`prices[${period}][enabled]`"
                                    value="1"
                                    :checked="!!props.plan.prices[period]"
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                />
                                {{ label }}
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                :name="`prices[${period}][price]`"
                                placeholder="Precio"
                                :value="props.plan.prices[period]?.price"
                                class="w-full max-w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <input
                                type="text"
                                :name="`prices[${period}][currency]`"
                                :value="
                                    props.plan.prices[period]?.currency ?? 'USD'
                                "
                                maxlength="3"
                                class="w-full max-w-20 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 uppercase focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                        </div>
                    </div>
                </Card>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card title="Módulos">
                        <div class="space-y-2">
                            <label
                                v-for="module in modules"
                                :key="module.id"
                                class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                            >
                                <input
                                    type="checkbox"
                                    name="modules[]"
                                    :value="module.id"
                                    :checked="
                                        props.plan.module_ids.includes(
                                            module.id,
                                        )
                                    "
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                />
                                {{ module.name }}
                            </label>
                        </div>
                    </Card>

                    <Card title="Features">
                        <div class="space-y-2">
                            <label
                                v-for="feature in features"
                                :key="feature.id"
                                class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                            >
                                <input
                                    type="checkbox"
                                    name="features[]"
                                    :value="feature.id"
                                    :checked="
                                        props.plan.feature_ids.includes(
                                            feature.id,
                                        )
                                    "
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                />
                                {{ feature.name }}
                            </label>
                        </div>
                    </Card>

                    <Card title="Límites">
                        <div class="space-y-3">
                            <div
                                v-for="limitType in limitTypes"
                                :key="limitType.id"
                            >
                                <label
                                    :for="`limit-${limitType.id}`"
                                    class="mb-1 block text-sm text-slate-700 dark:text-slate-300"
                                >
                                    {{ limitType.name }}
                                    <span
                                        v-if="limitType.unit"
                                        class="text-slate-400"
                                        >({{ limitType.unit }})</span
                                    >
                                </label>
                                <input
                                    :id="`limit-${limitType.id}`"
                                    type="number"
                                    min="0"
                                    :name="`limits[${limitType.id}]`"
                                    :value="props.plan.limits[limitType.id]"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                        </div>
                    </Card>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                >
                    {{ processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
            </Form>
        </div>
    </CentralLayout>
</template>
