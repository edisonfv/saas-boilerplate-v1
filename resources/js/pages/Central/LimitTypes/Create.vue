<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/LimitTypeController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';
</script>

<template>
    <Head title="Nuevo límite" />

    <CentralLayout title="Nuevo límite">
        <div class="space-y-6">
            <Link
                :href="central.limitTypes.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a límites
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
                    Crear tipo de límite
                </h2>
            </div>

            <Card title="Datos del límite">
                <Form
                    :action="store().url"
                    method="post"
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
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                            {{ errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="key"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Clave
                        </label>
                        <input
                            id="key"
                            type="text"
                            name="key"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.key" class="mt-1 text-sm text-red-600">
                            {{ errors.key }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            for="unit"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Unidad (opcional)
                        </label>
                        <input
                            id="unit"
                            type="text"
                            name="unit"
                            placeholder="GB, usuarios, etc."
                            class="w-full max-w-xs rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.unit" class="mt-1 text-sm text-red-600">
                            {{ errors.unit }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            {{ processing ? 'Creando...' : 'Crear límite' }}
                        </button>
                    </div>
                </Form>
            </Card>
        </div>
    </CentralLayout>
</template>
