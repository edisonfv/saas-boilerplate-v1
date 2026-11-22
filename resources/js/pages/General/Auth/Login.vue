<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store } from '@/actions/Modules/General/Http/Controllers/Auth/AuthenticatedSessionController';
import AuthShell from '@/components/AuthShell.vue';
import { brand } from '@/lib/brand';
import tenant from '@/routes/tenant';

const page = usePage();
const context = computed(
    () =>
        (page.props.tenant as { name: string } | null)?.name ??
        brand.workspaceName,
);
</script>

<template>
    <Head title="Iniciar sesión" />

    <AuthShell
        title="Iniciar sesión"
        description="Accede al espacio de trabajo de tu organización."
        :context="context"
        variant="tenant"
    >
        <Form
            :action="store().url"
            method="post"
            #default="{ errors, processing }"
            class="space-y-4"
        >
            <div>
                <label for="email" class="form-label">Email</label>
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
                <label for="password" class="form-label">Contraseña</label>
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

            <label
                class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400"
            >
                <input type="checkbox" name="remember" class="form-check" />
                Recordarme
            </label>

            <button
                type="submit"
                :disabled="processing"
                class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700 disabled:opacity-50"
            >
                {{ processing ? 'Ingresando…' : 'Ingresar' }}
            </button>
        </Form>

        <p
            class="mt-8 border-t border-ink-200 pt-6 text-sm text-ink-600 dark:border-ink-800 dark:text-ink-400"
        >
            ¿No puedes entrar o necesitas ayuda?
            <Link
                :href="tenant.support.public.create().url"
                class="font-semibold text-primary-700 hover:text-primary-800 dark:text-primary-300"
            >
                Deja una solicitud de soporte
            </Link>
        </p>
    </AuthShell>
</template>
