<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/composables/useAppearance';
import type { IconName } from '@/types/icon';

const { appearance, updateAppearance } = useAppearance();

const options: { value: Appearance; icon: IconName; label: string }[] = [
    { value: 'light', icon: 'sun', label: 'Claro' },
    { value: 'dark', icon: 'moon', label: 'Oscuro' },
    { value: 'system', icon: 'monitor', label: 'Sistema' },
];
</script>

<template>
    <div
        class="inline-flex items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 p-0.5 dark:border-gray-800 dark:bg-gray-800/50"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :title="option.label"
            :aria-label="option.label"
            :class="[
                'flex size-7 items-center justify-center rounded-md transition-colors',
                appearance === option.value
                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
            ]"
            @click="updateAppearance(option.value)"
        >
            <Icon :name="option.icon" />
        </button>
    </div>
</template>
