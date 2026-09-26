<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/LimitTypeController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

const props = defineProps<{
    limitType: {
        id: string;
        key: string;
        name: string;
        unit: string | null;
    };
}>();
</script>

<template>
    <Head :title="`Editar ${props.limitType.name}`" />

    <CentralLayout :title="`Editar ${props.limitType.name}`">
        <div class="space-y-6">
            <Link
                :href="central.limitTypes.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a límites
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Catálogo central
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Editar tipo de límite
                </h2>
            </div>

            <Form
                autocomplete="off"
                :action="update(props.limitType.id).url"
                method="patch"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Datos del límite">
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
                                :value="props.limitType.name"
                                class="form-control w-full"
                            />
                            <p v-if="errors.name" class="form-error">
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label for="key" class="form-label"> Clave </label>
                            <input
                                id="key"
                                type="text"
                                name="key"
                                required
                                :value="props.limitType.key"
                                class="form-control w-full"
                            />
                            <p v-if="errors.key" class="form-error">
                                {{ errors.key }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="unit" class="form-label">
                                Unidad (opcional)
                            </label>
                            <input
                                id="unit"
                                type="text"
                                name="unit"
                                :value="props.limitType.unit"
                                class="form-control w-full max-w-xs"
                            />
                            <p v-if="errors.unit" class="form-error">
                                {{ errors.unit }}
                            </p>
                        </div>
                    </div>
                </Card>
                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Guardar cambios"
                    processing-label="Guardando…"
                    :cancel-href="central.limitTypes.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
