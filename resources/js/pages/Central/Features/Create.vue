<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/FeatureController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

defineProps<{
    modules: { id: string; name: string }[];
}>();
</script>

<template>
    <Head title="Nueva feature" />

    <CentralLayout title="Nueva feature">
        <div class="space-y-6">
            <Link
                :href="central.features.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a features
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Catálogo central
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Crear feature
                </h2>
            </div>

            <Form
                autocomplete="off"
                :action="store().url"
                method="post"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Datos de la feature">
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

                        <div class="sm:col-span-2">
                            <label for="module_id" class="form-label">
                                Módulo (opcional)
                            </label>
                            <select
                                id="module_id"
                                name="module_id"
                                class="form-control w-full"
                            >
                                <option value="">
                                    Transversal (ningún módulo)
                                </option>
                                <option
                                    v-for="module in modules"
                                    :key="module.id"
                                    :value="module.id"
                                >
                                    {{ module.name }}
                                </option>
                            </select>
                            <p v-if="errors.module_id" class="form-error">
                                {{ errors.module_id }}
                            </p>
                        </div>
                    </div>
                </Card>
                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Crear feature"
                    processing-label="Creando…"
                    :cancel-href="central.features.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
