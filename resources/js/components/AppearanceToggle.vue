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
        class="inline-flex items-center gap-0.5 rounded-lg border border-ink-200 bg-ink-50 p-0.5 dark:border-ink-800 dark:bg-ink-800/50"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :title="option.label"
            :aria-label="`Tema ${option.label.toLowerCase()}`"
            :aria-pressed="appearance === option.value"
            :class="[
                'flex size-7 items-center justify-center rounded-md transition-colors',
                appearance === option.value
                    ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-white'
                    : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white',
            ]"
            @click="updateAppearance(option.value)"
        >
            <Icon :name="option.icon" class="size-4" />
        </button>
    </div>
</template>
