<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/StaffController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

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

    <CentralLayout :title="`Editar ${props.user.name}`">
        <div class="space-y-6">
            <Link
                :href="central.staff.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a staff
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
                    {{ props.user.name }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
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
                            class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                        >
                            <input
                                type="checkbox"
                                name="roles[]"
                                :value="role.id"
                                :checked="
                                    props.assigned_role_ids.includes(role.id)
                                "
                                class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
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
                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                >
                    {{ processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
            </Form>
        </div>
    </CentralLayout>
</template>
