<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
import DocumentCapture from '@/components/signatures/DocumentCapture.vue';
import { money } from '@/lib/signatures';
import type { SignatureFormOptions } from '@/lib/signatures';
import { ui } from '@/lib/ui';

/**
 * Applicant data + KYC documents of a signature request. Shared by the
 * operator's create/edit screens and the public storefront; fields and
 * required documents adapt to the applicant type (natural person, legal
 * representative, company member) as Uanataca requires.
 */
const props = withDefaults(
    defineProps<{
        options: SignatureFormOptions;
        action: string;
        method?: 'post' | 'put';
        initial?: Record<string, string | null> | null;
        uploadedDocuments?: string[];
        showSalePrice?: boolean;
        isPublic?: boolean;
        /** Step-by-step flow (public application) instead of one long form. */
        wizard?: boolean;
        selectedProductId?: string | null;
        whatsappUrl?: string | null;
        submitLabel: string;
        cancelHref?: string;
    }>(),
    {
        method: 'post',
        initial: null,
        uploadedDocuments: () => [],
        showSalePrice: false,
        isPublic: false,
        wizard: false,
        selectedProductId: null,
        whatsappUrl: null,
        cancelHref: undefined,
    },
);

const steps = ['Tu firma', 'Tus datos', 'Documentos', 'Confirmar'];
const step = ref(1);
const stepContainer = ref<HTMLElement | null>(null);
const missingDocumentsError = ref<string | null>(null);

/** Whether a step's section is on screen (always, outside the wizard). */
function shows(section: number): boolean {
    return !props.wizard || step.value === section;
}

const fields = [
    'signature_product_id',
    'applicant_type',
    'sale_price',
    'first_names',
    'first_surname',
    'second_surname',
    'document_type',
    'document_number',
    'fingerprint_code',
    'personal_ruc',
    'gender',
    'birth_date',
    'nationality',
    'mobile_phone',
    'landline_phone',
    'email',
    'province',
    'city',
    'address',
    'company_name',
    'company_ruc',
    'position',
    'legal_representative_first_names',
    'legal_representative_surnames',
    'legal_representative_document_type',
    'legal_representative_document_number',
] as const;

const defaults: Record<string, string> = {
    signature_product_id:
        props.selectedProductId ?? props.options.products[0]?.id ?? '',
    applicant_type: 'NaturalPerson',
    document_type: 'Cedula',
    gender: 'Male',
    nationality: 'ECUATORIANA',
    legal_representative_document_type: 'Cedula',
};

type ApplicationFields = Record<(typeof fields)[number], string>;

const form = useForm<
    ApplicationFields & {
        accepts_terms: boolean;
        documents: Record<string, File | null>;
    }
>({
    ...(Object.fromEntries(
        fields.map((field) => [
            field,
            props.initial?.[field] ?? defaults[field] ?? '',
        ]),
    ) as ApplicationFields),
    accepts_terms: false,
    documents: {},
});

const selectedProduct = computed(() =>
    props.options.products.find(
        (product) => product.id === form.signature_product_id,
    ),
);
const requiresCompany = computed(
    () =>
        !['NaturalPerson', 'NaturalPersonWithRuc'].includes(
            form.applicant_type,
        ),
);
const requiresPersonalRuc = computed(
    () => form.applicant_type === 'NaturalPersonWithRuc',
);
const requiresLegalRepresentative = computed(
    () => form.applicant_type === 'CompanyMember',
);
const isCedula = computed(() => form.document_type === 'Cedula');
const requiredKinds = computed(
    () => props.options.requiredDocuments[form.applicant_type as string] ?? [],
);
const optionalKinds = computed(() =>
    requiresCompany.value
        ? ['AppointmentAcceptance', 'Additional']
        : ['Additional'],
);
const documentSlots = computed(() =>
    props.options.documentKinds.filter(
        (kind) =>
            requiredKinds.value.includes(kind.kind) ||
            optionalKinds.value.includes(kind.kind),
    ),
);

function pick(kind: string, file: File | null) {
    form.documents = { ...form.documents, [kind]: file };
}

function errorFor(field: string): string | undefined {
    return (form.errors as Record<string, string>)[field];
}

const missingRequiredDocuments = computed(() =>
    props.options.documentKinds.filter(
        (kind) =>
            requiredKinds.value.includes(kind.kind) &&
            !form.documents[kind.kind] &&
            !props.uploadedDocuments.includes(kind.kind),
    ),
);

/** Wizard step that holds a server-side validation error. */
function stepOf(field: string): number {
    if (
        ['signature_product_id', 'applicant_type', 'sale_price'].includes(field)
    ) {
        return 1;
    }

    if (field.startsWith('documents')) {
        return 3;
    }

    return field === 'accepts_terms' ? 4 : 2;
}

function goTo(target: number) {
    step.value = target;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Native validation of the visible step only. The wizard's <form> is
 * `novalidate`, since the browser would otherwise block on required
 * fields of steps that are hidden.
 */
function currentStepIsValid(): boolean {
    const fieldsOnStep = Array.from(
        stepContainer.value?.querySelectorAll<
            HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
        >('input, select, textarea') ?? [],
    ).filter((field) => field.offsetParent !== null);

    if (!fieldsOnStep.every((field) => field.reportValidity())) {
        return false;
    }

    if (step.value === 3 && missingRequiredDocuments.value.length > 0) {
        missingDocumentsError.value = `Sube: ${missingRequiredDocuments.value
            .map((kind) => kind.label)
            .join(', ')}.`;

        return false;
    }

    missingDocumentsError.value = null;

    return true;
}

function submit() {
    if (props.wizard) {
        if (!currentStepIsValid()) {
            return;
        }

        if (step.value < steps.length) {
            goTo(step.value + 1);

            return;
        }
    }

    form.transform((data) => ({
        ...data,
        documents: Object.fromEntries(
            Object.entries(data.documents).filter(([, file]) => file),
        ),
        ...(props.method === 'put' ? { _method: 'put' } : {}),
    })).post(props.action, {
        forceFormData: true,
        preserveScroll: !props.wizard,
        onError: (errors) => {
            // Only field errors map to a step; others (e.g. a used
            // invitation link) are shown by the page itself.
            const fieldErrors = Object.keys(errors).filter(
                (key) =>
                    (fields as readonly string[]).includes(key) ||
                    key === 'accepts_terms' ||
                    key.startsWith('documents'),
            );

            if (props.wizard && fieldErrors.length > 0) {
                goTo(Math.min(...fieldErrors.map(stepOf)));
            }
        },
    });
}
</script>

<template>
    <form
        ref="stepContainer"
        autocomplete="off"
        :novalidate="wizard"
        class="col-span-12 grid grid-cols-12 gap-6"
        @submit.prevent="submit"
    >
        <ol
            v-if="wizard"
            class="col-span-12 grid grid-cols-4 gap-2 lg:col-span-8 lg:col-start-3"
            aria-label="Progreso de la solicitud"
        >
            <li v-for="(label, index) in steps" :key="label">
                <div
                    :class="[
                        'h-1.5 rounded-full',
                        index + 1 <= step
                            ? 'bg-primary-600'
                            : 'bg-ink-200 dark:bg-ink-800',
                    ]"
                />
                <p
                    :class="[
                        'mt-2 text-xs font-semibold',
                        index + 1 === step
                            ? 'text-primary-700 dark:text-primary-300'
                            : 'text-ink-500',
                    ]"
                    :aria-current="index + 1 === step ? 'step' : undefined"
                >
                    <span class="hidden sm:inline">Paso {{ index + 1 }} · </span
                    >{{ label }}
                </p>
            </li>
        </ol>

        <div
            :class="[
                'col-span-12 space-y-6',
                wizard ? 'lg:col-span-8 lg:col-start-3' : 'xl:col-span-8',
            ]"
        >
            <Card v-show="shows(1)" title="¿Qué tipo de firma necesitas?">
                <div
                    class="grid gap-3 sm:grid-cols-2"
                    role="radiogroup"
                    aria-label="Tipo de firma"
                >
                    <label
                        v-for="type in options.applicantTypeCards"
                        :key="type.value"
                        :class="[
                            'flex cursor-pointer flex-col rounded-xl border p-4 text-center transition',
                            form.applicant_type === type.value
                                ? 'border-primary-500 bg-primary-50/60 ring-2 ring-primary-500 dark:bg-primary-500/10'
                                : 'border-ink-200 hover:border-primary-300 dark:border-ink-700',
                        ]"
                    >
                        <input
                            v-model="form.applicant_type"
                            type="radio"
                            name="applicant_type"
                            :value="type.value"
                            class="sr-only"
                        />
                        <span
                            class="text-base font-extrabold text-ink-950 dark:text-white"
                            >{{ type.label }}</span
                        >
                        <span class="mt-1 text-sm text-ink-500">{{
                            type.description
                        }}</span>
                        <span
                            class="mx-auto my-3 h-0.5 w-full rounded-full bg-gradient-to-r from-primary-700 to-primary-400"
                        />
                        <span class="text-sm text-ink-700 dark:text-ink-200"
                            >Sirve para firmar documentos</span
                        >
                        <span
                            v-if="type.invoicing"
                            class="mt-1 text-sm text-ink-700 dark:text-ink-200"
                            >Sirve para facturación electrónica</span
                        >
                        <span
                            v-else
                            class="mx-auto mt-1.5 rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600 dark:bg-ink-800 dark:text-ink-300"
                            >Sin facturación electrónica</span
                        >
                        <span
                            :class="[
                                'mx-auto mt-4 inline-flex items-center gap-1.5 rounded-lg px-4 py-1.5 text-sm font-bold',
                                form.applicant_type === type.value
                                    ? 'bg-primary-600 text-white'
                                    : 'border border-ink-300 text-ink-800 dark:border-ink-600 dark:text-ink-200',
                            ]"
                        >
                            <Icon
                                v-if="form.applicant_type === type.value"
                                name="check-circle"
                                class="size-4"
                            />
                            {{
                                form.applicant_type === type.value
                                    ? 'Seleccionado'
                                    : 'Seleccionar'
                            }}
                        </span>
                    </label>
                </div>
            </Card>

            <Card v-show="shows(1)" title="Elige la vigencia de tu firma">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        v-for="product in options.products"
                        :key="product.id"
                        :class="[
                            'flex cursor-pointer items-start gap-3 rounded-lg border p-3.5 transition',
                            form.signature_product_id === product.id
                                ? 'border-primary-500 bg-primary-50/60 ring-1 ring-primary-500 dark:bg-primary-500/10'
                                : 'border-ink-200 hover:border-ink-300 dark:border-ink-700',
                        ]"
                    >
                        <input
                            v-model="form.signature_product_id"
                            type="radio"
                            name="signature_product_id"
                            :value="product.id"
                            class="mt-1"
                        />
                        <span class="min-w-0 flex-1">
                            <span
                                class="block font-semibold text-ink-950 dark:text-white"
                                >{{ product.name }}</span
                            >
                            <span class="block text-xs text-ink-500"
                                >Vigencia {{ product.validity_label }} ·
                                {{ product.container_label }}</span
                            >
                        </span>
                        <span
                            v-if="product.retail_price"
                            class="text-sm font-bold text-ink-950 tabular-nums dark:text-white"
                            >{{ money(product.retail_price) }}</span
                        >
                    </label>
                </div>
                <p v-if="errorFor('signature_product_id')" :class="ui.error">
                    {{ errorFor('signature_product_id') }}
                </p>

                <div
                    v-if="showSalePrice"
                    class="mt-5 grid gap-4 sm:grid-cols-2"
                >
                    <div>
                        <label for="sale_price" :class="ui.label"
                            >Precio de venta al cliente (USD)</label
                        >
                        <input
                            id="sale_price"
                            v-model="form.sale_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :placeholder="
                                selectedProduct?.retail_price ?? undefined
                            "
                            :class="ui.input"
                        />
                        <p v-if="errorFor('sale_price')" :class="ui.error">
                            {{ errorFor('sale_price') }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card v-show="shows(2)" title="Datos del titular">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="first_names" :class="ui.label"
                            >Nombres</label
                        >
                        <input
                            id="first_names"
                            v-model="form.first_names"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('first_names')" :class="ui.error">
                            {{ errorFor('first_names') }}
                        </p>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="first_surname" :class="ui.label"
                            >Primer apellido</label
                        >
                        <input
                            id="first_surname"
                            v-model="form.first_surname"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('first_surname')" :class="ui.error">
                            {{ errorFor('first_surname') }}
                        </p>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="second_surname" :class="ui.label"
                            >Segundo apellido</label
                        >
                        <input
                            id="second_surname"
                            v-model="form.second_surname"
                            :class="ui.input"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="document_type" :class="ui.label"
                            >Documento</label
                        >
                        <select
                            id="document_type"
                            v-model="form.document_type"
                            :class="ui.input"
                        >
                            <option
                                v-for="(label, value) in options.documentTypes"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="document_number" :class="ui.label"
                            >Número</label
                        >
                        <input
                            id="document_number"
                            v-model="form.document_number"
                            required
                            :inputmode="isCedula ? 'numeric' : 'text'"
                            :class="ui.input"
                        />
                        <p v-if="errorFor('document_number')" :class="ui.error">
                            {{ errorFor('document_number') }}
                        </p>
                    </div>
                    <div v-if="isCedula" class="sm:col-span-2">
                        <label for="fingerprint_code" :class="ui.label"
                            >Código dactilar</label
                        >
                        <input
                            id="fingerprint_code"
                            v-model="form.fingerprint_code"
                            placeholder="V1234V1234"
                            :class="[ui.input, 'uppercase']"
                        />
                        <p
                            v-if="errorFor('fingerprint_code')"
                            :class="ui.error"
                        >
                            {{ errorFor('fingerprint_code') }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="gender" :class="ui.label"
                            >Sexo (según cédula)</label
                        >
                        <select
                            id="gender"
                            v-model="form.gender"
                            :class="ui.input"
                        >
                            <option
                                v-for="(label, value) in options.genders"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="birth_date" :class="ui.label"
                            >Fecha de nacimiento</label
                        >
                        <input
                            id="birth_date"
                            v-model="form.birth_date"
                            type="date"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('birth_date')" :class="ui.error">
                            {{ errorFor('birth_date') }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="nationality" :class="ui.label"
                            >Nacionalidad</label
                        >
                        <input
                            id="nationality"
                            v-model="form.nationality"
                            required
                            :class="ui.input"
                        />
                    </div>
                    <div v-if="requiresPersonalRuc" class="sm:col-span-3">
                        <label for="personal_ruc" :class="ui.label"
                            >RUC personal</label
                        >
                        <input
                            id="personal_ruc"
                            v-model="form.personal_ruc"
                            inputmode="numeric"
                            maxlength="13"
                            required
                            :placeholder="
                                isCedula && form.document_number
                                    ? `${form.document_number}001`
                                    : undefined
                            "
                            :class="ui.input"
                        />
                        <p :class="ui.help">
                            Tu número de cédula seguido de 001. Habilita la
                            facturación electrónica.
                        </p>
                        <p v-if="errorFor('personal_ruc')" :class="ui.error">
                            {{ errorFor('personal_ruc') }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card v-show="shows(2)" title="Contacto y dirección">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label for="mobile_phone" :class="ui.label"
                            >Celular</label
                        >
                        <input
                            id="mobile_phone"
                            v-model="form.mobile_phone"
                            required
                            placeholder="0991234567"
                            inputmode="tel"
                            :class="ui.input"
                        />
                        <p :class="ui.help">
                            Recibirá el código de activación.
                        </p>
                        <p v-if="errorFor('mobile_phone')" :class="ui.error">
                            {{ errorFor('mobile_phone') }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="landline_phone" :class="ui.label"
                            >Teléfono fijo</label
                        >
                        <input
                            id="landline_phone"
                            v-model="form.landline_phone"
                            placeholder="022123456"
                            inputmode="tel"
                            :class="ui.input"
                        />
                        <p v-if="errorFor('landline_phone')" :class="ui.error">
                            {{ errorFor('landline_phone') }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="email" :class="ui.label">Correo</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('email')" :class="ui.error">
                            {{ errorFor('email') }}
                        </p>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="province" :class="ui.label"
                            >Provincia</label
                        >
                        <input
                            id="province"
                            v-model="form.province"
                            required
                            :class="ui.input"
                        />
                    </div>
                    <div class="sm:col-span-3">
                        <label for="city" :class="ui.label">Ciudad</label>
                        <input
                            id="city"
                            v-model="form.city"
                            required
                            :class="ui.input"
                        />
                    </div>
                    <div class="sm:col-span-6">
                        <label for="address" :class="ui.label">Dirección</label>
                        <input
                            id="address"
                            v-model="form.address"
                            required
                            maxlength="100"
                            :class="ui.input"
                        />
                        <p :class="ui.help">
                            Si incluye RUC, debe coincidir con la dirección
                            registrada en el SRI.
                        </p>
                        <p v-if="errorFor('address')" :class="ui.error">
                            {{ errorFor('address') }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card v-if="requiresCompany" v-show="shows(2)" title="Empresa">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="company_name" :class="ui.label"
                            >Razón social</label
                        >
                        <input
                            id="company_name"
                            v-model="form.company_name"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('company_name')" :class="ui.error">
                            {{ errorFor('company_name') }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company_ruc" :class="ui.label"
                            >RUC de la empresa</label
                        >
                        <input
                            id="company_ruc"
                            v-model="form.company_ruc"
                            required
                            inputmode="numeric"
                            :class="ui.input"
                        />
                        <p v-if="errorFor('company_ruc')" :class="ui.error">
                            {{ errorFor('company_ruc') }}
                        </p>
                    </div>
                    <div class="sm:col-span-6">
                        <label for="position" :class="ui.label">Cargo</label>
                        <input
                            id="position"
                            v-model="form.position"
                            required
                            :class="ui.input"
                        />
                        <p v-if="errorFor('position')" :class="ui.error">
                            {{ errorFor('position') }}
                        </p>
                    </div>
                    <template v-if="requiresLegalRepresentative">
                        <div class="sm:col-span-3">
                            <label
                                for="legal_representative_first_names"
                                :class="ui.label"
                                >Nombres del representante legal</label
                            >
                            <input
                                id="legal_representative_first_names"
                                v-model="form.legal_representative_first_names"
                                :class="ui.input"
                            />
                        </div>
                        <div class="sm:col-span-3">
                            <label
                                for="legal_representative_surnames"
                                :class="ui.label"
                                >Apellidos del representante legal</label
                            >
                            <input
                                id="legal_representative_surnames"
                                v-model="form.legal_representative_surnames"
                                :class="ui.input"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                for="legal_representative_document_type"
                                :class="ui.label"
                                >Documento</label
                            >
                            <select
                                id="legal_representative_document_type"
                                v-model="
                                    form.legal_representative_document_type
                                "
                                :class="ui.input"
                            >
                                <option
                                    v-for="(
                                        label, value
                                    ) in options.documentTypes"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-4">
                            <label
                                for="legal_representative_document_number"
                                :class="ui.label"
                                >Número de documento</label
                            >
                            <input
                                id="legal_representative_document_number"
                                v-model="
                                    form.legal_representative_document_number
                                "
                                :class="ui.input"
                            />
                        </div>
                        <p
                            v-if="
                                errorFor('legal_representative_first_names') ||
                                errorFor('legal_representative_document_number')
                            "
                            :class="[ui.error, 'sm:col-span-6']"
                        >
                            Completa los datos del representante legal.
                        </p>
                    </template>
                </div>
            </Card>
        </div>

        <div
            :class="[
                'col-span-12 space-y-6',
                wizard ? 'lg:col-span-8 lg:col-start-3' : 'xl:col-span-4',
            ]"
        >
            <Card v-show="shows(3)" title="Documentos">
                <div class="space-y-3">
                    <DocumentCapture
                        v-for="documentSlot in documentSlots"
                        :key="documentSlot.kind"
                        :document="documentSlot"
                        :required="requiredKinds.includes(documentSlot.kind)"
                        :already-uploaded="
                            uploadedDocuments.includes(documentSlot.kind)
                        "
                        :file="form.documents[documentSlot.kind] ?? null"
                        :error="errorFor(`documents.${documentSlot.kind}`)"
                        @select="(file) => pick(documentSlot.kind, file)"
                    />
                </div>
                <p :class="[ui.help, 'mt-4']">
                    Desde el celular, toca «Tomar foto» para usar la cámara.
                    Fotos nítidas, tomadas en el momento, sin filtros ni
                    recortes; documentos de empresa en PDF (máximo 13 MB).
                </p>
                <p v-if="missingDocumentsError" :class="[ui.error, 'mt-2']">
                    {{ missingDocumentsError }}
                </p>
                <progress
                    v-if="form.progress"
                    :value="form.progress.percentage"
                    max="100"
                    class="mt-3 w-full"
                />
            </Card>

            <Card v-if="wizard" v-show="shows(4)" title="Revisa tu solicitud">
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-ink-500">Firma</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ selectedProduct?.name }}
                            <span v-if="selectedProduct?.retail_price">
                                ·
                                {{ money(selectedProduct.retail_price) }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Solicitante</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ form.first_names }} {{ form.first_surname }}
                            {{ form.second_surname }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">
                            {{ options.documentTypes[form.document_type] }}
                        </dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ form.document_number }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Tipo</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ options.applicantTypes[form.applicant_type] }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Correo</dt>
                        <dd
                            class="font-semibold break-all text-ink-950 dark:text-white"
                        >
                            {{ form.email }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Celular</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ form.mobile_phone }}
                        </dd>
                    </div>
                    <div v-if="form.company_name" class="sm:col-span-2">
                        <dt class="text-ink-500">Empresa</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{ form.company_name }} · RUC {{ form.company_ruc }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-ink-500">Documentos</dt>
                        <dd class="font-semibold text-ink-950 dark:text-white">
                            {{
                                Object.values(form.documents).filter(Boolean)
                                    .length
                            }}
                            archivo(s) adjunto(s)
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card v-if="isPublic" v-show="shows(4)" title="Autorización">
                <label
                    class="flex items-start gap-3 text-sm text-ink-600 dark:text-ink-300"
                >
                    <input
                        v-model="form.accepts_terms"
                        type="checkbox"
                        required
                        :class="[ui.checkbox, 'mt-0.5']"
                    />
                    <span>
                        Autorizo el tratamiento de mis datos personales y
                        documentos para la emisión de mi firma electrónica,
                        conforme a la Ley Orgánica de Protección de Datos
                        Personales.
                    </span>
                </label>
                <p v-if="errorFor('accepts_terms')" :class="ui.error">
                    Debes aceptar la autorización para continuar.
                </p>
            </Card>
        </div>

        <div
            v-if="wizard"
            class="col-span-12 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-8 lg:col-start-3"
        >
            <a
                v-if="whatsappUrl"
                :href="whatsappUrl"
                target="_blank"
                rel="noopener"
                class="text-sm"
                :class="ui.link"
                >¿Necesitas ayuda? Escríbenos por WhatsApp</a
            >
            <span v-else />
            <div class="flex gap-3">
                <button
                    v-if="step > 1"
                    type="button"
                    :class="[ui.buttonSecondary, 'flex-1 sm:flex-none']"
                    @click="goTo(step - 1)"
                >
                    Atrás
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    :class="[ui.buttonPrimary, 'flex-1 sm:flex-none']"
                >
                    {{
                        step < steps.length
                            ? 'Continuar'
                            : form.processing
                              ? 'Enviando…'
                              : submitLabel
                    }}
                </button>
            </div>
        </div>

        <FormActions
            v-else
            :processing="form.processing"
            :is-dirty="form.isDirty"
            :submit-label="submitLabel"
            processing-label="Guardando…"
            :cancel-href="cancelHref"
        />
    </form>
</template>
