<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/General/Http/Controllers/RoleController';
import Card from '@/components/Card.vue';
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
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
            >
                Volver a roles
            </Link>

            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                Crear rol
            </h2>

            <Form
                :action="store().url"
                method="post"
                #default="{ errors, processing }"
                class="space-y-6"
            >
                <Card title="Datos generales">
                    <div class="max-w-md">
                        <label
                            for="name"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Nombre
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            required
                            autofocus
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                            {{ errors.name }}
                        </p>
                    </div>
                </Card>

                <Card title="Permisos">
                    <div v-if="permissions.length > 0" class="space-y-5">
                        <div v-for="group in permissions" :key="group.group">
                            <p
                                class="mb-2 text-xs font-semibold text-gray-500 uppercase dark:text-gray-400"
                            >
                                {{ group.group }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <label
                                    v-for="permission in group.items"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.id"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    {{ permission.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                        Este tenant aún no tiene módulos contratados, por lo que
                        no hay permisos disponibles para asignar. Los permisos
                        aparecerán aquí cuando se contrate un módulo.
                    </p>
                </Card>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ processing ? 'Creando...' : 'Crear rol' }}
                </button>
            </Form>
        </div>
    </GeneralLayout>
</template>
