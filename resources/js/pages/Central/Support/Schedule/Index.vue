<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import Badge from '@/components/Badge.vue';
import DatePicker from '@/components/DatePicker.vue';
import Icon from '@/components/Icon.vue';
import ScheduleSection from '@/components/support/ScheduleSection.vue';
import WeeklyHoursEditor from '@/components/support/WeeklyHoursEditor.vue';
import { useDateTime } from '@/composables/useDateTime';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { appointmentStatusTone, summarizeWeek } from '@/lib/support';
import type { WeeklyWindow } from '@/lib/support';
import { ui } from '@/lib/ui';
import { cn } from '@/lib/utils';
import central from '@/routes/central';

type Shift = {
    weekday: number;
    starts_at: string;
    ends_at: string;
};

interface Technician {
    id: string;
    central_user_id: string;
    name: string;
    capacity: number;
    is_active: boolean;
    shifts: Shift[];
}

interface AttendanceType {
    id: string;
    name: string;
    description: string | null;
    duration_minutes: number;
    is_active: boolean;
}

const props = defineProps<{
    range: { from: string; to: string };
    appointments: {
        id: string;
        ticket_id: string;
        ticket_code: string;
        subject: string;
        tenant_name: string | null;
        type_name: string;
        technician_name: string;
        starts_at: string;
        ends_at: string;
        status: string;
        status_label: string;
        meeting_url: string | null;
    }[];
    settings: Record<string, string | number>;
    businessHours: { weekday: number; opens_at: string; closes_at: string }[];
    attendanceTypes: AttendanceType[];
    technicians: Technician[];
    blackouts: {
        id: string;
        technician_name: string | null;
        starts_at: string;
        ends_at: string;
        reason: string;
    }[];
    staff: { id: string; name: string }[];
    timezone: string;
    can: { update: boolean };
}>();

const { dateTime, time, dayKey } = useDateTime();
const options = { preserveScroll: true };

// --- Week calendar ---------------------------------------------------------
const todayKey = dayKey(new Date().toISOString());

/** Formats a "YYYY-MM-DD" key; noon UTC keeps the day stable in any zone. */
function formatDay(key: string, formatOptions: Intl.DateTimeFormatOptions) {
    return new Intl.DateTimeFormat('es-EC', {
        timeZone: 'UTC',
        ...formatOptions,
    }).format(new Date(`${key}T12:00:00Z`));
}

function addDays(key: string, days: number): string {
    const date = new Date(`${key}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}

const weekTitle = computed(() => {
    const sameMonth =
        props.range.from.slice(0, 7) === props.range.to.slice(0, 7);
    const start = formatDay(
        props.range.from,
        sameMonth ? { day: 'numeric' } : { day: 'numeric', month: 'short' },
    );

    return `${start} – ${formatDay(props.range.to, { day: 'numeric', month: 'long', year: 'numeric' })}`;
});

const isCurrentWeek = computed(() => props.range.from === todayKey);

const days = computed(() =>
    Array.from({ length: 7 }, (_, index) => {
        const key = addDays(props.range.from, index);

        return {
            key,
            weekday: formatDay(key, { weekday: 'short' }),
            number: formatDay(key, { day: 'numeric' }),
            isToday: key === todayKey,
            isPast: key < todayKey,
            items: props.appointments.filter(
                (appointment) => dayKey(appointment.starts_at) === key,
            ),
            blackouts: props.blackouts.filter(
                (blackout) =>
                    dayKey(blackout.starts_at) <= key &&
                    dayKey(blackout.ends_at) >= key,
            ),
        };
    }),
);

function goToWeek(from: string | null): void {
    router.get(central.support.schedule.index().url, from ? { from } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

const pickedWeek = computed(() => [
    new Date(`${props.range.from}T12:00:00`),
    new Date(`${props.range.to}T12:00:00`),
]);

function onWeekPicked(value: Date[] | null): void {
    if (value?.[0]) {
        goToWeek(format(value[0], 'yyyy-MM-dd'));
    }
}

const statusStripe: Record<string, string> = {
    blue: 'border-l-primary-500',
    green: 'border-l-emerald-500',
    amber: 'border-l-accent-500',
    red: 'border-l-red-500',
    gray: 'border-l-ink-300 dark:border-l-ink-600',
};

// --- Configuration sections ------------------------------------------------
type SectionKey = 'hours' | 'types' | 'technicians' | 'blackouts' | 'rules';

const openSections = reactive<Record<SectionKey, boolean>>({
    hours: false,
    types: false,
    technicians: false,
    blackouts: false,
    rules: false,
});

async function openSection(key: SectionKey): Promise<void> {
    openSections[key] = true;
    await nextTick();
    document
        .getElementById(`section-${key}`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const activeTypes = computed(() =>
    props.attendanceTypes.filter((type) => type.is_active),
);
const readyTechnicians = computed(() =>
    props.technicians.filter(
        (technician) => technician.is_active && technician.shifts.length > 0,
    ),
);

const setupSteps = computed(() => [
    {
        key: 'hours' as const,
        label: 'Define en qué días y horas atiende el equipo',
        done: props.businessHours.length > 0,
    },
    {
        key: 'types' as const,
        label: 'Activa al menos un tipo de sesión',
        done: activeTypes.value.length > 0,
    },
    {
        key: 'technicians' as const,
        label: 'Agrega un técnico con sus turnos',
        done: readyTechnicians.value.length > 0,
    },
]);
const pendingSteps = computed(() =>
    setupSteps.value.filter((step) => !step.done),
);

// --- Business hours --------------------------------------------------------
const toHoursModel = (): WeeklyWindow[] =>
    props.businessHours.map((hour) => ({
        weekday: hour.weekday,
        start: hour.opens_at,
        end: hour.closes_at,
    }));

const hours = ref<WeeklyWindow[]>(toHoursModel());
const isSavingHours = ref(false);
const hoursError = ref<string | null>(null);

watch(
    () => props.businessHours,
    () => (hours.value = toHoursModel()),
);

const hoursChanged = computed(
    () => JSON.stringify(hours.value) !== JSON.stringify(toHoursModel()),
);
const hasInvalidRange = (windows: WeeklyWindow[]) =>
    windows.some((window) => window.end <= window.start);

function saveHours(): void {
    hoursError.value = null;
    isSavingHours.value = true;
    router.put(
        central.support.businessHours.update().url,
        {
            hours: hours.value.map((window) => ({
                weekday: window.weekday,
                opens_at: window.start,
                closes_at: window.end,
            })),
        },
        {
            ...options,
            onError: () =>
                (hoursError.value =
                    'Revisa los horarios: la hora final debe ser posterior a la inicial.'),
            onFinish: () => (isSavingHours.value = false),
        },
    );
}

// --- Attendance types ------------------------------------------------------
const durationPresets = [15, 30, 45, 60, 90, 120];

const typeForm = useForm({
    name: '',
    duration_minutes: 30,
    description: '',
    is_active: true,
});

function createType(): void {
    typeForm.post(central.support.attendanceTypes.store().url, {
        ...options,
        onSuccess: () => typeForm.reset(),
    });
}

function toggleType(type: AttendanceType): void {
    router.patch(
        central.support.attendanceTypes.update(type.id).url,
        { ...type, is_active: !type.is_active },
        options,
    );
}

// --- Technicians -----------------------------------------------------------
interface TechnicianDraft {
    capacity: number;
    is_active: boolean;
    shifts: WeeklyWindow[];
}

const toWindows = (shifts: Shift[]): WeeklyWindow[] =>
    shifts.map((shift) => ({
        weekday: shift.weekday,
        start: shift.starts_at,
        end: shift.ends_at,
    }));

const toShifts = (windows: WeeklyWindow[]): Shift[] =>
    windows.map((window) => ({
        weekday: window.weekday,
        starts_at: window.start,
        ends_at: window.end,
    }));

const editing = reactive<Record<string, TechnicianDraft>>({});
const newTechnician = reactive({ central_user_id: '', capacity: 1 });

function editTechnician(technician: Technician): void {
    editing[technician.id] = {
        capacity: technician.capacity,
        is_active: technician.is_active,
        shifts: toWindows(technician.shifts),
    };
}

function saveTechnician(id: string): void {
    const draft = editing[id];
    router.patch(
        central.support.technicians.update(id).url,
        {
            capacity: draft.capacity,
            is_active: draft.is_active,
            shifts: toShifts(draft.shifts),
        },
        { ...options, onSuccess: () => delete editing[id] },
    );
}

function createTechnician(): void {
    router.post(
        central.support.technicians.store().url,
        {
            ...newTechnician,
            is_active: true,
            // Starts with the team's hours; adjusted per person afterwards.
            shifts: toShifts(toHoursModel()),
        },
        {
            ...options,
            onSuccess: () => {
                newTechnician.central_user_id = '';
                newTechnician.capacity = 1;
            },
        },
    );
}

// --- Holidays and absences -------------------------------------------------
const blackoutForm = useForm({
    support_technician_id: '',
    all_day: true,
    dates: null as Date[] | null,
    reason: '',
});

function submitBlackout(): void {
    blackoutForm
        .transform((data) => {
            const start = data.dates?.[0] ?? null;
            const end = data.dates?.[1] ?? start;

            return {
                support_technician_id: data.support_technician_id || null,
                reason: data.reason,
                starts_at: start
                    ? format(
                          start,
                          data.all_day
                              ? "yyyy-MM-dd'T'00:00"
                              : "yyyy-MM-dd'T'HH:mm",
                      )
                    : null,
                ends_at: end
                    ? format(
                          end,
                          data.all_day
                              ? "yyyy-MM-dd'T'23:59"
                              : "yyyy-MM-dd'T'HH:mm",
                      )
                    : null,
            };
        })
        .post(central.support.blackouts.store().url, {
            ...options,
            onSuccess: () => blackoutForm.reset(),
        });
}

/** Errors come back for the transformed payload, not the form fields. */
const blackoutDatesError = computed(() => {
    const errors = blackoutForm.errors as Record<string, string | undefined>;

    return errors.starts_at ?? errors.ends_at ?? null;
});

function removeBlackout(id: string): void {
    if (
        window.confirm(
            '¿Eliminar este bloqueo? Las citas canceladas no se restauran.',
        )
    ) {
        router.delete(central.support.blackouts.destroy(id).url, options);
    }
}

function blackoutRange(blackout: { starts_at: string; ends_at: string }) {
    const sameDay = dayKey(blackout.starts_at) === dayKey(blackout.ends_at);

    return sameDay
        ? `${dateTime(blackout.starts_at)} → ${time(blackout.ends_at)}`
        : `${dateTime(blackout.starts_at)} → ${dateTime(blackout.ends_at)}`;
}

// --- Rules ---------------------------------------------------------------
const ruleGroups: {
    title: string;
    fields: { name: string; label: string; unit: string; help: string }[];
}[] = [
    {
        title: 'Reservas de los clientes',
        fields: [
            {
                name: 'booking_min_notice_hours',
                label: 'Anticipación mínima',
                unit: 'horas',
                help: 'No se puede agendar con menos tiempo que esto.',
            },
            {
                name: 'booking_max_days_ahead',
                label: 'Agendar como máximo a',
                unit: 'días',
                help: 'Hasta cuántos días en el futuro se muestran horarios.',
            },
            {
                name: 'cancellation_notice_hours',
                label: 'Cancelar hasta',
                unit: 'horas antes',
                help: 'Después de este límite el cliente ya no puede cancelar.',
            },
        ],
    },
    {
        title: 'Cierre de solicitudes',
        fields: [
            {
                name: 'auto_close_days',
                label: 'Cierre automático tras',
                unit: 'días',
                help: 'Una solicitud resuelta se cierra sola pasado este tiempo.',
            },
            {
                name: 'reopen_window_days',
                label: 'Reabrir durante',
                unit: 'días',
                help: 'Plazo en que el cliente puede reabrir una solicitud cerrada.',
            },
            {
                name: 'rating_link_days',
                label: 'Encuesta disponible',
                unit: 'días',
                help: 'Vigencia del enlace para calificar la atención.',
            },
        ],
    },
];

const rulesSummary = computed(
    () =>
        `${props.settings.currency} ${Number(props.settings.hourly_rate).toFixed(2)}/h · ` +
        `reservas con ${props.settings.booking_min_notice_hours} h de anticipación, ` +
        `hasta ${props.settings.booking_max_days_ahead} días`,
);
</script>

<template>
    <Head title="Agenda de soporte" />

    <CentralLayout title="Agenda de soporte">
        <div class="space-y-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Soporte
                    </p>
                    <h2
                        class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        Agenda de sesiones
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                    >
                        Aquí ves las sesiones agendadas con los clientes. Más
                        abajo configuras cuándo atiende el equipo y quién
                        atiende. Horas en {{ timezone }}.
                    </p>
                </div>
                <button
                    type="button"
                    :class="ui.buttonSecondary"
                    @click="openSection('hours')"
                >
                    <Icon name="settings" class="size-4" /> Configurar agenda
                </button>
            </div>

            <!-- Setup checklist: only while the agenda cannot take bookings -->
            <div
                v-if="pendingSteps.length > 0"
                class="rounded-xl border border-accent-200 bg-accent-50 p-5 dark:border-accent-500/30 dark:bg-accent-500/10"
            >
                <div class="flex items-start gap-3">
                    <Icon
                        name="exclamation-triangle"
                        class="mt-0.5 size-5 shrink-0 text-accent-700 dark:text-accent-400"
                    />
                    <div class="flex-1">
                        <p class="font-bold text-ink-950 dark:text-white">
                            Los clientes aún no pueden agendar sesiones
                        </p>
                        <p class="text-sm text-ink-700 dark:text-ink-300">
                            Completa estos pasos ({{
                                setupSteps.length - pendingSteps.length
                            }}
                            de {{ setupSteps.length }} listos):
                        </p>
                        <ol class="mt-3 space-y-2">
                            <li
                                v-for="(step, index) in setupSteps"
                                :key="step.key"
                                class="flex flex-wrap items-center gap-2 text-sm"
                            >
                                <Icon
                                    :name="step.done ? 'check-circle' : 'clock'"
                                    :class="
                                        cn(
                                            'size-5',
                                            step.done
                                                ? 'text-emerald-600'
                                                : 'text-ink-400',
                                        )
                                    "
                                />
                                <span
                                    :class="
                                        step.done
                                            ? 'text-ink-500 line-through'
                                            : 'font-semibold text-ink-900 dark:text-white'
                                    "
                                >
                                    {{ index + 1 }}. {{ step.label }}
                                </span>
                                <button
                                    v-if="!step.done && can.update"
                                    type="button"
                                    :class="[ui.link, 'text-sm']"
                                    @click="openSection(step.key)"
                                >
                                    Hacerlo ahora →
                                </button>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Week calendar -->
            <section class="space-y-4" aria-label="Calendario semanal">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :class="[ui.buttonSecondary, 'px-3']"
                            aria-label="Semana anterior"
                            @click="goToWeek(addDays(range.from, -7))"
                        >
                            <Icon name="arrow-left" class="size-4" />
                        </button>
                        <button
                            type="button"
                            :class="[ui.buttonSecondary, 'px-3']"
                            aria-label="Semana siguiente"
                            @click="goToWeek(addDays(range.from, 7))"
                        >
                            <Icon name="arrow-right" class="size-4" />
                        </button>
                        <button
                            type="button"
                            :disabled="isCurrentWeek"
                            :class="ui.buttonSecondary"
                            @click="goToWeek(null)"
                        >
                            Hoy
                        </button>
                    </div>

                    <DatePicker
                        :model-value="pickedWeek"
                        week-picker
                        auto-apply
                        :time-config="{ enableTimePicker: false }"
                        @update:model-value="onWeekPicked"
                    >
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-base font-bold text-ink-950 transition hover:bg-ink-100 dark:text-white dark:hover:bg-ink-800"
                            >
                                <Icon
                                    name="calendar"
                                    class="size-5 text-primary-600"
                                />
                                {{ weekTitle }}
                                <Icon
                                    name="chevrons-up-down"
                                    class="size-4 text-ink-400"
                                />
                            </button>
                        </template>
                    </DatePicker>

                    <p class="text-sm text-ink-500 dark:text-ink-400">
                        <strong
                            class="font-bold text-ink-900 tabular-nums dark:text-white"
                            >{{ appointments.length }}</strong
                        >
                        {{ appointments.length === 1 ? 'sesión' : 'sesiones' }}
                        en estos días
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7 lg:gap-2"
                >
                    <div
                        v-for="day in days"
                        :key="day.key"
                        :class="
                            cn(
                                'flex flex-col rounded-xl border bg-white lg:min-h-80 dark:bg-ink-900',
                                day.isToday
                                    ? 'border-primary-400 ring-2 ring-primary-500/15'
                                    : 'border-ink-200 dark:border-ink-800',
                                day.isPast && 'opacity-70',
                            )
                        "
                    >
                        <div
                            class="flex items-baseline gap-2 border-b border-ink-100 px-3 py-2 dark:border-ink-800"
                        >
                            <span
                                class="text-xs font-semibold text-ink-500 uppercase"
                                >{{ day.weekday }}</span
                            >
                            <span
                                :class="
                                    cn(
                                        'grid size-7 place-items-center rounded-full text-sm font-bold tabular-nums',
                                        day.isToday
                                            ? 'bg-primary-600 text-white'
                                            : 'text-ink-900 dark:text-white',
                                    )
                                "
                                >{{ day.number }}</span
                            >
                            <span
                                v-if="day.isToday"
                                class="text-xs font-semibold text-primary-700 dark:text-primary-300"
                                >Hoy</span
                            >
                        </div>

                        <div class="flex-1 space-y-2 p-2">
                            <p
                                v-for="blackout in day.blackouts"
                                :key="blackout.id"
                                class="rounded-lg bg-[repeating-linear-gradient(135deg,transparent,transparent_6px,rgb(0_0_0/0.04)_6px,rgb(0_0_0/0.04)_12px)] px-2 py-1.5 text-xs text-ink-600 ring-1 ring-ink-200 ring-inset dark:text-ink-300 dark:ring-ink-700"
                            >
                                <span class="font-semibold">{{
                                    blackout.reason
                                }}</span>
                                ·
                                {{
                                    blackout.technician_name ?? 'Todo el equipo'
                                }}
                            </p>

                            <article
                                v-for="item in day.items"
                                :key="item.id"
                                :class="
                                    cn(
                                        'rounded-lg border border-l-4 border-ink-200 bg-ink-50/60 p-2.5 dark:border-ink-700 dark:bg-ink-800/50',
                                        statusStripe[
                                            appointmentStatusTone(item.status)
                                        ],
                                    )
                                "
                            >
                                <p
                                    class="text-xs font-bold text-ink-950 tabular-nums dark:text-white"
                                >
                                    {{ time(item.starts_at) }} –
                                    {{ time(item.ends_at) }}
                                </p>
                                <Link
                                    :href="
                                        central.support.tickets.show(
                                            item.ticket_id,
                                        ).url
                                    "
                                    class="mt-0.5 line-clamp-2 text-sm leading-snug font-semibold text-ink-900 hover:text-primary-700 dark:text-white dark:hover:text-primary-300"
                                >
                                    {{ item.subject }}
                                </Link>
                                <p class="mt-1 truncate text-xs text-ink-500">
                                    {{ item.tenant_name ?? '—' }}
                                </p>
                                <p
                                    class="flex items-center gap-1 truncate text-xs text-ink-500"
                                >
                                    <Icon name="user" class="size-3.5" />
                                    {{ item.technician_name }}
                                </p>
                                <div
                                    class="mt-2 flex flex-wrap items-center justify-between gap-1"
                                >
                                    <Badge
                                        :tone="
                                            appointmentStatusTone(item.status)
                                        "
                                        >{{ item.status_label }}</Badge
                                    >
                                    <a
                                        v-if="item.meeting_url"
                                        :href="item.meeting_url"
                                        target="_blank"
                                        rel="noopener"
                                        :class="[ui.link, 'text-xs']"
                                        >Unirse ↗</a
                                    >
                                </div>
                            </article>

                            <p
                                v-if="
                                    day.items.length === 0 &&
                                    day.blackouts.length === 0
                                "
                                class="px-1 py-2 text-xs text-ink-400"
                            >
                                Sin sesiones
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Configuration -->
            <section class="space-y-3" aria-labelledby="schedule-config">
                <div>
                    <h3
                        id="schedule-config"
                        class="text-lg font-extrabold text-ink-950 dark:text-white"
                    >
                        Configuración de la agenda
                    </h3>
                    <p class="text-sm text-ink-600 dark:text-ink-400">
                        Sigue los pasos en orden. Los clientes solo ven horarios
                        en los que el equipo está abierto y hay un técnico
                        disponible.
                    </p>
                </div>

                <ScheduleSection
                    id="section-hours"
                    v-model:open="openSections.hours"
                    :step="1"
                    icon="clock"
                    title="Horario de atención del equipo"
                    description="Los días y horas en que el equipo atiende. Fuera de este horario no se ofrecen citas y el tiempo de respuesta (SLA) se pausa."
                    :summary="summarizeWeek(toHoursModel())"
                    :pending="businessHours.length === 0"
                >
                    <WeeklyHoursEditor
                        v-model="hours"
                        :disabled="!can.update"
                    />
                    <p v-if="hoursError" :class="ui.error">{{ hoursError }}</p>
                    <div
                        v-if="can.update"
                        class="mt-4 flex flex-wrap items-center gap-3"
                    >
                        <button
                            type="button"
                            :disabled="
                                !hoursChanged ||
                                isSavingHours ||
                                hasInvalidRange(hours)
                            "
                            :class="ui.buttonPrimary"
                            @click="saveHours"
                        >
                            {{
                                isSavingHours ? 'Guardando…' : 'Guardar horario'
                            }}
                        </button>
                        <button
                            v-if="hoursChanged"
                            type="button"
                            :class="ui.buttonSecondary"
                            @click="hours = toHoursModel()"
                        >
                            Descartar cambios
                        </button>
                        <span
                            v-if="hoursChanged"
                            class="text-xs font-semibold text-accent-700 dark:text-accent-400"
                            >Tienes cambios sin guardar</span
                        >
                    </div>
                </ScheduleSection>

                <ScheduleSection
                    id="section-types"
                    v-model:open="openSections.types"
                    :step="2"
                    icon="layers"
                    title="Tipos de sesión"
                    description="Lo que el cliente elige al agendar (por ejemplo, «Soporte remoto 30 min»). La duración define el tamaño de cada horario disponible."
                    :summary="
                        activeTypes.length > 0
                            ? activeTypes
                                  .map(
                                      (type) =>
                                          `${type.name} (${type.duration_minutes} min)`,
                                  )
                                  .join(' · ')
                            : 'Ningún tipo activo'
                    "
                    :pending="activeTypes.length === 0"
                >
                    <ul
                        class="divide-y divide-ink-100 rounded-xl border border-ink-200 dark:divide-ink-800 dark:border-ink-800"
                    >
                        <li
                            v-for="type in attendanceTypes"
                            :key="type.id"
                            class="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p
                                    :class="
                                        cn(
                                            'font-semibold',
                                            type.is_active
                                                ? 'text-ink-900 dark:text-white'
                                                : 'text-ink-400',
                                        )
                                    "
                                >
                                    {{ type.name }}
                                    <span
                                        class="ml-1 text-xs font-medium text-ink-500"
                                        >{{ type.duration_minutes }} min</span
                                    >
                                </p>
                                <p
                                    v-if="type.description"
                                    class="truncate text-xs text-ink-500"
                                >
                                    {{ type.description }}
                                </p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="type.is_active"
                                :aria-label="`${type.name}: ${type.is_active ? 'activo' : 'inactivo'}`"
                                :disabled="!can.update"
                                class="inline-flex items-center gap-2 text-xs font-semibold text-ink-600 disabled:cursor-not-allowed dark:text-ink-300"
                                @click="toggleType(type)"
                            >
                                <span
                                    :class="
                                        cn(
                                            'relative inline-flex h-5 w-9 items-center rounded-full transition',
                                            type.is_active
                                                ? 'bg-primary-600'
                                                : 'bg-ink-200 dark:bg-ink-700',
                                        )
                                    "
                                >
                                    <span
                                        :class="
                                            cn(
                                                'inline-block size-4 rounded-full bg-white shadow transition',
                                                type.is_active
                                                    ? 'translate-x-4.5'
                                                    : 'translate-x-0.5',
                                            )
                                        "
                                    />
                                </span>
                                {{ type.is_active ? 'Visible' : 'Oculto' }}
                            </button>
                        </li>
                        <li
                            v-if="attendanceTypes.length === 0"
                            class="px-4 py-3 text-sm text-ink-500"
                        >
                            Todavía no hay tipos de sesión.
                        </li>
                    </ul>

                    <form
                        v-if="can.update"
                        class="mt-5 space-y-4 rounded-xl bg-ink-50 p-4 dark:bg-ink-800/40"
                        @submit.prevent="createType"
                    >
                        <p
                            class="text-sm font-bold text-ink-900 dark:text-white"
                        >
                            Nuevo tipo de sesión
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="type-name" :class="ui.label"
                                    >Nombre</label
                                >
                                <input
                                    id="type-name"
                                    v-model="typeForm.name"
                                    required
                                    placeholder="Ej.: Capacitación"
                                    :class="ui.input"
                                />
                                <p
                                    v-if="typeForm.errors.name"
                                    :class="ui.error"
                                >
                                    {{ typeForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label for="type-description" :class="ui.label"
                                    >Descripción (opcional)</label
                                >
                                <input
                                    id="type-description"
                                    v-model="typeForm.description"
                                    placeholder="Qué incluye esta sesión"
                                    :class="ui.input"
                                />
                            </div>
                        </div>
                        <fieldset>
                            <legend :class="ui.label">Duración</legend>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="minutes in durationPresets"
                                    :key="minutes"
                                    type="button"
                                    :aria-pressed="
                                        typeForm.duration_minutes === minutes
                                    "
                                    :class="
                                        cn(
                                            'rounded-lg border px-3 py-1.5 text-sm font-semibold transition',
                                            typeForm.duration_minutes ===
                                                minutes
                                                ? 'border-primary-600 bg-primary-600 text-white'
                                                : 'border-ink-200 bg-white text-ink-700 hover:border-primary-300 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-200',
                                        )
                                    "
                                    @click="typeForm.duration_minutes = minutes"
                                >
                                    {{
                                        minutes < 60
                                            ? `${minutes} min`
                                            : `${minutes / 60} h`
                                    }}
                                </button>
                                <label
                                    class="ml-1 inline-flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300"
                                >
                                    Otro:
                                    <input
                                        v-model.number="
                                            typeForm.duration_minutes
                                        "
                                        type="number"
                                        min="10"
                                        max="480"
                                        step="5"
                                        :class="[ui.input, 'w-24']"
                                    />
                                    min
                                </label>
                            </div>
                            <p
                                v-if="typeForm.errors.duration_minutes"
                                :class="ui.error"
                            >
                                {{ typeForm.errors.duration_minutes }}
                            </p>
                        </fieldset>
                        <button
                            type="submit"
                            :disabled="typeForm.processing"
                            :class="ui.buttonPrimary"
                        >
                            <Icon name="plus" class="size-4" /> Crear tipo
                        </button>
                    </form>
                </ScheduleSection>

                <ScheduleSection
                    id="section-technicians"
                    v-model:open="openSections.technicians"
                    :step="3"
                    icon="users"
                    title="Técnicos"
                    description="Las personas que atienden las sesiones. Cada técnico tiene sus propios turnos y puede atender varias sesiones a la vez (capacidad)."
                    :summary="
                        readyTechnicians.length > 0
                            ? `${readyTechnicians.length} técnico(s) disponible(s): ${readyTechnicians.map((technician) => technician.name).join(', ')}`
                            : 'Ningún técnico disponible'
                    "
                    :pending="readyTechnicians.length === 0"
                >
                    <ul class="space-y-3">
                        <li
                            v-for="technician in technicians"
                            :key="technician.id"
                            class="rounded-xl border border-ink-200 p-4 dark:border-ink-800"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                                    >
                                        {{ technician.name.charAt(0) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            class="font-bold text-ink-950 dark:text-white"
                                        >
                                            {{ technician.name }}
                                            <Badge
                                                v-if="!technician.is_active"
                                                tone="gray"
                                                class="ml-1"
                                                >Inactivo</Badge
                                            >
                                        </p>
                                        <p class="text-xs text-ink-500">
                                            Hasta {{ technician.capacity }}
                                            {{
                                                technician.capacity === 1
                                                    ? 'sesión'
                                                    : 'sesiones'
                                            }}
                                            a la vez ·
                                            <span
                                                :class="
                                                    technician.shifts.length ===
                                                        0 && 'text-accent-700'
                                                "
                                                >{{
                                                    technician.shifts.length > 0
                                                        ? summarizeWeek(
                                                              toWindows(
                                                                  technician.shifts,
                                                              ),
                                                          )
                                                        : 'Sin turnos: no recibe citas'
                                                }}</span
                                            >
                                        </p>
                                    </div>
                                </div>
                                <button
                                    v-if="can.update && !editing[technician.id]"
                                    type="button"
                                    :class="ui.buttonSecondary"
                                    @click="editTechnician(technician)"
                                >
                                    <Icon name="pencil" class="size-4" /> Editar
                                </button>
                            </div>

                            <div
                                v-if="editing[technician.id]"
                                class="mt-4 space-y-4 border-t border-ink-100 pt-4 dark:border-ink-800"
                            >
                                <div class="flex flex-wrap items-end gap-6">
                                    <div>
                                        <label
                                            :class="ui.label"
                                            :for="`capacity-${technician.id}`"
                                            >Sesiones a la vez</label
                                        >
                                        <input
                                            :id="`capacity-${technician.id}`"
                                            v-model.number="
                                                editing[technician.id].capacity
                                            "
                                            type="number"
                                            min="1"
                                            max="20"
                                            :class="[ui.input, 'w-28']"
                                        />
                                    </div>
                                    <label
                                        class="inline-flex items-center gap-2 pb-3 text-sm font-medium"
                                    >
                                        <input
                                            v-model="
                                                editing[technician.id].is_active
                                            "
                                            type="checkbox"
                                            :class="ui.checkbox"
                                        />
                                        Disponible para recibir citas
                                    </label>
                                </div>

                                <div>
                                    <div
                                        class="mb-2 flex flex-wrap items-center justify-between gap-2"
                                    >
                                        <p :class="[ui.label, 'mb-0']">
                                            Turnos
                                        </p>
                                        <button
                                            v-if="businessHours.length > 0"
                                            type="button"
                                            :class="[ui.link, 'text-xs']"
                                            @click="
                                                editing[technician.id].shifts =
                                                    toHoursModel()
                                            "
                                        >
                                            Usar el horario del equipo
                                        </button>
                                    </div>
                                    <WeeklyHoursEditor
                                        v-model="editing[technician.id].shifts"
                                    />
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        :disabled="
                                            hasInvalidRange(
                                                editing[technician.id].shifts,
                                            )
                                        "
                                        :class="ui.buttonPrimary"
                                        @click="saveTechnician(technician.id)"
                                    >
                                        Guardar
                                    </button>
                                    <button
                                        type="button"
                                        :class="ui.buttonSecondary"
                                        @click="delete editing[technician.id]"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </li>
                        <li
                            v-if="technicians.length === 0"
                            class="text-sm text-ink-500"
                        >
                            Todavía no hay técnicos.
                        </li>
                    </ul>

                    <div
                        v-if="can.update"
                        class="mt-5 rounded-xl bg-ink-50 p-4 dark:bg-ink-800/40"
                    >
                        <p
                            class="mb-3 text-sm font-bold text-ink-900 dark:text-white"
                        >
                            Agregar técnico
                        </p>
                        <p
                            v-if="staff.length === 0"
                            class="text-sm text-ink-500"
                        >
                            Todo el staff ya está registrado como técnico.
                        </p>
                        <div
                            v-else
                            class="flex flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            <div class="flex-1">
                                <label for="new-technician" :class="ui.label"
                                    >Persona del equipo</label
                                >
                                <select
                                    id="new-technician"
                                    v-model="newTechnician.central_user_id"
                                    :class="ui.input"
                                >
                                    <option value="" disabled>Elegir…</option>
                                    <option
                                        v-for="member in staff"
                                        :key="member.id"
                                        :value="member.id"
                                    >
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="sm:w-40">
                                <label for="new-capacity" :class="ui.label"
                                    >Sesiones a la vez</label
                                >
                                <input
                                    id="new-capacity"
                                    v-model.number="newTechnician.capacity"
                                    type="number"
                                    min="1"
                                    max="20"
                                    :class="ui.input"
                                />
                            </div>
                            <button
                                type="button"
                                :disabled="!newTechnician.central_user_id"
                                :class="ui.buttonPrimary"
                                @click="createTechnician"
                            >
                                <Icon name="plus" class="size-4" /> Agregar
                            </button>
                        </div>
                        <p v-if="staff.length > 0" :class="ui.help">
                            {{
                                businessHours.length > 0
                                    ? 'Se crea con el horario del equipo; luego puedes ajustar sus turnos.'
                                    : 'Define primero el horario del equipo para que el técnico lo herede.'
                            }}
                        </p>
                    </div>
                </ScheduleSection>

                <ScheduleSection
                    id="section-blackouts"
                    v-model:open="openSections.blackouts"
                    :step="4"
                    icon="calendar"
                    title="Feriados y ausencias"
                    description="Bloquea días en los que no se atiende (feriados) o en los que un técnico no está (vacaciones, permisos). Las citas afectadas pasan a otro técnico libre; si no hay, se cancelan y se avisa al cliente."
                    :summary="
                        blackouts.length > 0
                            ? `Próximo: ${blackouts[0].reason} (${dateTime(blackouts[0].starts_at)})`
                            : 'Sin feriados ni ausencias próximas'
                    "
                >
                    <ul
                        v-if="blackouts.length > 0"
                        class="mb-5 divide-y divide-ink-100 rounded-xl border border-ink-200 dark:divide-ink-800 dark:border-ink-800"
                    >
                        <li
                            v-for="blackout in blackouts"
                            :key="blackout.id"
                            class="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-semibold text-ink-900 dark:text-white"
                                >
                                    {{ blackout.reason }}
                                </p>
                                <p class="text-xs text-ink-500">
                                    {{
                                        blackout.technician_name ??
                                        'Todo el equipo'
                                    }}
                                    · {{ blackoutRange(blackout) }}
                                </p>
                            </div>
                            <button
                                v-if="can.update"
                                type="button"
                                :class="ui.buttonDanger"
                                @click="removeBlackout(blackout.id)"
                            >
                                Eliminar
                            </button>
                        </li>
                    </ul>

                    <form
                        v-if="can.update"
                        class="space-y-4 rounded-xl bg-ink-50 p-4 dark:bg-ink-800/40"
                        @submit.prevent="submitBlackout"
                    >
                        <p
                            class="text-sm font-bold text-ink-900 dark:text-white"
                        >
                            Registrar feriado o ausencia
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="blackout-technician"
                                    :class="ui.label"
                                    >¿A quién aplica?</label
                                >
                                <select
                                    id="blackout-technician"
                                    v-model="blackoutForm.support_technician_id"
                                    :class="ui.input"
                                >
                                    <option value="">
                                        Todo el equipo (feriado)
                                    </option>
                                    <option
                                        v-for="technician in technicians"
                                        :key="technician.id"
                                        :value="technician.id"
                                    >
                                        Solo {{ technician.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="blackout-reason" :class="ui.label"
                                    >Motivo</label
                                >
                                <input
                                    id="blackout-reason"
                                    v-model="blackoutForm.reason"
                                    required
                                    placeholder="Feriado nacional, vacaciones…"
                                    :class="ui.input"
                                />
                                <p
                                    v-if="blackoutForm.errors.reason"
                                    :class="ui.error"
                                >
                                    {{ blackoutForm.errors.reason }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <div
                                class="mb-1.5 flex flex-wrap items-center justify-between gap-2"
                            >
                                <span :class="[ui.label, 'mb-0']"
                                    >¿Qué días?</span
                                >
                                <label
                                    class="inline-flex items-center gap-2 text-sm"
                                >
                                    <input
                                        v-model="blackoutForm.all_day"
                                        type="checkbox"
                                        :class="ui.checkbox"
                                    />
                                    Días completos
                                </label>
                            </div>
                            <DatePicker
                                v-model="blackoutForm.dates"
                                range
                                :auto-apply="blackoutForm.all_day"
                                :min-date="new Date()"
                                :time-config="{
                                    enableTimePicker: !blackoutForm.all_day,
                                    is24: true,
                                    minutesIncrement: 15,
                                }"
                                :formats="{
                                    input: blackoutForm.all_day
                                        ? 'EEE d MMM yyyy'
                                        : 'EEE d MMM yyyy, HH:mm',
                                }"
                                placeholder="Elige un día o un rango de días"
                                class="sm:max-w-md"
                            />
                            <p :class="ui.help">
                                Para un solo día, haz clic dos veces en la misma
                                fecha.
                            </p>
                            <p v-if="blackoutDatesError" :class="ui.error">
                                {{ blackoutDatesError }}
                            </p>
                        </div>
                        <button
                            type="submit"
                            :disabled="
                                blackoutForm.processing || !blackoutForm.dates
                            "
                            :class="ui.buttonPrimary"
                        >
                            Registrar bloqueo
                        </button>
                    </form>
                </ScheduleSection>

                <ScheduleSection
                    id="section-rules"
                    v-model:open="openSections.rules"
                    :step="5"
                    icon="settings"
                    title="Reglas y tarifa"
                    description="Ajustes avanzados. Los valores por defecto sirven para la mayoría de los casos."
                    :summary="rulesSummary"
                >
                    <Form
                        autocomplete="off"
                        v-bind="central.support.settings.update.form()"
                        :options="options"
                        #default="{ errors, processing }"
                        class="space-y-6"
                    >
                        <fieldset>
                            <legend
                                class="mb-3 text-sm font-bold text-ink-900 dark:text-white"
                            >
                                Tarifa
                            </legend>
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-8 md:col-span-4">
                                    <label for="hourly_rate" :class="ui.label"
                                        >Valor por hora</label
                                    >
                                    <input
                                        id="hourly_rate"
                                        name="hourly_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :value="settings.hourly_rate"
                                        :disabled="!can.update"
                                        :class="ui.input"
                                    />
                                    <p :class="ui.help">
                                        Se cobra por las horas que superan el
                                        plan y en solicitudes «por horas».
                                    </p>
                                    <p
                                        v-if="errors.hourly_rate"
                                        :class="ui.error"
                                    >
                                        {{ errors.hourly_rate }}
                                    </p>
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <label for="currency" :class="ui.label"
                                        >Moneda</label
                                    >
                                    <input
                                        id="currency"
                                        name="currency"
                                        maxlength="3"
                                        :value="settings.currency"
                                        :disabled="!can.update"
                                        :class="[ui.input, 'uppercase']"
                                    />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset
                            v-for="group in ruleGroups"
                            :key="group.title"
                        >
                            <legend
                                class="mb-3 text-sm font-bold text-ink-900 dark:text-white"
                            >
                                {{ group.title }}
                            </legend>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div
                                    v-for="field in group.fields"
                                    :key="field.name"
                                >
                                    <label
                                        :for="field.name"
                                        :class="ui.label"
                                        >{{ field.label }}</label
                                    >
                                    <div class="flex items-center gap-2">
                                        <input
                                            :id="field.name"
                                            :name="field.name"
                                            type="number"
                                            min="0"
                                            :value="settings[field.name]"
                                            :disabled="!can.update"
                                            :class="[ui.input, 'w-24']"
                                        />
                                        <span
                                            class="text-sm text-ink-500 dark:text-ink-400"
                                            >{{ field.unit }}</span
                                        >
                                    </div>
                                    <p :class="ui.help">{{ field.help }}</p>
                                    <p
                                        v-if="errors[field.name]"
                                        :class="ui.error"
                                    >
                                        {{ errors[field.name] }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <button
                            v-if="can.update"
                            type="submit"
                            :disabled="processing"
                            :class="ui.buttonPrimary"
                        >
                            Guardar reglas
                        </button>
                    </Form>
                </ScheduleSection>
            </section>
        </div>
    </CentralLayout>
</template>
