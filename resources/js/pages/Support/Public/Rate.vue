<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import PublicShell from '@/components/PublicShell.vue';
import TicketRatingForm from '@/components/support/TicketRatingForm.vue';

const props = defineProps<{
    ticket: { code: string; subject: string; resolver_name: string | null };
    alreadyRated: boolean;
    isResolved: boolean;
    submitUrl: string;
}>();

const page = usePage();
const justRated = computed(
    () =>
        (page.flash as { status?: string } | undefined)?.status ===
        'support-rated',
);
const form = computed(() => ({
    action: props.submitUrl,
    method: 'post' as const,
}));
</script>

<template>
    <Head title="Califica la atención" />

    <PublicShell context="Encuesta de satisfacción">
        <Card
            class="col-span-12 md:col-span-8 md:col-start-3 xl:col-span-6 xl:col-start-4"
        >
            <p class="eyebrow text-primary-600 dark:text-primary-400">
                {{ ticket.code }}
            </p>
            <h1
                class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
            >
                {{ ticket.subject }}
            </h1>
            <p
                v-if="ticket.resolver_name"
                class="mt-1 text-sm text-ink-600 dark:text-ink-400"
            >
                Atendido por {{ ticket.resolver_name }}
            </p>

            <div
                v-if="alreadyRated || justRated"
                class="mt-6 flex items-center gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
                <Icon name="check-circle" class="size-6 shrink-0" />
                <p class="text-sm font-semibold">
                    ¡Gracias! Ya registramos tu calificación.
                </p>
            </div>
            <p
                v-else-if="!isResolved"
                class="mt-6 text-sm text-ink-600 dark:text-ink-400"
            >
                Esta solicitud volvió a abrirse; podrás calificarla cuando la
                resolvamos.
            </p>
            <div v-else class="mt-6">
                <TicketRatingForm :form="form" />
            </div>
        </Card>
    </PublicShell>
</template>
