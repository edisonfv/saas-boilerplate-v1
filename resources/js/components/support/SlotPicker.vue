<script setup lang="ts">
import { computed } from 'vue';
import { useDateTime } from '@/composables/useDateTime';
import type { BookingSlot } from '@/lib/support';
import { cn } from '@/lib/utils';

/**
 * Attendance type → day → time slot. Days and slots come from the server
 * (SupportAvailability), already filtered by capacity and booking rules;
 * the parent reloads them when the type or day changes.
 */
const props = defineProps<{
    types: {
        id: string;
        name: string;
        duration_minutes: number;
        description?: string | null;
    }[];
    typeId: string | null;
    dates: string[];
    date: string | null;
    slots: BookingSlot[];
    loading?: boolean;
}>();

const emit = defineEmits<{
    (
        event: 'change',
        payload: { typeId: string | null; date: string | null },
    ): void;
}>();

const selected = defineModel<string | null>({ default: null });
const { time, timeZone } = useDateTime();

const dayLabel = (date: string) =>
    new Intl.DateTimeFormat('es-EC', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        timeZone: 'UTC',
    }).format(new Date(`${date}T12:00:00Z`));

const groupedSlots = computed(() => {
    const groups: { label: string; slots: BookingSlot[] }[] = [
        { label: 'Mañana', slots: [] },
        { label: 'Tarde', slots: [] },
        { label: 'Noche', slots: [] },
    ];

    for (const slot of props.slots) {
        const hour = Number(time(slot.starts_at).slice(0, 2));
        groups[hour < 12 ? 0 : hour < 18 ? 1 : 2].slots.push(slot);
    }

    return groups.filter((group) => group.slots.length > 0);
});

function selectType(typeId: string): void {
    selected.value = null;
    emit('change', { typeId, date: null });
}

function selectDate(date: string): void {
    selected.value = null;
    emit('change', { typeId: props.typeId, date });
}
</script>

<template>
    <div class="space-y-6">
        <fieldset>
            <legend class="mb-3 eyebrow text-ink-500 dark:text-ink-400">
                1 · Tipo de atención
            </legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button
                    v-for="type in types"
                    :key="type.id"
                    type="button"
                    :aria-pressed="type.id === typeId"
                    :class="
                        cn(
                            'rounded-xl border p-3 text-left transition',
                            type.id === typeId
                                ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-500/10'
                                : 'border-ink-200 bg-white hover:border-primary-300 dark:border-ink-700 dark:bg-ink-900',
                        )
                    "
                    @click="selectType(type.id)"
                >
                    <span
                        class="block text-sm font-bold text-ink-950 dark:text-white"
                    >
                        {{ type.name }}
                    </span>
                    <span class="text-xs text-ink-500 dark:text-ink-400">
                        {{ type.duration_minutes }} min
                    </span>
                </button>
            </div>
        </fieldset>

        <fieldset>
            <legend class="mb-3 eyebrow text-ink-500 dark:text-ink-400">
                2 · Día
            </legend>
            <p
                v-if="dates.length === 0"
                class="text-sm text-ink-500 dark:text-ink-400"
            >
                No hay horarios disponibles en los próximos días para este tipo
                de atención.
            </p>
            <div v-else class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="day in dates"
                    :key="day"
                    type="button"
                    :aria-pressed="day === date"
                    :class="
                        cn(
                            'shrink-0 rounded-lg border px-3 py-2 text-sm font-semibold capitalize transition',
                            day === date
                                ? 'border-primary-600 bg-primary-600 text-white'
                                : 'border-ink-200 bg-white text-ink-700 hover:border-primary-300 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-200',
                        )
                    "
                    @click="selectDate(day)"
                >
                    {{ dayLabel(day) }}
                </button>
            </div>
        </fieldset>

        <fieldset v-if="date">
            <legend class="mb-3 eyebrow text-ink-500 dark:text-ink-400">
                3 · Hora
                <span class="ml-1 tracking-normal normal-case"
                    >({{ timeZone }})</span
                >
            </legend>
            <p v-if="loading" class="text-sm text-ink-500">
                Cargando horarios…
            </p>
            <p
                v-else-if="slots.length === 0"
                class="text-sm text-ink-500 dark:text-ink-400"
            >
                Ese día ya no tiene horarios libres.
            </p>
            <div v-else class="space-y-4">
                <div v-for="group in groupedSlots" :key="group.label">
                    <p
                        class="mb-2 text-xs font-semibold text-ink-500 dark:text-ink-400"
                    >
                        {{ group.label }}
                    </p>
                    <div
                        class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-6"
                    >
                        <button
                            v-for="slot in group.slots"
                            :key="slot.starts_at"
                            type="button"
                            :aria-pressed="selected === slot.starts_at"
                            :title="`${slot.available} cupo(s) disponible(s)`"
                            :class="
                                cn(
                                    'relative rounded-lg border px-2 py-2 text-sm font-semibold tabular-nums transition',
                                    selected === slot.starts_at
                                        ? 'border-primary-600 bg-primary-600 text-white'
                                        : 'border-ink-200 bg-white text-ink-800 hover:border-primary-400 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-100',
                                )
                            "
                            @click="selected = slot.starts_at"
                        >
                            {{ time(slot.starts_at) }}
                            <span
                                v-if="slot.available === 1"
                                class="absolute -top-1.5 -right-1.5 size-3 rounded-full border-2 border-white bg-accent-500 dark:border-ink-900"
                                title="Último cupo"
                            />
                        </button>
                    </div>
                </div>
                <p
                    class="flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400"
                >
                    <span class="size-2.5 rounded-full bg-accent-500" /> Último
                    cupo disponible
                </p>
            </div>
        </fieldset>
    </div>
</template>
