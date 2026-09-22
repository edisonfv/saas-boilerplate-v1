<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import GeneralLayout from '@/layouts/GeneralLayout.vue';
import { subscriptionStatusTone } from '@/lib/status';

interface Subscription {
    plan_name: string;
    billing_period_label: string;
    status: string;
    status_label: string;
    trial_ends_at: string | null;
    current_period_end: string | null;
}

interface Entitlements {
    modules: string[];
    features: string[];
    limits: Record<string, number>;
}

defineProps<{
    tenant: { id: string };
    subscription: Subscription | null;
    entitlements: Entitlements | null;
}>();
</script>

<template>
    <Head title="Mi Dashboard" />

    <GeneralLayout title="Mi Dashboard" :tenant-id="tenant.id">
        <template v-if="subscription">
            <Card class="mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Plan actual
                        </p>
                        <p
                            class="text-2xl font-semibold text-gray-900 dark:text-white"
                        >
                            {{ subscription.plan_name }}
                        </p>
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Facturación
                            {{
                                subscription.billing_period_label.toLowerCase()
                            }}
                            <span v-if="subscription.trial_ends_at">
                                &middot; prueba hasta
                                {{ subscription.trial_ends_at }}</span
                            >
                        </p>
                    </div>
                    <Badge
                        :tone="subscriptionStatusTone(subscription.status)"
                        >{{ subscription.status_label }}</Badge
                    >
                </div>
            </Card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card title="Módulos activos">
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="module in entitlements?.modules ?? []"
                            :key="module"
                            tone="blue"
                            >{{ module }}</Badge
                        >
                        <span
                            v-if="!entitlements?.modules.length"
                            class="text-sm text-gray-400"
                            >Ninguno</span
                        >
                    </div>
                </Card>

                <Card title="Features">
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="feature in entitlements?.features ?? []"
                            :key="feature"
                            tone="gray"
                            >{{ feature }}</Badge
                        >
                        <span
                            v-if="!entitlements?.features.length"
                            class="text-sm text-gray-400"
                            >Ninguna</span
                        >
                    </div>
                </Card>

                <Card title="Límites del plan">
                    <ul class="space-y-1">
                        <li
                            v-for="(value, key) in entitlements?.limits ?? {}"
                            :key="key"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-gray-700 dark:text-gray-300">{{
                                key
                            }}</span>
                            <span
                                class="font-medium text-gray-900 dark:text-white"
                                >{{ value }}</span
                            >
                        </li>
                    </ul>
                </Card>
            </div>
        </template>

        <Card v-else>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Este tenant no tiene ninguna suscripción activa todavía.
            </p>
        </Card>
    </GeneralLayout>
</template>
