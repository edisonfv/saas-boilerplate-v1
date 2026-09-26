<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store } from '@/actions/Modules/Central/Http/Controllers/TenantController';
import Card from '@/components/Card.vue';
import FormActions from '@/components/FormActions.vue';
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
                class="inline-flex items-center gap-2 text-sm font-medium text-ink-500 hover:text-ink-950 dark:text-ink-400 dark:hover:text-white"
            >
                <Icon name="arrow-left" />
                Volver a tenants
            </Link>

            <div>
                <p class="eyebrow text-primary-600 dark:text-primary-400">
                    Estado comercial
                </p>
                <h2
                    class="mt-2 text-2xl font-extrabold text-ink-950 dark:text-white"
                >
                    Crear tenant
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm text-ink-600 dark:text-ink-400"
                >
                    Aprovisiona la empresa, su dominio, el owner inicial y una
                    suscripción con precio activo.
                </p>
            </div>

            <Form
                autocomplete="off"
                v-bind="store.form()"
                #default="{ errors, processing, isDirty }"
                class="space-y-6"
            >
                <Card title="Datos del tenant">
                    <div
                        class="grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-2"
                    >
                        <div>
                            <label for="id" class="form-label">
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
                                class="form-control w-full"
                                @input="
                                    tenantId = (
                                        $event.target as HTMLInputElement
                                    ).value
                                "
                            />
                            <p
                                class="mt-1 flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400"
                            >
                                <Icon name="globe" class="size-3.5" />
                                {{ tenantId || '{id}' }}.{{
                                    props.centralDomain
                                }}
                            </p>
                            <p v-if="errors.id" class="form-error">
                                {{ errors.id }}
                            </p>
                        </div>

                        <div>
                            <label for="company_name" class="form-label">
                                Empresa
                            </label>
                            <input
                                id="company_name"
                                type="text"
                                name="company_name"
                                required
                                autocomplete="off"
                                class="form-control w-full"
                            />
                            <p v-if="errors.company_name" class="form-error">
                                {{ errors.company_name }}
                            </p>
                        </div>

                        <div>
                            <label for="legal_name" class="form-label">
                                Razón social
                            </label>
                            <input
                                id="legal_name"
                                type="text"
                                name="legal_name"
                                autocomplete="off"
                                class="form-control w-full"
                            />
                            <p v-if="errors.legal_name" class="form-error">
                                {{ errors.legal_name }}
                            </p>
                        </div>

                        <div>
                            <label for="tax_identifier" class="form-label">
                                Identificación fiscal
                            </label>
                            <input
                                id="tax_identifier"
                                type="text"
                                name="tax_identifier"
                                class="form-control w-full"
                            />
                            <p v-if="errors.tax_identifier" class="form-error">
                                {{ errors.tax_identifier }}
                            </p>
                        </div>

                        <div>
                            <label for="country_code" class="form-label">
                                País
                            </label>
                            <input
                                id="country_code"
                                type="text"
                                name="country_code"
                                maxlength="2"
                                placeholder="EC"
                                class="form-control w-full uppercase"
                            />
                            <p v-if="errors.country_code" class="form-error">
                                {{ errors.country_code }}
                            </p>
                        </div>

                        <div>
                            <label for="timezone" class="form-label">
                                Zona horaria
                            </label>
                            <input
                                id="timezone"
                                type="text"
                                name="timezone"
                                value="America/Guayaquil"
                                class="form-control w-full"
                            />
                            <p v-if="errors.timezone" class="form-error">
                                {{ errors.timezone }}
                            </p>
                        </div>

                        <div>
                            <label for="owner_name" class="form-label">
                                Owner
                            </label>
                            <input
                                id="owner_name"
                                type="text"
                                name="owner_name"
                                required
                                autocomplete="off"
                                class="form-control w-full"
                            />
                            <p v-if="errors.owner_name" class="form-error">
                                {{ errors.owner_name }}
                            </p>
                        </div>

                        <div>
                            <label for="owner_email" class="form-label">
                                Email owner
                            </label>
                            <input
                                id="owner_email"
                                type="email"
                                name="owner_email"
                                required
                                autocomplete="off"
                                class="form-control w-full"
                            />
                            <p v-if="errors.owner_email" class="form-error">
                                {{ errors.owner_email }}
                            </p>
                        </div>

                        <div>
                            <label for="owner_password" class="form-label">
                                Contraseña del owner
                            </label>
                            <input
                                id="owner_password"
                                type="password"
                                name="owner_password"
                                required
                                autocomplete="new-password"
                                class="form-control w-full"
                            />
                            <p v-if="errors.owner_password" class="form-error">
                                {{ errors.owner_password }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="owner_password_confirmation"
                                class="form-label"
                            >
                                Confirmar contraseña
                            </label>
                            <input
                                id="owner_password_confirmation"
                                type="password"
                                name="owner_password_confirmation"
                                required
                                autocomplete="new-password"
                                class="form-control w-full"
                            />
                        </div>

                        <div>
                            <label for="plan_id" class="form-label">
                                Plan
                            </label>
                            <select
                                id="plan_id"
                                v-model="selectedPlanId"
                                name="plan_id"
                                required
                                class="form-control w-full"
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
                            <p v-if="errors.plan_id" class="form-error">
                                {{ errors.plan_id }}
                            </p>
                        </div>

                        <div>
                            <label for="billing_period" class="form-label">
                                Periodo de facturación
                            </label>
                            <select
                                id="billing_period"
                                v-model="selectedBillingPeriod"
                                name="billing_period"
                                required
                                class="form-control w-full"
                            >
                                <option value="" disabled selected>
                                    Selecciona un periodo
                                </option>
                                <option
                                    v-for="(label, period) in billingPeriods"
                                    :key="period"
                                    :value="period"
                                    :disabled="
                                        !isBillingPeriodAvailable(period)
                                    "
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
                                class="mt-1 text-xs text-ink-500 dark:text-ink-400"
                            >
                                Solo se podra guardar un periodo con precio
                                activo para el plan seleccionado.
                            </p>
                            <p v-if="errors.billing_period" class="form-error">
                                {{ errors.billing_period }}
                            </p>
                        </div>
                    </div>
                </Card>
                <FormActions
                    :processing="processing"
                    :is-dirty="isDirty"
                    submit-label="Crear tenant"
                    processing-label="Aprovisionando…"
                    :cancel-href="central.tenants.index().url"
                />
            </Form>
        </div>
    </CentralLayout>
</template>
