<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicShell from '@/components/PublicShell.vue';
import RequesterTicketView from '@/components/support/RequesterTicketView.vue';
import type { RequesterTicket } from '@/components/support/RequesterTicketView.vue';
import support from '@/routes/support';

const props = defineProps<{
    ticket: RequesterTicket;
    token: string;
    canReopen: boolean;
}>();

// Guests authenticate every request with their secret token.
const replyForm = {
    ...support.public.tickets.reply.form(props.ticket.id),
    action: support.public.tickets.reply.url(props.ticket.id, {
        query: { token: props.token },
    }),
};
</script>

<template>
    <Head :title="`${ticket.code} · ${ticket.subject}`" />

    <PublicShell context="Seguimiento de solicitud">
        <RequesterTicketView
            :ticket="ticket"
            :reply-form="replyForm"
            :can-reopen="canReopen"
        />
    </PublicShell>
</template>
