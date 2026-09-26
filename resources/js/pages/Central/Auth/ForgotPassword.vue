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

            <p
                v-if="recentlySuccessful"
                class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                Te enviamos el enlace de recuperación por correo.
            </p>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ processing ? 'Enviando...' : 'Enviar enlace' }}
            </button>
        </Form>

        <p class="mt-6 text-center text-sm text-ink-500 dark:text-ink-400">
            <Link
                :href="central.login().url"
                class="font-medium text-ink-700 hover:text-ink-950 dark:text-ink-300 dark:hover:text-white"
            >
                Volver a iniciar sesión
            </Link>
        </p>
    </CentralAuthShell>
</template>
