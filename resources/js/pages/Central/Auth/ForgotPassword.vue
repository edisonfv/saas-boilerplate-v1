<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/PasswordResetLinkController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';
import central from '@/routes/central';
</script>

<template>
    <Head title="Recuperar contraseña" />

    <CentralAuthShell
        title="Recuperar contraseña"
        description="Ingresa tu email y enviaremos un enlace para restablecer el acceso."
    >
        <Form
            :action="store().url"
            method="post"
            #default="{ errors, processing, recentlySuccessful }"
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

            <p
                v-if="recentlySuccessful"
                class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                Te enviamos el enlace de recuperación por correo.
            </p>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
            >
                {{ processing ? 'Enviando...' : 'Enviar enlace' }}
            </button>
        </Form>

        <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            <Link
                :href="central.login().url"
                class="font-medium text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
            >
                Volver a iniciar sesión
            </Link>
        </p>
    </CentralAuthShell>
</template>
