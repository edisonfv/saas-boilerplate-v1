<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/RegisteredUserController';
import Card from '@/components/Card.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';

defineProps<{
    roles: string[];
}>();
</script>

<template>
    <Head title="Nuevo staff" />

    <CentralLayout title="Nuevo staff">
        <div class="space-y-6">
            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Acceso central
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Crear cuenta de staff
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                >
                    Asigna un rol central para separar soporte, billing, ventas
                    y administración total.
                </p>
            </div>

            <Card title="Datos de la cuenta">
                <Form
                    :action="store().url"
                    method="post"
                    #default="{ errors, processing }"
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
                            autofocus
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">
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
                            autocomplete="username"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.email }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            for="role"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Rol
                        </label>
                        <select
                            id="role"
                            name="role"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="" disabled selected>
                                Selecciona un rol
                            </option>
                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                        <p v-if="errors.role" class="mt-1 text-sm text-red-600">
                            {{ errors.role }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Contraseña
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
                            Confirmar contraseña
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
                            {{ processing ? 'Creando...' : 'Crear cuenta' }}
                        </button>
                    </div>
                </Form>
            </Card>
        </div>
    </CentralLayout>
</template>
