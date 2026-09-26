<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import TimeField from '@/components/TimeField.vue';
import { weekdays } from '@/lib/support';
import type { WeeklyWindow } from '@/lib/support';
import { cn } from '@/lib/utils';

/**
 * One row per weekday with an open/closed switch and its time ranges, so a
 * week is edited the way people think about it ("Monday 8 to 5") instead of
 * as a flat list of windows. Quick templates fill the whole week at once.
 */
withDefaults(defineProps<{ disabled?: boolean }>(), { disabled: false });

const model = defineModel<WeeklyWindow[]>({ required: true });

const templates: {
    label: string;
    days: number[];
    start: string;
    end: string;
}[] = [
    {
        label: 'Lun–Vie · 08:00–17:00',
        days: [1, 2, 3, 4, 5],
        start: '08:00',
        end: '17:00',
    },
    {
        label: 'Lun–Vie · 08:00–20:00',
        days: [1, 2, 3, 4, 5],
        start: '08:00',
        end: '20:00',
    },
    {
        label: 'Lun–Sáb · 08:00–18:00',
        days: [1, 2, 3, 4, 5, 6],
        start: '08:00',
        end: '18:00',
    },
];

function rangesOf(day: number): WeeklyWindow[] {
    return model.value
        .filter((window) => window.weekday === day)
        .sort((a, b) => a.start.localeCompare(b.start));
}

function isOpen(day: number): boolean {
    return model.value.some((window) => window.weekday === day);
}

function isInvalid(window: WeeklyWindow): boolean {
    return window.end <= window.start;
}

function toggleDay(day: number): void {
    if (isOpen(day)) {
        model.value = model.value.filter((window) => window.weekday !== day);

        return;
    }

    // Reuse the ranges of the closest open day so opening Saturday after
    // setting up Friday needs no retyping.
    const reference = [...model.value].sort(
        (a, b) => Math.abs(a.weekday - day) - Math.abs(b.weekday - day),
    )[0];
    const ranges = reference
        ? rangesOf(reference.weekday)
        : [{ weekday: day, start: '08:00', end: '17:00' }];

    model.value = [
        ...model.value,
        ...ranges.map((range) => ({ ...range, weekday: day })),
    ];
}

function addRange(day: number): void {
    const last = rangesOf(day).at(-1);
    const start = last ? last.end : '08:00';
    const [hours, minutes] = start.split(':').map(Number);
    const end = `${String(Math.min(hours + 4, 23)).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

    model.value = [...model.value, { weekday: day, start, end }];
}

function updateRange(
    target: WeeklyWindow,
    key: 'start' | 'end',
    value: string,
): void {
    model.value = model.value.map((window) =>
        window === target ? { ...window, [key]: value } : window,
    );
}

function removeRange(target: WeeklyWindow): void {
    model.value = model.value.filter((window) => window !== target);
}

function copyToOpenDays(day: number): void {
    const ranges = rangesOf(day);
    const openDays = [1, 2, 3, 4, 5, 6, 7].filter(
        (other) => other !== day && isOpen(other),
    );

    model.value = [
        ...model.value.filter((window) => !openDays.includes(window.weekday)),
        ...openDays.flatMap((other) =>
            ranges.map((range) => ({ ...range, weekday: other })),
        ),
    ];
}

function applyTemplate(template: (typeof templates)[number]): void {
    model.value = template.days.map((weekday) => ({
        weekday,
        start: template.start,
        end: template.end,
    }));
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="!disabled" class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-ink-500 dark:text-ink-400">
                Plantillas rápidas:
            </span>
            <button
                v-for="template in templates"
                :key="template.label"
                type="button"
                class="rounded-full border border-ink-200 px-3 py-1 text-xs font-semibold text-ink-700 transition hover:border-primary-400 hover:text-primary-700 dark:border-ink-700 dark:text-ink-200 dark:hover:text-primary-300"
                @click="applyTemplate(template)"
            >
                {{ template.label }}
            </button>
        </div>

        <ul
            class="divide-y divide-ink-100 rounded-xl border border-ink-200 dark:divide-ink-800 dark:border-ink-800"
        >
            <li
                v-for="day in 7"
                :key="day"
                class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start"
            >
                <div class="flex items-center gap-3 sm:w-40 sm:pt-2">
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="isOpen(day)"
                        :aria-label="`${weekdays[day]}: ${isOpen(day) ? 'abierto' : 'cerrado'}`"
                        :disabled="disabled"
                        :class="
                            cn(
                                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60',
                                isOpen(day)
                                    ? 'bg-primary-600'
                                    : 'bg-ink-200 dark:bg-ink-700',
                            )
                        "
                        @click="toggleDay(day)"
                    >
                        <span
                            :class="
                                cn(
                                    'inline-block size-4 rounded-full bg-white shadow transition',
                                    isOpen(day)
                                        ? 'translate-x-4.5'
                                        : 'translate-x-0.5',
                                )
                            "
                        />
                    </button>
                    <span
                        :class="
                            cn(
                                'text-sm font-semibold',
                                isOpen(day)
                                    ? 'text-ink-900 dark:text-white'
                                    : 'text-ink-400',
                            )
                        "
                    >
                        {{ weekdays[day] }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p v-if="!isOpen(day)" class="text-sm text-ink-400 sm:pt-2">
                        Cerrado
                    </p>
                    <div v-else class="space-y-2">
                        <div
                            v-for="(range, index) in rangesOf(day)"
                            :key="`${day}-${index}`"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <TimeField
                                    :model-value="range.start"
                                    :disabled="disabled"
                                    :invalid="isInvalid(range)"
                                    :label="`${weekdays[day]}: desde`"
                                    @update:model-value="
                                        updateRange(range, 'start', $event)
                                    "
                                />
                                <span class="text-sm text-ink-500">a</span>
                                <TimeField
                                    :model-value="range.end"
                                    :disabled="disabled"
                                    :invalid="isInvalid(range)"
                                    :label="`${weekdays[day]}: hasta`"
                                    @update:model-value="
                                        updateRange(range, 'end', $event)
                                    "
                                />
                                <button
                                    v-if="!disabled"
                                    type="button"
                                    class="rounded-lg p-2 text-ink-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    :aria-label="`Quitar horario de ${weekdays[day]}`"
                                    @click="removeRange(range)"
                                >
                                    <Icon name="x-mark" class="size-4" />
                                </button>
                            </div>
                            <p v-if="isInvalid(range)" class="form-error">
                                La hora final debe ser posterior a la inicial.
                            </p>
                        </div>
                        <div
                            v-if="!disabled"
                            class="flex flex-wrap gap-x-4 gap-y-1"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-800 dark:text-primary-300"
                                title="Útil para pausas de almuerzo: 08:00–12:00 y 13:00–17:00"
                                @click="addRange(day)"
                            >
                                <Icon name="plus" class="size-3.5" /> Otro
                                horario
                            </button>
                            <button
                                type="button"
                                class="text-xs font-semibold text-ink-500 hover:text-ink-800 dark:hover:text-white"
                                title="Copia este horario a todos los días abiertos"
                                @click="copyToOpenDays(day)"
                            >
                                Copiar a los demás días
                            </button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
