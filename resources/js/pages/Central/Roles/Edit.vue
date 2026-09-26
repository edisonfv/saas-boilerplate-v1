<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/RoleController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface PermissionGroup {
    group: string;
    items: { id: string; name: string; label: string }[];
}

const props = defineProps<{
    permissions: PermissionGroup[];
    role: {
        id: string;
        name: string;
    };
    assigned_permission_ids: string[];
}>();
</script>

<template>
    <Head :title="`Editar ${props.role.name}`" />

    <CentralLayout :title="`Editar ${props.role.name}`">
        <div class="space-y-6">
            <Link
                :href="central.roles.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a roles
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Control de acceso
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Editar rol
                </h2>
            </div>

            <Form
                autocomplete="off"
                :action="update(props.role.id).url"
                method="patch"
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
                            :value="props.role.name"
                            class="form-control w-full"
                        />
                        <p v-if="errors.name" class="form-error">
                            {{ errors.name }}
                        </p>
                    </div>
                </Card>

                <Card title="Permisos">
                    <div class="space-y-5">
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
                                        :checked="
                                            props.assigned_permission_ids.includes(
                                                permission.id,
                                            )
                                        "
                                        class="form-check"
                                    />
                                    {{ permission.label }}
                                </label>
                            </div>
                        </div>
                        <p
                            v-if="permissions.length === 0"
                            class="text-sm text-ink-500 dark:text-ink-400"
                        >
                            No hay permisos disponibles.
                        </p>
                    </div>
                </Card>

                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Guardar cambios"
                    processing-label="Guardando…"
                    :cancel-href="central.roles.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
