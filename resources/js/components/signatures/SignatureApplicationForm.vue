<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
import Icon from '@/components/Icon.vue';
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
        submitLabel: string;
        cancelHref?: string;
    }>(),
    {
        method: 'post',
        initial: null,
        uploadedDocuments: () => [],
        showSalePrice: false,
        isPublic: false,
        cancelHref: undefined,
    },
);

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
    signature_product_id: props.options.products[0]?.id ?? '',
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
const requiresCompany = computed(() => form.applicant_type !== 'NaturalPerson');
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
        : ['RucCopy', 'Additional'],
);
const documentSlots = computed(() =>
    props.options.documentKinds.filter(
        (kind) =>
            requiredKinds.value.includes(kind.kind) ||
            optionalKinds.value.includes(kind.kind),
    ),
);

function pick(kind: string, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.documents = { ...form.documents, [kind]: file };
}

function errorFor(field: string): string | undefined {
    return (form.errors as Record<string, string>)[field];
}

function submit() {
    form.transform((data) => ({
        ...data,
        documents: Object.fromEntries(
            Object.entries(data.documents).filter(([, file]) => file),
        ),
        ...(props.method === 'put' ? { _method: 'put' } : {}),
    })).post(props.action, { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <form
        autocomplete="off"
        class="col-span-12 grid grid-cols-12 gap-6"
        @submit.prevent="submit"
    >
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <Card title="Firma electrónica">
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

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="applicant_type" :class="ui.label"
                            >Tipo de solicitante</label
                        >
                        <select
                            id="applicant_type"
                            v-model="form.applicant_type"
                            :class="ui.input"
                        >
                            <option
                                v-for="(label, value) in options.applicantTypes"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div v-if="showSalePrice">
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

            <Card title="Datos del titular">
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
                    <div class="sm:col-span-3">
                        <label for="personal_ruc" :class="ui.label"
                            >RUC personal (opcional)</label
                        >
                        <input
                            id="personal_ruc"
                            v-model="form.personal_ruc"
                            inputmode="numeric"
                            :class="ui.input"
                        />
                        <p :class="ui.help">
                            Necesario para facturación electrónica.
                        </p>
                        <p v-if="errorFor('personal_ruc')" :class="ui.error">
                            {{ errorFor('personal_ruc') }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card title="Contacto y dirección">
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

            <Card v-if="requiresCompany" title="Empresa">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="company_name" :class="ui.label"
                            >Razón social</label
                        >
                        <input
                            id="company_name"
                            v-model="form.company_name"
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

        <div class="col-span-12 space-y-6 xl:col-span-4">
            <Card title="Documentos">
                <ul class="space-y-4">
                    <li v-for="slot in documentSlots" :key="slot.kind">
                        <label
                            :for="`document-${slot.kind}`"
                            class="flex items-center justify-between gap-2 text-sm font-semibold text-ink-800 dark:text-ink-200"
                        >
                            <span>
                                {{ slot.label }}
                                <span
                                    v-if="!requiredKinds.includes(slot.kind)"
                                    class="font-normal text-ink-400"
                                    >(opcional)</span
                                >
                            </span>
                            <Icon
                                v-if="
                                    form.documents[slot.kind] ||
                                    uploadedDocuments.includes(slot.kind)
                                "
                                name="check-circle"
                                class="size-4.5 text-emerald-600"
                            />
                        </label>
                        <input
                            :id="`document-${slot.kind}`"
                            type="file"
                            :accept="slot.accept"
                            class="mt-1.5 block w-full text-xs text-ink-600 file:mr-3 file:rounded-md file:border-0 file:bg-ink-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold dark:text-ink-300 dark:file:bg-ink-800 dark:file:text-ink-200"
                            @change="pick(slot.kind, $event)"
                        />
                        <p
                            v-if="uploadedDocuments.includes(slot.kind)"
                            :class="ui.help"
                        >
                            Ya cargado. Sube otro archivo solo para
                            reemplazarlo.
                        </p>
                        <p
                            v-if="errorFor(`documents.${slot.kind}`)"
                            :class="ui.error"
                        >
                            {{ errorFor(`documents.${slot.kind}`) }}
                        </p>
                    </li>
                </ul>
                <p :class="[ui.help, 'mt-4']">
                    Fotos nítidas en JPG o PNG; documentos societarios en PDF.
                    Máximo 13 MB por archivo.
                </p>
                <progress
                    v-if="form.progress"
                    :value="form.progress.percentage"
                    max="100"
                    class="mt-3 w-full"
                />
            </Card>

            <Card v-if="isPublic" title="Autorización">
                <label
                    class="flex items-start gap-3 text-sm text-ink-600 dark:text-ink-300"
                >
                    <input
                        v-model="form.accepts_terms"
                        type="checkbox"
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

        <FormActions
            :processing="form.processing"
            :is-dirty="form.isDirty"
            :submit-label="submitLabel"
            processing-label="Guardando…"
            :cancel-href="cancelHref"
        />
    </form>
</template>
