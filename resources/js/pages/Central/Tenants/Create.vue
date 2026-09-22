<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store } from '@/actions/Modules/Central/Http/Controllers/TenantController';
import Card from '@/components/Card.vue';
import Icon from '@/components/Icon.vue';
import CentralLayout from '@/layouts/CentralLayout.vue';
import central from '@/routes/central';

interface PlanOption {
    id: string;
    name: string;
    billing_periods: Record<string, string>;
}

const props = defineProps<{
    plans: PlanOption[];
    billingPeriods: Record<string, string>;
    centralDomain: string;
}>();

const tenantId = ref('');
const selectedPlanId = ref('');
const selectedBillingPeriod = ref('');
const selectedPlan = computed(() =>
    props.plans.find((plan) => plan.id === selectedPlanId.value),
);
const availableBillingPeriods = computed(
    () => selectedPlan.value?.billing_periods ?? {},
);

function isBillingPeriodAvailable(period: string): boolean {
    return Object.prototype.hasOwnProperty.call(
        availableBillingPeriods.value,
        period,
    );
}

watch(selectedPlanId, () => {
    selectedBillingPeriod.value = '';
});
</script>

<template>
    <Head title="Nuevo tenant" />

    <CentralLayout title="Nuevo tenant">
        <div class="space-y-6">
            <Link
                :href="central.tenants.index().url"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a tenants
            </Link>

            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase dark:text-slate-400"
                >
                    Estado comercial
                </p>
                <h2
                    class="mt-1 text-xl font-semibold text-slate-950 dark:text-white"
                >
                    Crear tenant
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400"
                >
                    Aprovisiona la empresa, su dominio, el owner inicial y una
                    suscripcion con precio activo.
                </p>
            </div>

            <Card title="Datos del tenant">
                <Form
                    v-bind="store.form()"
                    #default="{ errors, processing }"
                    class="grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-2"
                >
                    <div>
                        <label
                            for="id"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Identificador
                        </label>
                        <input
                            id="id"
                            type="text"
                            name="id"
                            required
                            autofocus
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            placeholder="acme"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            @input="
                                tenantId = ($event.target as HTMLInputElement)
                                    .value
                            "
                        />
                        <p
                            class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            <Icon name="globe" class="size-3.5" />
                            {{ tenantId || '{id}' }}.{{ props.centralDomain }}
                        </p>
                        <p v-if="errors.id" class="mt-1 text-sm text-red-600">
                            {{ errors.id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="company_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Empresa
                        </label>
                        <input
                            id="company_name"
                            type="text"
                            name="company_name"
                            required
                            autocomplete="organization"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.company_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.company_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="legal_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Razon social
                        </label>
                        <input
                            id="legal_name"
                            type="text"
                            name="legal_name"
                            autocomplete="organization"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.legal_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.legal_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="tax_identifier"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Identificacion fiscal
                        </label>
                        <input
                            id="tax_identifier"
                            type="text"
                            name="tax_identifier"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.tax_identifier"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.tax_identifier }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="country_code"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Pais
                        </label>
                        <input
                            id="country_code"
                            type="text"
                            name="country_code"
                            maxlength="2"
                            placeholder="EC"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 uppercase focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.country_code"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.country_code }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="timezone"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Zona horaria
                        </label>
                        <input
                            id="timezone"
                            type="text"
                            name="timezone"
                            value="America/Guayaquil"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.timezone"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.timezone }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="owner_name"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Owner
                        </label>
                        <input
                            id="owner_name"
                            type="text"
                            name="owner_name"
                            required
                            autocomplete="name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.owner_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.owner_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="owner_email"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Email owner
                        </label>
                        <input
                            id="owner_email"
                            type="email"
                            name="owner_email"
                            required
                            autocomplete="email"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.owner_email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.owner_email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="owner_password"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Password owner
                        </label>
                        <input
                            id="owner_password"
                            type="password"
                            name="owner_password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                        <p
                            v-if="errors.owner_password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.owner_password }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="owner_password_confirmation"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Confirmar password
                        </label>
                        <input
                            id="owner_password_confirmation"
                            type="password"
                            name="owner_password_confirmation"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label
                            for="plan_id"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Plan
                        </label>
                        <select
                            id="plan_id"
                            v-model="selectedPlanId"
                            name="plan_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="" disabled>
                                Selecciona un plan
                            </option>
                            <option
                                v-for="plan in plans"
                                :key="plan.id"
                                :value="plan.id"
                            >
                                {{ plan.name }}
                            </option>
                        </select>
                        <p
                            v-if="errors.plan_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.plan_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="billing_period"
                            class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Periodo de facturacion
                        </label>
                        <select
                            id="billing_period"
                            v-model="selectedBillingPeriod"
                            name="billing_period"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="" disabled selected>
                                Selecciona un periodo
                            </option>
                            <option
                                v-for="(label, period) in billingPeriods"
                                :key="period"
                                :value="period"
                                :disabled="!isBillingPeriodAvailable(period)"
                            >
                                {{ label }}
                                {{
                                    selectedPlan &&
                                    !isBillingPeriodAvailable(period)
                                        ? ' - sin precio activo'
                                        : ''
                                }}
                            </option>
                        </select>
                        <p
                            v-if="selectedPlan"
                            class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                        >
                            Solo se podra guardar un periodo con precio activo
                            para el plan seleccionado.
                        </p>
                        <p
                            v-if="errors.billing_period"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.billing_period }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            <Icon name="plus" class="size-4.5" />
                            {{
                                processing
                                    ? 'Aprovisionando...'
                                    : 'Crear tenant'
                            }}
                        </button>
                    </div>
                </Form>
            </Card>
        </div>
    </CentralLayout>
</template>
