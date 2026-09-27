<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { destroy } from '@/actions/Modules/General/Http/Controllers/Auth/AuthenticatedSessionController';
import AuthShell from '@/components/AuthShell.vue';
import { useDateTime } from '@/composables/useDateTime';
import { brand } from '@/lib/brand';
import { ui } from '@/lib/ui';

/**
 * Shown by App\Http\Middleware\EnsureTenantIsActive when the workspace is
 * suspended or its subscription lapsed.
 */
const props = defineProps<{
    reason: 'Suspended' | 'SubscriptionLapsed';
    reason_label: string;
    subscription: {
        status_label: string;
        trial_ends_at: string | null;
        current_period_end: string | null;
    } | null;
}>();

const page = usePage();
const { date } = useDateTime();

const context = computed(
    () =>
        (page.props.tenant as { name: string } | null)?.name ??
        brand.workspaceName,
);

const description = computed(() =>
    props.reason === 'Suspended'
        ? 'El acceso a este espacio de trabajo fue suspendido por el administrador de la plataforma.'
        : 'La suscripción de tu organización caducó. Renuévala para volver a usar el sistema; tus datos se conservan.',
);
</script>

<template>
    <Head title="Espacio inhabilitado" />

    <AuthShell
        title="Espacio inhabilitado"
        :description="description"
        :context="context"
        variant="tenant"
    >
        <div
            class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
            role="alert"
        >
            <p class="font-semibold">{{ reason_label }}</p>
            <template v-if="subscription">
                <p>Estado: {{ subscription.status_label }}</p>
                <p v-if="subscription.current_period_end">
                    Periodo vigente hasta:
                    {{ date(subscription.current_period_end) }}
                </p>
                <p v-if="subscription.trial_ends_at">
                    Prueba hasta: {{ date(subscription.trial_ends_at) }}
                </p>
            </template>
        </div>

        <p class="mt-6 text-sm text-ink-600 dark:text-ink-400">
            Comunícate con el equipo de soporte o tu ejecutivo comercial para
            reactivar el servicio.
        </p>

        <Link
            :href="destroy().url"
            method="post"
            as="button"
            :class="[ui.buttonSecondary, 'mt-6 w-full']"
        >
            Cerrar sesión
        </Link>
    </AuthShell>
</template>
