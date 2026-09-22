<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/actions/Modules/Central/Http/Controllers/Auth/NewPasswordController';
import CentralAuthShell from '@/components/CentralAuthShell.vue';

const props = defineProps<{
    email: string;
    token: string;
}>();
</script>

<template>
    <Head title="Restablecer contraseña" />

    <CentralAuthShell
        title="Restablecer contraseña"
        description="Elige una nueva contraseña para recuperar tu cuenta central."
    >
        <Form
            :action="store().url"
            method="post"
            #default="{ errors, processing }"
            class="space-y-4"
        >
            <input type="hidden" name="token" :value="props.token" />

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
                    :value="props.email"
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
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">
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

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
            >
                {{ processing ? 'Guardando...' : 'Restablecer contraseña' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
