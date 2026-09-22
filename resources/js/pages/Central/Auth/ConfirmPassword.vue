<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/ConfirmablePasswordController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';
</script>

<template>
    <Head title="Confirmar contraseña" />

    <CentralAuthShell
        title="Confirmar contraseña"
        description="Esta área está protegida. Confirma tu contraseña para continuar."
    >
        <Form
            :action="store().url"
            method="post"
            #default="{ errors, processing }"
            class="space-y-4"
        >
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
                    autofocus
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">
                    {{ errors.password }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
            >
                {{ processing ? 'Confirmando...' : 'Confirmar' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
