<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useDateTime } from '@/composables/useDateTime';
import { formatBytes } from '@/lib/support';
import type { TicketAttachment, TicketMessage } from '@/lib/support';
import { cn } from '@/lib/utils';

/**
 * The original request followed by the conversation. Internal notes are
 * only present in the staff payload and are highlighted as such.
 */
defineProps<{
    description: string;
    requesterName: string;
    createdAt: string | null;
    attachments: TicketAttachment[];
    messages: TicketMessage[];
}>();

const { dateTime } = useDateTime();

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <ol class="space-y-4">
        <li class="flex gap-3">
            <span
                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink-100 text-xs font-bold text-ink-700 dark:bg-ink-800 dark:text-ink-200"
            >
                {{ initials(requesterName) }}
            </span>
            <div
                class="min-w-0 flex-1 rounded-xl border border-ink-200 bg-white p-4 dark:border-ink-800 dark:bg-ink-900"
            >
                <p class="text-xs text-ink-500 dark:text-ink-400">
                    <span class="font-semibold text-ink-900 dark:text-white">
                        {{ requesterName }}
                    </span>
                    abrió la solicitud · {{ dateTime(createdAt) }}
                </p>
                <p
                    class="mt-2 text-sm leading-6 whitespace-pre-line text-ink-800 dark:text-ink-200"
                >
                    {{ description }}
                </p>
                <ul v-if="attachments.length" class="mt-3 flex flex-wrap gap-2">
                    <li v-for="file in attachments" :key="file.id">
                        <a
                            :href="file.url"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-primary-300 hover:text-primary-700 dark:border-ink-700 dark:text-ink-200"
                        >
                            <Icon name="paperclip" class="size-3.5" />
                            {{ file.name }}
                            <span class="text-ink-400">{{
                                formatBytes(file.size)
                            }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li v-for="message in messages" :key="message.id" class="flex gap-3">
            <span
                :class="
                    cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        message.author_type === 'Staff'
                            ? 'bg-brand-night text-white dark:bg-primary-500/20 dark:text-primary-200'
                            : 'bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200',
                    )
                "
            >
                {{ initials(message.author_name) }}
            </span>
            <div
                :class="
                    cn(
                        'min-w-0 flex-1 rounded-xl border p-4',
                        message.is_internal
                            ? 'border-accent-300 bg-accent-50 dark:border-accent-500/40 dark:bg-accent-500/10'
                            : message.author_type === 'Staff'
                              ? 'border-primary-100 bg-primary-50/60 dark:border-primary-500/20 dark:bg-primary-500/5'
                              : 'border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900',
                    )
                "
            >
                <p
                    class="flex flex-wrap items-center gap-x-2 text-xs text-ink-500 dark:text-ink-400"
                >
                    <span class="font-semibold text-ink-900 dark:text-white">
                        {{ message.author_name }}
                    </span>
                    <span
                        v-if="message.is_internal"
                        class="rounded bg-accent-200 px-1.5 py-0.5 eyebrow !text-[0.6rem] text-accent-900"
                    >
                        Nota interna
                    </span>
                    <span>{{ dateTime(message.created_at) }}</span>
                </p>
                <p
                    class="mt-2 text-sm leading-6 whitespace-pre-line text-ink-800 dark:text-ink-200"
                >
                    {{ message.body }}
                </p>
                <ul
                    v-if="message.attachments.length"
                    class="mt-3 flex flex-wrap gap-2"
                >
                    <li v-for="file in message.attachments" :key="file.id">
                        <a
                            :href="file.url"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 bg-white px-2.5 py-1 text-xs font-medium text-ink-700 hover:border-primary-300 hover:text-primary-700 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-200"
                        >
                            <Icon name="paperclip" class="size-3.5" />
                            {{ file.name }}
                            <span class="text-ink-400">{{
                                formatBytes(file.size)
                            }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ol>
</template>
