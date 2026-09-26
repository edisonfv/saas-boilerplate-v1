<script setup lang="ts">
import { computed } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import Icon from '@/components/Icon.vue';

/**
 * 24-hour time picker bound to an "HH:mm" string (the format the backend
 * validates), in 15-minute steps.
 */
withDefaults(
    defineProps<{
        label?: string;
        disabled?: boolean;
        invalid?: boolean;
    }>(),
    { label: undefined, disabled: false, invalid: false },
);

const model = defineModel<string>({ required: true });

const pad = (value: number) => String(value).padStart(2, '0');

const time = computed({
    get: () => {
        const [hours, minutes] = model.value.split(':').map(Number);

        return { hours: hours || 0, minutes: minutes || 0 };
    },
    set: (value: { hours: number; minutes: number } | null) => {
        if (value) {
            model.value = `${pad(value.hours)}:${pad(value.minutes)}`;
        }
    },
});
</script>

<template>
    <DatePicker
        v-model="time"
        time-picker
        auto-apply
        :disabled="disabled"
        :time-config="{
            is24: true,
            minutesIncrement: 15,
            minutesGridIncrement: 15,
        }"
        :input-attrs="{ clearable: false, state: invalid ? false : undefined }"
        :formats="{ input: 'HH:mm' }"
        :aria-labels="label ? { input: label } : undefined"
        class="w-28"
    >
        <template #input-icon>
            <Icon name="clock" class="ml-2.5 size-4 text-ink-400" />
        </template>
    </DatePicker>
</template>
