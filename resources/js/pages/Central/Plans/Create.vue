<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/PlanController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
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
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a planes
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Catálogo central
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Crear plan
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                >
                    Define el nombre, precios por periodo, módulos, features y
                    límites que incluye este plan.
                </p>
            </div>

            <Form
                autocomplete="off"
                :action="store().url"
                method="post"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Datos generales">
                    <div
                        class="grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label for="name" class="form-label">
                                Nombre
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autofocus
                                class="form-control w-full"
                            />
                            <p v-if="errors.name" class="form-error">
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label for="slug" class="form-label"> Slug </label>
                            <input
                                id="slug"
                                type="text"
                                name="slug"
                                required
                                class="form-control w-full"
                            />
                            <p v-if="errors.slug" class="form-error">
                                {{ errors.slug }}
                            </p>
                        </div>

                        <div>
                            <label for="trial_days" class="form-label">
                                Días de trial (opcional)
                            </label>
                            <input
                                id="trial_days"
                                type="number"
                                name="trial_days"
                                min="0"
                                class="form-control w-full"
                            />
                            <p v-if="errors.trial_days" class="form-error">
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
                            class="flex flex-col gap-3 rounded-lg border border-ink-200 p-3 sm:flex-row sm:items-center dark:border-ink-800"
                        >
                            <label
                                class="flex w-40 shrink-0 items-center gap-2 text-sm font-medium text-ink-700 dark:text-ink-300"
                            >
                                <input
                                    type="checkbox"
                                    :name="`prices[${period}][enabled]`"
                                    value="1"
                                    class="form-check"
                                />
                                {{ label }}
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                :name="`prices[${period}][price]`"
                                placeholder="Precio"
                                class="form-control w-full max-w-32"
                            />
                            <input
                                type="text"
                                :name="`prices[${period}][currency]`"
                                value="USD"
                                maxlength="3"
                                placeholder="USD"
                                class="form-control w-full max-w-20 uppercase"
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
                                class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                            >
                                <input
                                    type="checkbox"
                                    name="modules[]"
                                    :value="module.id"
                                    class="form-check"
                                />
                                {{ module.name }}
                            </label>
                            <p
                                v-if="modules.length === 0"
                                class="text-sm text-ink-400"
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
                                class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                            >
                                <input
                                    type="checkbox"
                                    name="features[]"
                                    :value="feature.id"
                                    class="form-check"
                                />
                                {{ feature.name }}
                            </label>
                            <p
                                v-if="features.length === 0"
                                class="text-sm text-ink-400"
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
                                    class="mb-1 block text-sm text-ink-700 dark:text-ink-300"
                                >
                                    {{ limitType.name }}
                                    <span
                                        v-if="limitType.unit"
                                        class="text-ink-400"
                                        >({{ limitType.unit }})</span
                                    >
                                </label>
                                <input
                                    :id="`limit-${limitType.id}`"
                                    type="number"
                                    min="0"
                                    :name="`limits[${limitType.id}]`"
                                    class="form-control w-full"
                                />
                            </div>
                            <p
                                v-if="limitTypes.length === 0"
                                class="text-sm text-ink-400"
                            >
                                No hay tipos de límite creados todavía.
                            </p>
                        </div>
                    </Card>
                </div>

                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Crear plan"
                    processing-label="Creando…"
                    :cancel-href="central.plans.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
