<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import PublicShell from '@/components/PublicShell.vue';
import { ui } from '@/lib/ui';

defineProps<{
    categories: Record<string, string>;
    storeUrl: string;
    tenantName: string | null;
}>();
</script>

<template>
    <Head title="Solicitar soporte" />

    <PublicShell :context="tenantName ?? undefined">
        <div class="col-span-12 lg:col-span-4">
            <p class="eyebrow text-primary-600 dark:text-primary-400">
                Centro de ayuda
            </p>
            <h1
                class="mt-3 text-3xl font-extrabold text-brand-indigo dark:text-white"
            >
                ¿En qué podemos ayudarte?
            </h1>
            <p class="mt-3 text-sm leading-6 text-ink-600 dark:text-ink-400">
                Cuéntanos qué necesitas. Te enviaremos por correo un código y un
                enlace para seguir tu solicitud, sin necesidad de iniciar
                sesión.
            </p>
            <p
                v-if="tenantName"
                class="mt-4 rounded-lg bg-primary-50 px-3 py-2 text-sm text-primary-800 dark:bg-primary-500/10 dark:text-primary-200"
            >
                La solicitud quedará asociada a <strong>{{ tenantName }}</strong
                >.
            </p>
        </div>

        <Card class="col-span-12 lg:col-span-8">
            <Form
                autocomplete="off"
                :action="storeUrl"
                method="post"
                #default="{ errors, processing }"
                class="grid grid-cols-12 gap-4"
            >
                <!-- Honeypot: hidden from people, bots fill it. -->
                <div class="hidden" aria-hidden="true">
                    <label for="website">Sitio web</label>
                    <input
                        id="website"
                        name="website"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>

                <div class="col-span-12 sm:col-span-6">
                    <label for="requester_name" :class="ui.label">Nombre</label>
                    <input
                        id="requester_name"
                        name="requester_name"
                        autocomplete="off"
                        required
                        :class="ui.input"
                    />
                    <p v-if="errors.requester_name" :class="ui.error">
                        {{ errors.requester_name }}
                    </p>
                </div>
                <div class="col-span-12 sm:col-span-6">
                    <label for="requester_email" :class="ui.label">Email</label>
                    <input
                        id="requester_email"
                        name="requester_email"
                        type="email"
                        autocomplete="off"
                        required
                        :class="ui.input"
                    />
                    <p v-if="errors.requester_email" :class="ui.error">
                        {{ errors.requester_email }}
                    </p>
                </div>
                <div v-if="!tenantName" class="col-span-12 sm:col-span-6">
                    <label for="requester_company" :class="ui.label"
                        >Empresa</label
                    >
                    <input
                        id="requester_company"
                        name="requester_company"
                        autocomplete="off"
                        :class="ui.input"
                    />
                </div>
                <div
                    :class="['col-span-12', tenantName ? '' : 'sm:col-span-6']"
                >
                    <label for="category" :class="ui.label"
                        >Tipo de solicitud</label
                    >
                    <select id="category" name="category" :class="ui.input">
                        <option
                            v-for="(label, value) in categories"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div class="col-span-12">
                    <label for="subject" :class="ui.label">Asunto</label>
                    <input
                        id="subject"
                        name="subject"
                        required
                        :class="ui.input"
                    />
                    <p v-if="errors.subject" :class="ui.error">
                        {{ errors.subject }}
                    </p>
                </div>
                <div class="col-span-12">
                    <label for="description" :class="ui.label"
                        >Descripción</label
                    >
                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        required
                        :class="ui.input"
                    />
                    <p v-if="errors.description" :class="ui.error">
                        {{ errors.description }}
                    </p>
                </div>
                <div class="col-span-12">
                    <label for="attachments" :class="ui.label"
                        >Adjuntos (opcional)</label
                    >
                    <input
                        id="attachments"
                        name="attachments[]"
                        type="file"
                        multiple
                        class="text-sm text-ink-600 dark:text-ink-300"
                    />
                    <p v-if="errors.attachments" :class="ui.error">
                        {{ errors.attachments }}
                    </p>
                </div>
                <div class="col-span-12">
                    <button
                        type="submit"
                        :disabled="processing"
                        :class="[ui.buttonPrimary, 'w-full sm:w-auto']"
                    >
                        {{ processing ? 'Enviando…' : 'Enviar solicitud' }}
                    </button>
                </div>
            </Form>
        </Card>
    </PublicShell>
</template>
