<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import SlotPicker from '@/components/support/SlotPicker.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import type { BookingSlot } from '@/lib/support';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

const props = defineProps<{
    ticket: { id: string; code: string; subject: string };
    types: {
        id: string;
        name: string;
        description: string | null;
        duration_minutes: number;
    }[];
    typeId: string | null;
    dates: string[];
    date: string | null;
    slots: BookingSlot[];
    rules: {
        min_notice_hours: number;
        max_days_ahead: number;
        cancellation_notice_hours: number;
    };
}>();

const { longDate, time } = useDateTime();
const selected = ref<string | null>(null);
const isLoading = ref(false);
const isBooking = ref(false);
const error = ref<string | null>(null);

const selectedType = computed(() =>
    props.types.find((type) => type.id === props.typeId),
);

function reload(payload: { typeId: string | null; date: string | null }): void {
    isLoading.value = true;
    router.reload({
        only: ['typeId', 'dates', 'date', 'slots'],
        data: {
            type: payload.typeId ?? undefined,
            date: payload.date ?? undefined,
        },
        onFinish: () => (isLoading.value = false),
    });
}

function confirm(): void {
    if (!selected.value || !props.typeId) {
        return;
    }

    isBooking.value = true;
    error.value = null;
    router.post(
        tenant.support.appointments.store(props.ticket.id).url,
        { support_attendance_type_id: props.typeId, starts_at: selected.value },
        {
            onError: (errors) => {
                error.value =
                    errors.starts_at ?? 'No pudimos agendar la sesión.';
                // Someone may have taken the last seat: refresh availability.
                reload({ typeId: props.typeId, date: props.date });
                selected.value = null;
            },
            onFinish: () => (isBooking.value = false),
        },
    );
}
</script>

<template>
    <Head title="Agendar sesión" />

    <GeneralLayout title="Agendar sesión">
        <Card class="col-span-12 xl:col-span-8" title="Elige un horario">
            <SlotPicker
                v-model="selected"
                :types="types"
                :type-id="typeId"
                :dates="dates"
                :date="date"
                :slots="slots"
                :loading="isLoading"
                @change="reload"
            />
        </Card>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Resumen">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-ink-500">Solicitud</dt>
                        <dd class="font-semibold text-ink-900 dark:text-white">
                            {{ ticket.code }} · {{ ticket.subject }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Atención</dt>
                        <dd class="font-semibold text-ink-900 dark:text-white">
                            {{
                                selectedType
                                    ? `${selectedType.name} (${selectedType.duration_minutes} min)`
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Horario</dt>
                        <dd
                            class="font-semibold text-ink-900 capitalize dark:text-white"
                        >
                            {{
                                selected
                                    ? `${longDate(selected)}, ${time(selected)}`
                                    : 'Elige un horario'
                            }}
                        </dd>
                    </div>
                </dl>
                <p
                    v-if="error"
                    class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300"
                >
                    {{ error }}
                </p>
                <button
                    type="button"
                    :disabled="!selected || isBooking"
                    :class="[ui.buttonPrimary, 'mt-4 w-full']"
                    @click="confirm"
                >
                    {{ isBooking ? 'Agendando…' : 'Confirmar sesión' }}
                </button>
                <Link
                    :href="tenant.support.tickets.show(ticket.id).url"
                    :class="[
                        ui.link,
                        'mt-3 inline-flex items-center gap-1 text-sm',
                    ]"
                >
                    <Icon name="arrow-left" class="size-4" /> Volver a la
                    solicitud
                </Link>
            </Card>

            <Card title="Políticas">
                <ul
                    class="list-disc space-y-1.5 pl-4 text-sm text-ink-600 dark:text-ink-400"
                >
                    <li>
                        Agenda con al menos {{ rules.min_notice_hours }} h de
                        anticipación y hasta {{ rules.max_days_ahead }} días
                        adelante.
                    </li>
                    <li>
                        Puedes cancelar hasta
                        {{ rules.cancellation_notice_hours }} h antes.
                    </li>
                    <li>
                        Si no te conectas, la sesión cuenta igual contra las
                        horas de tu plan.
                    </li>
                </ul>
            </Card>
        </aside>
    </GeneralLayout>
</template>
