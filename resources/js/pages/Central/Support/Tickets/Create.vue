<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { ui } from '@/lib/ui';
import central from '@/routes/central';

const props = defineProps<{
    tenants: {
        id: string;
        name: string;
        contact_name: string | null;
        contact_email: string | null;
    }[];
    modules: { id: string; name: string }[];
    categories: Record<string, string>;
    priorities: Record<string, string>;
}>();

const tenantId = ref('');
const requesterName = ref('');
const requesterEmail = ref('');

// Pre-fill the requester with the tenant's primary contact (editable).
watch(tenantId, (id) => {
    const tenant = props.tenants.find((item) => item.id === id);

    if (tenant) {
        requesterName.value ||= tenant.contact_name ?? '';
        requesterEmail.value ||= tenant.contact_email ?? '';
    }
});
</script>

<template>
    <Head title="Registrar ticket" />

    <CentralLayout title="Registrar ticket">
        <Form
            autocomplete="off"
            v-bind="central.support.tickets.store.form()"
            #default="{ errors, processing, isDirty }"
            class="grid grid-cols-12 gap-6"
        >
            <div class="col-span-12 space-y-6 xl:col-span-8">
                <Card title="Solicitud">
                    <div class="space-y-4">
                        <div>
                            <label for="subject" :class="ui.label"
                                >Asunto</label
                            >
                            <input
                                id="subject"
                                name="subject"
                                type="text"
                                required
                                autofocus
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
                                :class="ui.input"
                            />
                            <p v-if="errors.description" :class="ui.error">
                                {{ errors.description }}
                            </p>
                        </div>
                        <div>
                            <label for="attachments" :class="ui.label"
                                >Adjuntos</label
                            >
                            <input
                                id="attachments"
                                name="attachments[]"
                                type="file"
                                multiple
                                class="text-sm text-ink-600 dark:text-ink-300"
                            />
                            <p :class="ui.help">
                                Hasta 5 archivos de 10 MB (PDF, imágenes,
                                Office, ZIP).
                            </p>
                            <p v-if="errors.attachments" :class="ui.error">
                                {{ errors.attachments }}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-4">
                <Card title="Cliente">
                    <div class="space-y-4">
                        <div>
                            <label for="tenant_id" :class="ui.label"
                                >Tenant</label
                            >
                            <select
                                id="tenant_id"
                                v-model="tenantId"
                                name="tenant_id"
                                :class="ui.input"
                            >
                                <option value="">Sin tenant (prospecto)</option>
                                <option
                                    v-for="tenant in tenants"
                                    :key="tenant.id"
                                    :value="tenant.id"
                                >
                                    {{ tenant.name }}
                                </option>
                            </select>
                            <p :class="ui.help">
                                Define la cobertura del plan, el SLA y la
                                facturación.
                            </p>
                            <p v-if="errors.tenant_id" :class="ui.error">
                                {{ errors.tenant_id }}
                            </p>
                        </div>
                        <div>
                            <label for="requester_name" :class="ui.label"
                                >Nombre del solicitante</label
                            >
                            <input
                                id="requester_name"
                                v-model="requesterName"
                                name="requester_name"
                                type="text"
                                required
                                :class="ui.input"
                            />
                            <p v-if="errors.requester_name" :class="ui.error">
                                {{ errors.requester_name }}
                            </p>
                        </div>
                        <div>
                            <label for="requester_email" :class="ui.label"
                                >Email del solicitante</label
                            >
                            <input
                                id="requester_email"
                                v-model="requesterEmail"
                                name="requester_email"
                                type="email"
                                required
                                :class="ui.input"
                            />
                            <p v-if="errors.requester_email" :class="ui.error">
                                {{ errors.requester_email }}
                            </p>
                        </div>
                        <div v-if="!tenantId">
                            <label for="requester_company" :class="ui.label"
                                >Empresa</label
                            >
                            <input
                                id="requester_company"
                                name="requester_company"
                                type="text"
                                :class="ui.input"
                            />
                        </div>
                    </div>
                </Card>

                <Card title="Clasificación">
                    <div class="space-y-4">
                        <div>
                            <label for="category" :class="ui.label"
                                >Categoría</label
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
                                >Prioridad</label
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
                        </div>
                        <div>
                            <label for="module_id" :class="ui.label"
                                >Módulo relacionado</label
                            >
                            <select
                                id="module_id"
                                name="module_id"
                                :class="ui.input"
                            >
                                <option value="">General</option>
                                <option
                                    v-for="module in modules"
                                    :key="module.id"
                                    :value="module.id"
                                >
                                    {{ module.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </Card>
            </div>
            <FormActions
                :processing="processing"
                :is-dirty="isDirty"
                submit-label="Registrar ticket"
                processing-label="Registrando…"
                :cancel-href="central.support.tickets.index().url"
            />
        </Form>
    </CentralLayout>
</template>
