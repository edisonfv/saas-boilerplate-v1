<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/RoleController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

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

    <CentralLayout title="Nuevo rol">
        <div class="space-y-6">
            <Link
                :href="central.roles.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a roles
            </Link>

            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Control de acceso
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Crear rol
                </h2>
            </div>

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
                </Card>

                <Card title="Permisos">
                    <div class="space-y-5">
                        <div v-for="group in permissions" :key="group.group">
                            <p
                                class="mb-2 text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ group.group }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <label
                                    v-for="permission in group.items"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.id"
                                        class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                                    />
                                    {{ permission.label }}
                                </label>
                            </div>
                        </div>
                        <p
                            v-if="permissions.length === 0"
                            class="text-sm text-slate-500 dark:text-slate-400"
                        >
                            No hay permisos disponibles.
                        </p>
                    </div>
                </Card>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                >
                    {{ processing ? 'Creando...' : 'Crear rol' }}
                </button>
            </Form>
        </div>
    </CentralLayout>
</template>
