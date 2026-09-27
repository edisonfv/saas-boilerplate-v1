<script setup lang="ts">
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import { ui } from '@/lib/ui';

/**
 * Editor for one repeatable section of the public website (benefits,
 * steps, FAQ): add, remove and reorder entries of two text fields.
 * An empty list hides the section on the site.
 */
type Row = Record<string, string>;

const props = defineProps<{
    title: string;
    description: string;
    /** Field name in the form, used to resolve "name.0.key" errors. */
    name: string;
    fields: { key: string; label: string; multiline?: boolean; max: number }[];
    addLabel: string;
    maxRows: number;
    errors: Record<string, string>;
}>();

const rows = defineModel<Row[]>({ required: true });

function add() {
    rows.value = [
        ...rows.value,
        Object.fromEntries(props.fields.map((field) => [field.key, ''])),
    ];
}

function remove(index: number) {
    rows.value = rows.value.filter((_, position) => position !== index);
}

function move(index: number, offset: number) {
    const target = index + offset;

    if (target < 0 || target >= rows.value.length) {
        return;
    }

    const reordered = [...rows.value];
    [reordered[index], reordered[target]] = [
        reordered[target],
        reordered[index],
    ];
    rows.value = reordered;
}

/**
 * Edits the entry in place: rebuilding the list here would read the model
 * before the parent re-renders, so two quick edits (typing, autofill) would
 * overwrite each other.
 */
function update(index: number, key: string, value: string) {
    const row = rows.value[index];

    if (row) {
        row[key] = value;
    }
}
</script>

<template>
    <Card :title="title">
        <p class="mb-4 text-sm text-ink-600 dark:text-ink-400">
            {{ description }}
        </p>

        <ol class="space-y-4">
            <li
                v-for="(row, index) in rows"
                :key="index"
                class="rounded-lg border border-ink-200 p-4 dark:border-ink-800"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-ink-500"
                        >#{{ index + 1 }}</span
                    >
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-ink-600 hover:bg-ink-100 disabled:opacity-40 dark:text-ink-300 dark:hover:bg-ink-800"
                            :disabled="index === 0"
                            :aria-label="`Subir #${index + 1}`"
                            @click="move(index, -1)"
                        >
                            ↑
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-ink-600 hover:bg-ink-100 disabled:opacity-40 dark:text-ink-300 dark:hover:bg-ink-800"
                            :disabled="index === rows.length - 1"
                            :aria-label="`Bajar #${index + 1}`"
                            @click="move(index, 1)"
                        >
                            ↓
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                            @click="remove(index)"
                        >
                            Quitar
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div v-for="field in fields" :key="field.key">
                        <label
                            :for="`${name}-${index}-${field.key}`"
                            :class="ui.label"
                            >{{ field.label }}</label
                        >
                        <textarea
                            v-if="field.multiline"
                            :id="`${name}-${index}-${field.key}`"
                            :value="row[field.key]"
                            rows="3"
                            :maxlength="field.max"
                            required
                            :class="ui.input"
                            @input="
                                update(
                                    index,
                                    field.key,
                                    ($event.target as HTMLTextAreaElement)
                                        .value,
                                )
                            "
                        />
                        <input
                            v-else
                            :id="`${name}-${index}-${field.key}`"
                            :value="row[field.key]"
                            :maxlength="field.max"
                            required
                            :class="ui.input"
                            @input="
                                update(
                                    index,
                                    field.key,
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <p
                            v-if="errors[`${name}.${index}.${field.key}`]"
                            :class="ui.error"
                        >
                            {{ errors[`${name}.${index}.${field.key}`] }}
                        </p>
                    </div>
                </div>
            </li>
        </ol>

        <p
            v-if="!rows.length"
            class="rounded-lg bg-ink-50 p-3 text-sm text-ink-500 dark:bg-ink-800/50"
        >
            Sin entradas: esta sección no se mostrará en el sitio.
        </p>
        <p v-if="errors[name]" :class="[ui.error, 'mt-2']">
            {{ errors[name] }}
        </p>

        <button
            v-if="rows.length < maxRows"
            type="button"
            :class="[ui.buttonSecondary, 'mt-4']"
            @click="add"
        >
            <Icon name="plus" class="size-4" /> {{ addLabel }}
        </button>
    </Card>
</template>
