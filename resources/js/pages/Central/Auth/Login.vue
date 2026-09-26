<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/AuthenticatedSessionController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';
import central from '@/routes/central';
</script>

<template>
    <Head title="Iniciar sesión" />

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
                <label for="email" class="form-label"> Email </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="username"
                    class="form-control w-full"
                />
                <p v-if="errors.email" class="form-error">
                    {{ errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="form-label"> Contraseña </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="form-control w-full"
                />
                <p v-if="errors.password" class="form-error">
                    {{ errors.password }}
                </p>
            </div>

            <div class="flex items-center justify-between gap-4">
                <label
                    class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400"
                >
                    <input type="checkbox" name="remember" class="form-check" />
                    Recordarme
                </label>

                <Link
                    :href="central.password.request().url"
                    class="text-sm font-medium text-ink-700 hover:text-ink-950 dark:text-ink-300 dark:hover:text-white"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ processing ? 'Ingresando...' : 'Ingresar' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
