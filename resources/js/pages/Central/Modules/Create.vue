<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/ModuleController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

defineProps<{
    billingPeriods: Record<string, string>;
    actions: Record<string, string>;
}>();
</script>

<template>
    <Head title="Nuevo módulo" />

    <CentralLayout title="Nuevo módulo">
        <div class="space-y-6">
            <Link
                :href="central.modules.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a módulos
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Capacidades vendibles
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Crear módulo
                </h2>
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

                        <label
                            class="flex items-center gap-2 text-sm text-ink-700 sm:col-span-2 dark:text-ink-300"
                        >
                            <input
                                type="checkbox"
                                name="sellable_as_addon"
                                value="1"
                                class="form-check"
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
                                class="form-control w-full max-w-20 uppercase"
                            />
                        </div>
                    </div>
                </Card>

                <Card title="Permisos que ofrece este módulo">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="(label, action) in actions"
                            :key="action"
                            class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                :value="action"
                                class="form-check"
                            />
                            {{ label }}
                        </label>
                    </div>
                </Card>

                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Crear módulo"
                    processing-label="Creando…"
                    :cancel-href="central.modules.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
