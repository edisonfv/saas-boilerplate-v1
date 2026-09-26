<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/General/Http/Controllers/RoleController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import tenant from '@/routes/tenant';

interface PermissionGroup {
    group: string;
    items: { id: string; name: string; label: string }[];
}

defineProps<{
    permissions: PermissionGroup[];
}>();
</script>

<template>
    <Head title="Nuevo rol" />

    <GeneralLayout title="Nuevo rol">
        <div class="space-y-6">
            <Link
                :href="tenant.roles.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white"
            >
                Volver a roles
            </Link>

            <h2 class="text-xl font-semibold text-ink-900 dark:text-white">
                Crear rol
            </h2>

            <Form
                autocomplete="off"
                :action="store().url"
                method="post"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Datos generales">
                    <div class="max-w-md">
                        <label for="name" class="form-label"> Nombre </label>
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
                </Card>

                <Card title="Permisos">
                    <div v-if="permissions.length > 0" class="space-y-5">
                        <div v-for="group in permissions" :key="group.group">
                            <p
                                class="mb-2 text-xs font-semibold text-ink-500 uppercase dark:text-ink-400"
                            >
                                {{ group.group }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <label
                                    v-for="permission in group.items"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.id"
                                        class="form-check"
                                    />
                                    {{ permission.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ink-500 dark:text-ink-400">
                        Este tenant aún no tiene módulos contratados, por lo que
                        no hay permisos disponibles para asignar. Los permisos
                        aparecerán aquí cuando se contrate un módulo.
                    </p>
                </Card>

                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Crear rol"
                    processing-label="Creando…"
                    :cancel-href="tenant.roles.index().url"
                />
            </Form>
        </div>
    </GeneralLayout>
</template>
