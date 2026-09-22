<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/General/Http/Controllers/Auth/AuthenticatedSessionController';
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        class="flex min-h-screen items-center justify-center bg-gray-50 p-6 dark:bg-gray-950"
    >
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center gap-2">
                <div
                    class="flex size-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-bold text-white"
                >
                    S
                </div>
                <span class="font-semibold text-gray-900 dark:text-white"
                    >SaaS</span
                >
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <h1
                    class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"
                >
                    Iniciar sesión
                </h1>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    Accede al panel de tu organización.
                </p>

                <Form
                    :action="store().url"
                    method="post"
                    #default="{ errors, processing }"
                    class="space-y-4"
                >
                    <div>
                        <label
                            for="email"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Email</label
                        >
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <p
                            v-if="errors.email"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Contraseña</label
                        >
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <p
                            v-if="errors.password"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ errors.password }}
                        </p>
                    </div>

                    <label
                        class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                    >
                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        Recordarme
                    </label>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Ingresando…' : 'Ingresar' }}
                    </button>
                </Form>
            </div>
        </div>
    </div>
</template>
