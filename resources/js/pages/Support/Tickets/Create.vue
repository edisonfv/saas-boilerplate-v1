<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

defineProps<{
    categories: Record<string, string>;
    priorities: Record<string, string>;
}>();
</script>

<template>
    <Head title="Nueva solicitud" />

    <GeneralLayout title="Nueva solicitud">
        <Form
            autocomplete="off"
            v-bind="tenant.support.tickets.store.form()"
            #default="{ errors, processing, isDirty }"
            class="col-span-12 grid grid-cols-12 gap-6"
        >
            <Card
                title="¿En qué podemos ayudarte?"
                class="col-span-12 xl:col-span-8"
            >
                <div class="space-y-4">
                    <div>
                        <label for="subject" :class="ui.label">Asunto</label>
                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            required
                            autofocus
                            placeholder="Resume el problema en una línea"
                            :class="ui.input"
                        />
                        <p v-if="errors.subject" :class="ui.error">
                            {{ errors.subject }}
                        </p>
                    </div>
                    <div>
                        <label for="description" :class="ui.label"
                            >Descripción</label
                        >
                        <textarea
                            id="description"
                            name="description"
                            rows="8"
                            required
                            placeholder="¿Qué intentabas hacer, qué pasó y qué esperabas que pasara?"
                            :class="ui.input"
                        />
                        <p v-if="errors.description" :class="ui.error">
                            {{ errors.description }}
                        </p>
                    </div>
                    <div>
                        <label for="attachments" :class="ui.label"
                            >Capturas o archivos</label
                        >
                        <input
                            id="attachments"
                            name="attachments[]"
                            type="file"
                            multiple
                            class="text-sm text-ink-600 dark:text-ink-300"
                        />
                        <p :class="ui.help">Hasta 5 archivos de 10 MB.</p>
                        <p v-if="errors.attachments" :class="ui.error">
                            {{ errors.attachments }}
                        </p>
                    </div>
                </div>
            </Card>

            <div class="col-span-12 space-y-6 xl:col-span-4">
                <Card title="Clasificación">
                    <div class="space-y-4">
                        <div>
                            <label for="category" :class="ui.label"
                                >Tipo de solicitud</label
                            >
                            <select
                                id="category"
                                name="category"
                                :class="ui.input"
                            >
                                <option
                                    v-for="(label, value) in categories"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label for="priority" :class="ui.label"
                                >Impacto</label
                            >
                            <select
                                id="priority"
                                name="priority"
                                :class="ui.input"
                            >
                                <option
                                    v-for="(label, value) in priorities"
                                    :key="value"
                                    :value="value"
                                    :selected="value === 'Normal'"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <p :class="ui.help">
                                Nuestro equipo puede ajustar la prioridad al
                                revisarla.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
            <FormActions
                :processing="processing"
                :is-dirty="isDirty"
                submit-label="Enviar solicitud"
                processing-label="Enviando…"
                :cancel-href="tenant.support.tickets.index().url"
            />
        </Form>
    </GeneralLayout>
</template>
