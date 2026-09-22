<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { destroy } from '@/actions/Modules/Central/Http/Controllers/Auth/AuthenticatedSessionController';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/EmailVerificationNotificationController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';

defineProps<{
    status?: string | null;
}>();
</script>

<template>
    <Head title="Verificar email" />

    <CentralAuthShell
        title="Verifica tu correo"
        description="Antes de continuar, revisa tu correo y abre el enlace de verificación."
    >
        <p
            v-if="status === 'verification-link-sent'"
            class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
        >
            Te enviamos un nuevo enlace de verificación.
        </p>

        <Form
            :action="store().url"
            method="post"
            #default="{ processing }"
            class="space-y-4"
        >
            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
            >
                {{ processing ? 'Enviando...' : 'Reenviar enlace' }}
            </button>
        </Form>

        <Link
            :href="destroy().url"
            method="post"
            as="button"
            class="mt-4 w-full rounded-lg px-4 py-2 text-center text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
        >
            Salir
        </Link>
    </CentralAuthShell>
</template>
