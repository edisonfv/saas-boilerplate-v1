<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ui } from '@/lib/ui';

/**
 * Sticky action bar for long forms: it floats at the bottom of the viewport
 * while the form scrolls, so the primary action is always reachable, and
 * settles in place once the end of the form comes into view.
 * Place it as the last child of the <Form>.
 */
withDefaults(
    defineProps<{
        submitLabel: string;
        processingLabel?: string;
        processing?: boolean;
        cancelHref?: string;
        cancelLabel?: string;
        isDirty?: boolean;
    }>(),
    {
        processingLabel: 'Guardando…',
        processing: false,
        cancelHref: undefined,
        cancelLabel: 'Cancelar',
        isDirty: false,
    },
);
</script>

<template>
    <div
        class="sticky bottom-3 z-20 col-span-full flex flex-col gap-3 rounded-xl bg-white/95 px-4 py-3 shadow-[0_12px_32px_-8px_rgb(14_21_53/0.22),0_2px_6px_-2px_rgb(14_21_53/0.08)] ring-1 ring-ink-950/5 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-5 md:bottom-4 dark:bg-ink-900/95 dark:shadow-[0_12px_32px_-8px_rgb(0_0_0/0.6)] dark:ring-white/10"
    >
        <div class="min-w-0 text-xs text-ink-500 dark:text-ink-400">
            <slot name="hint">
                <span
                    v-if="isDirty"
                    class="inline-flex items-center gap-2 font-medium text-accent-700 dark:text-accent-400"
                >
                    <span class="size-2 rounded-full bg-accent-500" />
                    Cambios sin guardar
                </span>
            </slot>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-3">
            <slot name="secondary" />
            <Link
                v-if="cancelHref"
                :href="cancelHref"
                :class="[ui.buttonSecondary, 'flex-1 sm:flex-none']"
            >
                {{ cancelLabel }}
            </Link>
            <button
                type="submit"
                :disabled="processing"
                :class="[ui.buttonPrimary, 'flex-1 sm:flex-none']"
            >
                {{ processing ? processingLabel : submitLabel }}
            </button>
        </div>
    </div>
</template>
