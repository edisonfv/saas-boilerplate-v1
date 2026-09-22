<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/FeatureController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

const props = defineProps<{
    modules: { id: string; name: string }[];
    feature: {
        id: string;
        slug: string;
        name: string;
        module_id: string | null;
    };
}>();
</script>

<template>
    <Head :title="`Editar ${props.feature.name}`" />

    <CentralLayout :title="`Editar ${props.feature.name}`">
        <div class="space-y-6">
            <Link
                :href="central.features.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a features
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
                    Editar feature
                </h2>
            </div>

            <Card title="Datos de la feature">
                <Form
                    :action="update(props.feature.id).url"
                    method="patch"
                    #default="{ errors, processing }"
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
                            :value="props.feature.name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">
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
                            :value="props.feature.slug"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.slug" class="mt-1 text-sm text-red-600">
                            {{ errors.slug }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            for="module_id"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Módulo (opcional)
                        </label>
                        <select
                            id="module_id"
                            name="module_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option
                                value=""
                                :selected="props.feature.module_id === null"
                            >
                                Transversal (ningún módulo)
                            </option>
                            <option
                                v-for="module in modules"
                                :key="module.id"
                                :value="module.id"
                                :selected="
                                    props.feature.module_id === module.id
                                "
                            >
                                {{ module.name }}
                            </option>
                        </select>
                        <p
                            v-if="errors.module_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.module_id }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            {{
                                processing ? 'Guardando...' : 'Guardar cambios'
                            }}
                        </button>
                    </div>
                </Form>
            </Card>
        </div>
    </CentralLayout>
</template>
