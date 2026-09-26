<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { ui } from '@/lib/ui';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * 1–5 star CSAT survey. Answering "No" to "¿Se resolvió?" reopens the ticket.
 */
defineProps<{
    form: RouteFormDefinition<'post'>;
}>();

const stars = ref(0);
const hovered = ref(0);
const wasResolved = ref<'1' | '0'>('1');
const labels = ['', 'Muy mala', 'Mala', 'Regular', 'Buena', 'Excelente'];
</script>

<template>
    <Form
        autocomplete="off"
        v-bind="form"
        :options="{ preserveScroll: true }"
        #default="{ errors, processing }"
        class="space-y-4"
    >
        <div>
            <p :class="ui.label">¿Cómo calificas la atención?</p>
            <div class="flex items-center gap-1" @mouseleave="hovered = 0">
                <button
                    v-for="value in 5"
                    :key="value"
                    type="button"
                    class="rounded-md p-0.5 transition hover:scale-110"
                    :aria-label="`${value} estrella${value > 1 ? 's' : ''}`"
                    :aria-pressed="stars === value"
                    @mouseenter="hovered = value"
                    @click="stars = value"
                >
                    <Icon
                        name="star"
                        :class="[
                            'size-8',
                            value <= (hovered || stars)
                                ? 'fill-accent-400 text-accent-500'
                                : 'text-ink-300 dark:text-ink-600',
                        ]"
                    />
                </button>
                <span
                    class="ml-2 text-sm font-medium text-ink-600 dark:text-ink-300"
                >
                    {{ labels[hovered || stars] }}
                </span>
            </div>
            <input type="hidden" name="stars" :value="stars || ''" />
            <p v-if="errors.stars" :class="ui.error">{{ errors.stars }}</p>
        </div>

        <fieldset>
            <legend :class="ui.label">¿Se resolvió tu problema?</legend>
            <div class="flex gap-4 text-sm text-ink-700 dark:text-ink-200">
                <label class="inline-flex items-center gap-2">
                    <input
                        v-model="wasResolved"
                        type="radio"
                        name="was_resolved"
                        value="1"
                        class="accent-primary-600"
                    />
                    Sí
                </label>
                <label class="inline-flex items-center gap-2">
                    <input
                        v-model="wasResolved"
                        type="radio"
                        name="was_resolved"
                        value="0"
                        class="accent-primary-600"
                    />
                    No, necesito más ayuda
                </label>
            </div>
            <p v-if="wasResolved === '0'" :class="ui.help">
                Reabriremos tu solicitud para seguir atendiéndote.
            </p>
        </fieldset>

        <div>
            <label for="rating-comment" :class="ui.label"
                >Comentario (opcional)</label
            >
            <textarea
                id="rating-comment"
                name="comment"
                rows="3"
                :class="ui.input"
            />
        </div>

        <button
            type="submit"
            :disabled="processing || stars === 0"
            :class="ui.buttonPrimary"
        >
            Enviar calificación
        </button>
    </Form>
</template>
