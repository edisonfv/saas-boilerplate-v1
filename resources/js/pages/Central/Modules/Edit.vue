<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/ModuleController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

const props = defineProps<{
    billingPeriods: Record<string, string>;
    actions: Record<string, string>;
    module: {
        id: string;
        slug: string;
        name: string;
        sellable_as_addon: boolean;
        prices: Record<string, { price: string; currency: string }>;
        permissions: string[];
    };
}>();
</script>

<template>
    <Head :title="`Editar ${props.module.name}`" />

    <CentralLayout :title="`Editar ${props.module.name}`">
        <div class="space-y-6">
            <Link
                :href="central.modules.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a módulos
            </Link>

            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Capacidades vendibles
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Editar módulo
                </h2>
            </div>

            <Form
                :action="update(props.module.id).url"
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
                                :value="props.module.name"
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
                                :value="props.module.slug"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <p
                                v-if="errors.slug"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.slug }}
                            </p>
                        </div>

                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2 dark:text-slate-300"
                        >
                            <input
                                type="checkbox"
                                name="sellable_as_addon"
                                value="1"
                                :checked="props.module.sellable_as_addon"
                                class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                            />
                            Se puede vender como addon independiente
                        </label>
                    </div>
                </Card>

                <Card title="Precios de addon (si aplica)">
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
                                    :checked="!!props.module.prices[period]"
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
                                :value="props.module.prices[period]?.price"
                                class="w-full max-w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <input
                                type="text"
                                :name="`prices[${period}][currency]`"
                                :value="
                                    props.module.prices[period]?.currency ??
                                    'USD'
                                "
                                maxlength="3"
                                class="w-full max-w-20 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 uppercase focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                        </div>
                    </div>
                </Card>

                <Card title="Permisos que ofrece este módulo">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="(label, action) in actions"
                            :key="action"
                            class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                :value="action"
                                :checked="
                                    props.module.permissions.includes(action)
                                "
                                class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                            />
                            {{ label }}
                        </label>
                    </div>
                </Card>

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
