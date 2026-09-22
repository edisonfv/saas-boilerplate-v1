<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/General/Http/Controllers/UserController';
import Card from '@/components/Card.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import tenant from '@/routes/tenant';

const props = defineProps<{
    user: {
        id: string;
        name: string;
        email: string;
    };
    roles: { id: string; name: string }[];
    assigned_role_ids: string[];
}>();
</script>

<template>
    <Head :title="`Editar ${props.user.name}`" />

    <GeneralLayout :title="`Editar ${props.user.name}`">
        <div class="space-y-6">
            <Link
                :href="tenant.users.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
            >
                Volver a usuarios
            </Link>

            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ props.user.name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ props.user.email }}
                </p>
            </div>

            <Form
                :action="update(props.user.id).url"
                method="patch"
                #default="{ errors, processing }"
                class="space-y-6"
            >
                <Card title="Roles asignados">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <input
                                type="checkbox"
                                name="roles[]"
                                :value="role.id"
                                :checked="
                                    props.assigned_role_ids.includes(role.id)
                                "
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            {{ role.name }}
                        </label>
                    </div>
                    <p v-if="errors.roles" class="mt-3 text-sm text-red-600">
                        {{ errors.roles }}
                    </p>
                </Card>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
            </Form>
        </div>
    </GeneralLayout>
</template>
