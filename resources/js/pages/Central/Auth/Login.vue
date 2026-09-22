<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/AuthenticatedSessionController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';
import central from '@/routes/central';
</script>

<template>
    <Head title="Central login" />

    <CentralAuthShell
        title="Iniciar sesión"
        description="Acceso exclusivo para el equipo que administra la plataforma."
    >
        <Form
            :action="store().url"
            method="post"
            #default="{ errors, processing }"
            class="space-y-4"
        >
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
                    autofocus
                    autocomplete="username"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">
                    {{ errors.email }}
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
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">
                    {{ errors.password }}
                </p>
            </div>

            <div class="flex items-center justify-between gap-4">
                <label
                    class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                >
                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-slate-300 text-slate-950 focus:ring-slate-500"
                    />
                    Recordarme
                </label>

                <Link
                    :href="central.password.request().url"
                    class="text-sm font-medium text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
            >
                {{ processing ? 'Ingresando...' : 'Ingresar' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
