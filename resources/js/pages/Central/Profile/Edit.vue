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
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Cuenta central
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Perfil y seguridad
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                >
                    Administra tus datos de staff, credenciales y cierre de
                    cuenta.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <Card title="Datos de perfil">
                        <p
                            v-if="status === 'profile-updated'"
                            class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            Perfil actualizado.
                        </p>

                        <Form
                            v-bind="updateProfile.form()"
                            #default="{
                                errors,
                                processing,
                                recentlySuccessful,
                            }"
                            class="grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
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
                                    :value="authUser?.name"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                                <p
                                    v-if="errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="email"
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                                >
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    :value="authUser?.email"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                                <p
                                    v-if="errors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 sm:col-span-2">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
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
                        <p
                            v-if="status === 'password-updated'"
                            class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            Contraseña actualizada.
                        </p>

                        <Form
                            v-bind="updatePassword.form()"
                            #default="{ errors, processing }"
                            reset-on-success
                            class="grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div class="sm:col-span-2">
                                <label
                                    for="current_password"
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                                >
                                    Contraseña actual
                                </label>
                                <input
                                    id="current_password"
                                    type="password"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                                <p
                                    v-if="errors.current_password"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="password"
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                                >
                                    Nueva contraseña
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                                <p
                                    v-if="errors.password"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.password }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="password_confirmation"
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                                >
                                    Confirmar nueva contraseña
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
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
                    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                        Esta acción elimina tu acceso central. Se pedirá tu
                        contraseña para confirmar.
                    </p>

                    <Form
                        v-bind="destroyProfile.form()"
                        #default="{ errors, processing }"
                        class="space-y-4"
                    >
                        <div>
                            <label
                                for="delete_password"
                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Contraseña
                            </label>
                            <input
                                id="delete_password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <p
                                v-if="errors.password"
                                class="mt-1 text-sm text-red-600"
                            >
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
