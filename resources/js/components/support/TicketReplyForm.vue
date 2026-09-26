<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { ui } from '@/lib/ui';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * Reply box shared by the console, the tenant workspace and the public
 * tracking page. `allowInternal` adds the staff-only "internal note" switch.
 */
withDefaults(
    defineProps<{
        form: RouteFormDefinition<'post'>;
        allowInternal?: boolean;
        hiddenFields?: Record<string, string>;
        placeholder?: string;
        submitLabel?: string;
    }>(),
    {
        allowInternal: false,
        hiddenFields: () => ({}),
        placeholder: 'Escribe tu respuesta…',
        submitLabel: 'Enviar respuesta',
    },
);

const isInternal = ref(false);
</script>

<template>
    <Form
        autocomplete="off"
        v-bind="form"
        reset-on-success
        :options="{ preserveScroll: true }"
        #default="{ errors, processing }"
        :class="[
            'rounded-xl border p-4 transition-colors',
            isInternal
                ? 'border-accent-300 bg-accent-50 dark:border-accent-500/40 dark:bg-accent-500/10'
                : 'border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900',
        ]"
    >
        <input
            v-for="(value, name) in hiddenFields"
            :key="name"
            type="hidden"
            :name="name"
            :value="value"
        />

        <label for="reply-body" class="sr-only">Mensaje</label>
        <textarea
            id="reply-body"
            name="body"
            rows="4"
            required
            :placeholder="
                isInternal ? 'Nota visible solo para el staff…' : placeholder
            "
            :class="[ui.input, 'resize-y']"
        />
        <p v-if="errors.body" :class="ui.error">{{ errors.body }}</p>

        <div
            class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-4">
                <label
                    class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-ink-600 hover:text-ink-900 dark:text-ink-300"
                >
                    <Icon name="paperclip" class="size-4" />
                    <span>Adjuntar</span>
                    <input
                        type="file"
                        name="attachments[]"
                        multiple
                        class="max-w-48 text-xs file:hidden"
                    />
                </label>

                <label
                    v-if="allowInternal"
                    class="inline-flex items-center gap-2 text-sm font-medium text-ink-600 dark:text-ink-300"
                >
                    <input
                        type="hidden"
                        name="is_internal"
                        :value="isInternal ? '1' : '0'"
                    />
                    <input
                        v-model="isInternal"
                        type="checkbox"
                        :class="ui.checkbox"
                    />
                    Nota interna
                </label>
            </div>

            <button
                type="submit"
                :disabled="processing"
                :class="ui.buttonPrimary"
            >
                {{
                    processing
                        ? 'Enviando…'
                        : isInternal
                          ? 'Guardar nota'
                          : submitLabel
                }}
            </button>
        </div>
        <p v-if="errors.attachments" :class="ui.error">
            {{ errors.attachments }}
        </p>
        <p
            v-for="(message, key) in errors"
            v-show="String(key).startsWith('attachments.')"
            :key="key"
            :class="ui.error"
        >
            {{ message }}
        </p>
    </Form>
</template>
