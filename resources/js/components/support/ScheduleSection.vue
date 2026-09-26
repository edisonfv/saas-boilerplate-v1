<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { cn } from '@/lib/utils';
import type { IconName } from '@/types/icon';

/**
 * Collapsible settings block for the support agenda: collapsed it shows a
 * one-line summary of the current setup, expanded it shows the editor.
 */
defineProps<{
    id: string;
    step: number;
    icon: IconName;
    title: string;
    description: string;
    summary: string;
    pending?: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <section
        :id="id"
        class="scroll-mt-24 rounded-xl border border-ink-200 bg-white shadow-sm shadow-ink-950/[0.03] dark:border-ink-800 dark:bg-ink-900"
    >
        <button
            type="button"
            :aria-expanded="open"
            :aria-controls="`${id}-panel`"
            class="flex w-full items-center gap-4 px-5 py-4 text-left"
            @click="open = !open"
        >
            <span
                :class="
                    cn(
                        'grid size-10 shrink-0 place-items-center rounded-xl',
                        pending
                            ? 'bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-400'
                            : 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
                    )
                "
            >
                <Icon :name="icon" class="size-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block text-sm font-bold text-ink-950 dark:text-white"
                >
                    {{ step }}. {{ title }}
                </span>
                <span
                    :class="
                        cn(
                            'mt-0.5 block truncate text-sm',
                            pending
                                ? 'font-medium text-accent-700 dark:text-accent-400'
                                : 'text-ink-500 dark:text-ink-400',
                        )
                    "
                >
                    {{ summary }}
                </span>
            </span>
            <Icon
                name="chevron-right"
                :class="
                    cn(
                        'size-5 shrink-0 text-ink-400 transition',
                        open && 'rotate-90',
                    )
                "
            />
        </button>
        <div
            v-if="open"
            :id="`${id}-panel`"
            class="border-t border-ink-200 px-5 py-5 dark:border-ink-800"
        >
            <p class="mb-5 text-sm text-ink-600 dark:text-ink-400">
                {{ description }}
            </p>
            <slot />
        </div>
    </section>
</template>
