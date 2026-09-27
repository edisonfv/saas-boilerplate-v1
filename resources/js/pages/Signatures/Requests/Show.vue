<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import PaymentPanel from '@/components/signatures/PaymentPanel.vue';
import QuotaCard from '@/components/signatures/QuotaCard.vue';
import { useDateTime } from '@/composables/useDateTime';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import {
    money,
    paymentStatusTone,
    signatureStatusTone,
} from '@/lib/signatures';
import type { PaymentRecord, SignatureAccount } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import tenant from '@/routes/tenant';

interface RequestDetail {
    id: string;
    code: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    source_label: string;
    applicant_name: string;
    applicant_type_label: string;
    product_name: string;
    validity_label: string;
    container_label: string;
    sale_price: string | null;
    document_type_label: string;
    document_number: string;
    fingerprint_code: string | null;
    personal_ruc: string | null;
    gender_label: string;
    birth_date: string;
    nationality: string;
    mobile_phone: string;
    landline_phone: string | null;
    email: string;
    province: string;
    city: string;
    address: string;
    company_name: string | null;
    company_ruc: string | null;
    position: string | null;
    legal_representative_first_names: string | null;
    legal_representative_surnames: string | null;
    is_editable: boolean;
    provider_token: string | null;
    last_error: string | null;
    certificate_serial: string | null;
    certificate_valid_from: string | null;
    certificate_valid_to: string | null;
    submitted_at: string | null;
    issued_at: string | null;
    created_at: string;
    missing_documents: { kind: string; label: string }[];
    documents: {
        id: string;
        kind: string;
        kind_label: string;
        original_name: string;
        size: number;
        url: string;
    }[];
    events: {
        id: string;
        status: string;
        status_label: string;
        description: string;
        actor_name: string | null;
        created_at: string;
    }[];
}

defineProps<{
    request: RequestDetail;
    payments: PaymentRecord[];
    paymentLink: string | null;
    paymentWhatsappUrl: string | null;
    paymentMethods: Record<string, string>;
    account: SignatureAccount | null;
    can: {
        update: boolean;
        delete: boolean;
        submit: boolean;
        payments: boolean;
    };
}>();

const { dateTime, date } = useDateTime();

const confirmDelete = () => window.confirm('¿Eliminar este borrador?');
const confirmSubmit = () =>
    window.confirm(
        'Se enviará a Uanataca y se descontará una firma de tu cupo. ¿Continuar?',
    );
</script>

<template>
    <Head :title="request.code" />

    <GeneralLayout :title="request.code">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <Link
                :href="tenant.signatures.requests.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" /> Volver a solicitudes
            </Link>

            <Card>
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="text-xl font-bold text-ink-950 dark:text-white"
                            >
                                {{ request.applicant_name }}
                            </h2>
                            <Badge :tone="signatureStatusTone(request.status)">
                                {{ request.status_label }}
                            </Badge>
                            <Badge
                                :tone="
                                    paymentStatusTone(request.payment_status)
                                "
                            >
                                {{ request.payment_status_label }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-ink-500">
                            <span class="font-mono">{{ request.code }}</span> ·
                            {{ request.product_name }} ·
                            {{ request.applicant_type_label }} ·
                            {{ request.source_label }}
                        </p>
                    </div>
                    <div
                        v-if="request.is_editable"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <Form
                            v-if="can.delete"
                            v-bind="
                                tenant.signatures.requests.destroy.form(
                                    request.id,
                                )
                            "
                            #default="{ processing }"
                            :on-before="confirmDelete"
                        >
                            <button
                                type="submit"
                                :disabled="processing"
                                :class="ui.buttonDanger"
                            >
                                Eliminar
                            </button>
                        </Form>
                        <Link
                            v-if="can.update"
                            :href="
                                tenant.signatures.requests.edit(request.id).url
                            "
                            :class="ui.buttonSecondary"
                        >
                            <Icon name="pencil" class="size-4.5" /> Editar
                        </Link>
                    </div>
                </div>

                <div
                    v-if="request.last_error"
                    class="mt-4 flex gap-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300"
                >
                    <Icon name="exclamation-triangle" class="size-5 shrink-0" />
                    <p>{{ request.last_error }}</p>
                </div>

                <Form
                    v-if="request.is_editable && can.submit"
                    v-bind="tenant.signatures.requests.submit.form(request.id)"
                    #default="{ processing, errors }"
                    class="mt-5 rounded-lg border border-primary-200 bg-primary-50/60 p-4 dark:border-primary-500/30 dark:bg-primary-500/10"
                    :on-before="confirmSubmit"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="text-sm text-ink-700 dark:text-ink-300">
                            <p
                                class="font-semibold text-ink-950 dark:text-white"
                            >
                                Enviar a la entidad certificadora
                            </p>
                            <p v-if="request.payment_status !== 'Paid'">
                                Primero confirma el pago del cliente.
                            </p>
                            <p v-else-if="request.missing_documents.length">
                                Faltan:
                                {{
                                    request.missing_documents
                                        .map((doc) => doc.label)
                                        .join(', ')
                                }}.
                            </p>
                            <p v-else>
                                Verifica los datos: al enviar se consume una
                                firma de tu cupo.
                            </p>
                        </div>
                        <button
                            type="submit"
                            :disabled="
                                processing ||
                                request.payment_status !== 'Paid' ||
                                request.missing_documents.length > 0
                            "
                            :class="ui.buttonPrimary"
                        >
                            <Icon name="arrow-right" class="size-4.5" />
                            {{ processing ? 'Enviando…' : 'Enviar a Uanataca' }}
                        </button>
                    </div>
                    <p v-if="errors.submit" :class="[ui.error, 'mt-2']">
                        {{ errors.submit }}
                    </p>
                </Form>
            </Card>

            <PaymentPanel
                :request-id="request.id"
                :status="request.payment_status"
                :status-label="request.payment_status_label"
                :sale-price="request.sale_price"
                :payments="payments"
                :payment-link="paymentLink"
                :whatsapp-url="paymentWhatsappUrl"
                :methods="paymentMethods"
                :can-manage="can.payments"
            />

            <Card title="Datos del titular">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-ink-500">
                            {{ request.document_type_label }}
                        </dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.document_number }}
                        </dd>
                    </div>
                    <div v-if="request.fingerprint_code">
                        <dt class="text-ink-500">Código dactilar</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.fingerprint_code }}
                        </dd>
                    </div>
                    <div v-if="request.personal_ruc">
                        <dt class="text-ink-500">RUC personal</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.personal_ruc }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Sexo</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.gender_label }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Nacimiento</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ date(`${request.birth_date}T12:00:00Z`) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Nacionalidad</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.nationality }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Celular</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.mobile_phone }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Correo</dt>
                        <dd
                            class="font-medium break-all text-ink-950 dark:text-white"
                        >
                            {{ request.email }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Ubicación</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.city }}, {{ request.province }}
                        </dd>
                    </div>
                    <div class="sm:col-span-3">
                        <dt class="text-ink-500">Dirección</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.address }}
                        </dd>
                    </div>
                    <template v-if="request.company_name">
                        <div>
                            <dt class="text-ink-500">Empresa</dt>
                            <dd
                                class="font-medium text-ink-950 dark:text-white"
                            >
                                {{ request.company_name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">RUC empresa</dt>
                            <dd
                                class="font-medium text-ink-950 dark:text-white"
                            >
                                {{ request.company_ruc }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Cargo</dt>
                            <dd
                                class="font-medium text-ink-950 dark:text-white"
                            >
                                {{ request.position }}
                            </dd>
                        </div>
                    </template>
                    <div v-if="request.legal_representative_first_names">
                        <dt class="text-ink-500">Representante legal</dt>
                        <dd class="font-medium text-ink-950 dark:text-white">
                            {{ request.legal_representative_first_names }}
                            {{ request.legal_representative_surnames }}
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card title="Historial">
                <ol class="space-y-4">
                    <li
                        v-for="event in request.events"
                        :key="event.id"
                        class="flex gap-3"
                    >
                        <span
                            class="mt-1.5 size-2 shrink-0 rounded-full bg-primary-500"
                        />
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-medium text-ink-950 dark:text-white">
                                {{ event.description }}
                            </p>
                            <p class="text-xs text-ink-500">
                                {{ dateTime(event.created_at) }}
                                <template v-if="event.actor_name">
                                    · {{ event.actor_name }}</template
                                >
                            </p>
                        </div>
                        <Badge :tone="signatureStatusTone(event.status)">{{
                            event.status_label
                        }}</Badge>
                    </li>
                </ol>
            </Card>
        </div>

        <aside class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Venta">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Firma</dt>
                        <dd
                            class="text-right font-medium text-ink-950 dark:text-white"
                        >
                            {{ request.validity_label }} ·
                            {{ request.container_label }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Precio al cliente</dt>
                        <dd
                            class="font-medium text-ink-950 tabular-nums dark:text-white"
                        >
                            {{
                                request.sale_price
                                    ? money(request.sale_price)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Enviada</dt>
                        <dd class="text-ink-950 dark:text-white">
                            {{ dateTime(request.submitted_at) }}
                        </dd>
                    </div>
                    <div
                        v-if="request.provider_token"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-ink-500">Token Uanataca</dt>
                        <dd
                            class="font-mono text-xs break-all text-ink-950 dark:text-white"
                        >
                            {{ request.provider_token }}
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card v-if="request.certificate_serial" title="Certificado emitido">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Serie</dt>
                        <dd
                            class="font-mono text-xs text-ink-950 dark:text-white"
                        >
                            {{ request.certificate_serial }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Vigente desde</dt>
                        <dd class="text-ink-950 dark:text-white">
                            {{ date(request.certificate_valid_from) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-500">Vence</dt>
                        <dd class="text-ink-950 dark:text-white">
                            {{ date(request.certificate_valid_to) }}
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card title="Documentos">
                <ul class="space-y-2">
                    <li
                        v-for="document in request.documents"
                        :key="document.id"
                    >
                        <a
                            :href="document.url"
                            class="flex items-center gap-2 text-sm"
                            :class="ui.link"
                        >
                            <Icon name="paperclip" class="size-4 shrink-0" />
                            {{ document.kind_label }}
                        </a>
                    </li>
                    <li
                        v-for="missing in request.missing_documents"
                        :key="missing.kind"
                        class="flex items-center gap-2 text-sm text-accent-700 dark:text-accent-400"
                    >
                        <Icon name="exclamation-triangle" class="size-4" />
                        Falta: {{ missing.label }}
                    </li>
                </ul>
            </Card>

            <QuotaCard :account="account" />
        </aside>
    </GeneralLayout>
</template>
