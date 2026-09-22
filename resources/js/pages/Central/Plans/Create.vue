<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/PlanController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

defineProps<{
    modules: { id: string; name: string }[];
    features: { id: string; name: string }[];
    limitTypes: {
        id: string;
        key: string;
        name: string;
        unit: string | null;
    }[];
    billingPeriods: Record<string, string>;
}>();
</script>

<template>
    <Head title="Nuevo plan" />

    <CentralLayout title="Nuevo plan">
        <div class="space-y-6">
            <Link
                :href="central.plans.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a planes
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
                    Crear plan
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                >
                    Define el nombre, precios por periodo, módulos, features y
                    límites que incluye este plan.
                </p>
            </div>

            <Form
                :action="store().url"
                method="post"
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
                                class="w-full max-w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <input
                                type="text"
                                :name="`prices[${period}][currency]`"
                                value="USD"
                                maxlength="3"
                                placeholder="USD"
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
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                />
                                {{ module.name }}
                            </label>
                            <p
                                v-if="modules.length === 0"
                                class="text-sm text-slate-400"
                            >
                                No hay módulos creados todavía.
                            </p>
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
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                />
                                {{ feature.name }}
                            </label>
                            <p
                                v-if="features.length === 0"
                                class="text-sm text-slate-400"
                            >
                                No hay features creadas todavía.
                            </p>
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
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <p
                                v-if="limitTypes.length === 0"
                                class="text-sm text-slate-400"
                            >
                                No hay tipos de límite creados todavía.
                            </p>
                        </div>
                    </Card>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                >
                    {{ processing ? 'Creando...' : 'Crear plan' }}
                </button>
            </Form>
        </div>
    </CentralLayout>
</template>
