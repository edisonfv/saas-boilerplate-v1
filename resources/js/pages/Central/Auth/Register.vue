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
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Acceso central
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Crear cuenta de staff
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
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

                    <div>
                        <label for="email" class="form-label"> Email </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="username"
                            class="form-control w-full"
                        />
                        <p v-if="errors.email" class="form-error">
                            {{ errors.email }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="role" class="form-label"> Rol </label>
                        <select
                            id="role"
                            name="role"
                            required
                            class="form-control w-full"
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
                        <p v-if="errors.role" class="form-error">
                            {{ errors.role }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="form-label">
                            Contraseña
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
                        <label for="password_confirmation" class="form-label">
                            Confirmar contraseña
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
                            {{ processing ? 'Creando...' : 'Crear cuenta' }}
                        </button>
                    </div>
                </Form>
            </Card>
        </div>
    </CentralLayout>
</template>
