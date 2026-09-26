<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RequesterTicketView from '@/components/support/RequesterTicketView.vue';
import type { RequesterTicket } from '@/components/support/RequesterTicketView.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import type { TicketAppointment } from '@/lib/support';
import tenant from '@/routes/tenant';

const props = defineProps<{
    ticket: RequesterTicket;
    canReopen: boolean;
    can: { book: boolean };
}>();

const cancelUrl = (appointment: TicketAppointment) =>
    tenant.support.appointments.destroy({
        ticket: props.ticket.id,
        appointment: appointment.id,
    }).url;
</script>

<template>
    <Head :title="`${ticket.code} · ${ticket.subject}`" />

    <GeneralLayout :title="ticket.code">
        <RequesterTicketView
            :ticket="ticket"
            :reply-form="tenant.support.tickets.reply.form(ticket.id)"
            :rating-form="tenant.support.tickets.rate.form(ticket.id)"
            :can-reopen="canReopen"
            :book-href="
                can.book
                    ? tenant.support.appointments.create(ticket.id).url
                    : null
            "
            :cancel-url="can.book ? cancelUrl : null"
        />
    </GeneralLayout>
</template>
