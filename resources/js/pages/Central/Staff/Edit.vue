<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/Modules/Central/Http/Controllers/StaffController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
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
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a staff
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Control de acceso
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    {{ props.user.name }}
                </h2>
                <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                    {{ props.user.email }}
                </p>
            </div>

            <Form
                autocomplete="off"
                :action="update(props.user.id).url"
                method="patch"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Roles asignados">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"
                        >
                            <input
                                type="checkbox"
                                name="roles[]"
                                :value="role.id"
                                :checked="
                                    props.assigned_role_ids.includes(role.id)
                                "
                                class="form-check"
                            />
                            {{ role.name }}
                        </label>
                    </div>
                    <p v-if="errors.roles" class="mt-3 text-sm text-red-600">
                        {{ errors.roles }}
                    </p>
                </Card>

                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Guardar cambios"
                    processing-label="Guardando…"
                    :cancel-href="central.staff.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
