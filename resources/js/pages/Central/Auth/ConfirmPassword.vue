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
                <label for="password" class="form-label"> Contraseña </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    class="form-control w-full"
                />
                <p v-if="errors.password" class="form-error">
                    {{ errors.password }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ processing ? 'Confirmando...' : 'Confirmar' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
