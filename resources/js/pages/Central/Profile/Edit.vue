<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update as updatePassword } from '@/actions/Modules/Central/Http/Controllers/Auth/PasswordController';
import {
    destroy as destroyProfile,
    update as updateProfile,
} from '@/actions/Modules/Central/Http/Controllers/ProfileController';
import Card from '@/components/Card.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';

defineProps<{
    status?: string | null;
}>();

const page = usePage();
const authUser = computed(
    () => page.props.auth?.user as { name: string; email: string } | null,
);
</script>

<template>
    <Head title="Perfil" />

    <CentralLayout title="Perfil">
        <div class="space-y-6">
            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Cuenta central
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Perfil y seguridad
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                >
                    Administra tus datos de staff, credenciales y cierre de
                    cuenta.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <Card title="Datos de perfil">
                        <Form
                            autocomplete="off"
                            v-bind="updateProfile.form()"
                            #default="{
                                errors,
                                processing,
                                recentlySuccessful,
                            }"
                            class="grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2"
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
                                    :value="authUser?.name"
                                    class="form-control w-full"
                                />
                                <p v-if="errors.name" class="form-error">
                                    {{ errors.name }}
                                </p>
                            </div>

                            <div>
                                <label for="email" class="form-label">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    :value="authUser?.email"
                                    class="form-control w-full"
                                />
                                <p v-if="errors.email" class="form-error">
                                    {{ errors.email }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 sm:col-span-2">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{
                                        processing ? 'Guardando...' : 'Guardar'
                                    }}
                                </button>

                                <p
                                    v-if="recentlySuccessful"
                                    class="text-sm text-emerald-600 dark:text-emerald-400"
                                >
                                    Guardado.
                                </p>
                            </div>
                        </Form>
                    </Card>

                    <Card title="Cambiar contraseña">
                        <Form
                            autocomplete="off"
                            v-bind="updatePassword.form()"
                            #default="{ errors, processing }"
                            reset-on-success
                            class="grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div class="sm:col-span-2">
                                <label
                                    for="current_password"
                                    class="form-label"
                                >
                                    Contraseña actual
                                </label>
                                <input
                                    id="current_password"
                                    type="password"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                    class="form-control w-full"
                                />
                                <p
                                    v-if="errors.current_password"
                                    class="form-error"
                                >
                                    {{ errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label for="password" class="form-label">
                                    Nueva contraseña
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    class="form-control w-full"
                                />
                                <p v-if="errors.password" class="form-error">
                                    {{ errors.password }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="password_confirmation"
                                    class="form-label"
                                >
                                    Confirmar nueva contraseña
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="form-control w-full"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{
                                        processing
                                            ? 'Guardando...'
                                            : 'Actualizar contraseña'
                                    }}
                                </button>
                            </div>
                        </Form>
                    </Card>
                </div>

                <Card title="Eliminar cuenta">
                    <p class="mb-4 text-sm text-ink-500 dark:text-ink-400">
                        Esta acción elimina tu acceso central. Se pedirá tu
                        contraseña para confirmar.
                    </p>

                    <Form
                        autocomplete="off"
                        v-bind="destroyProfile.form()"
                        #default="{ errors, processing }"
                        class="space-y-4"
                    >
                        <div>
                            <label for="delete_password" class="form-label">
                                Contraseña
                            </label>
                            <input
                                id="delete_password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="form-control w-full focus:border-red-500 focus:ring-red-500/15"
                            />
                            <p v-if="errors.password" class="form-error">
                                {{ errors.password }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-50"
                        >
                            {{
                                processing ? 'Eliminando...' : 'Eliminar cuenta'
                            }}
                        </button>
                    </Form>
                </Card>
            </div>
        </div>
    </CentralLayout>
</template>
