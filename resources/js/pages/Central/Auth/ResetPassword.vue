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
                <label for="email" class="form-label"> Email </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    :value="props.email"
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
                <label for="password" class="form-label">
                    Nueva contraseña
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

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ processing ? 'Guardando...' : 'Restablecer contraseña' }}
            </button>
        </Form>
    </CentralAuthShell>
</template>
