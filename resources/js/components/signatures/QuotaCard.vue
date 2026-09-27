<script setup lang="ts">
import { computed } from 'vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import { money } from '@/lib/signatures';
import type { SignatureAccount } from '@/lib/signatures';

/**
 * The tenant's remaining signature quota: available credit (credit mode)
 * or units per product (prepaid mode).
 */
const props = defineProps<{
    account: SignatureAccount | null;
}>();

const usedPercent = computed(() => {
    const limit = Number(props.account?.credit_limit ?? 0);

    return limit > 0
        ? Math.min(
              100,
              Math.round((Number(props.account?.credit_used) / limit) * 100),
          )
        : 0;
});
const prepaidProducts = computed(
    () =>
        props.account?.products.filter(
            (product) => product.available_units > 0,
        ) ?? [],
);
</script>

<template>
    <Card title="Cupo de firmas">
        <p v-if="!account" class="text-sm text-ink-600 dark:text-ink-400">
            Tu empresa aún no está habilitada para vender firmas. Contacta al
            administrador de la plataforma.
        </p>

        <template v-else>
            <div class="flex items-center gap-2">
                <Badge :tone="account.is_credit ? 'blue' : 'green'">
                    {{ account.affiliation_mode_label }}
                </Badge>
                <Badge v-if="!account.is_active" tone="red">Suspendido</Badge>
            </div>

            <template v-if="account.is_credit">
                <p
                    class="mt-3 text-3xl font-extrabold tracking-tight text-ink-950 tabular-nums dark:text-white"
                >
                    {{ money(account.credit_available) }}
                    <span class="text-base font-semibold text-ink-500"
                        >disponibles</span
                    >
                </p>
                <div
                    class="mt-3 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800"
                >
                    <div
                        :class="[
                            'h-full rounded-full',
                            usedPercent >= 90
                                ? 'bg-accent-500'
                                : 'bg-primary-500',
                        ]"
                        :style="{ width: `${usedPercent}%` }"
                    />
                </div>
                <p class="mt-2 text-xs text-ink-500">
                    Usado {{ money(account.credit_used) }} de
                    {{ money(account.credit_limit) }}. Cada firma enviada se
                    carga a su precio de crédito.
                </p>
            </template>

            <template v-else>
                <ul
                    v-if="prepaidProducts.length"
                    class="mt-3 divide-y divide-ink-100 dark:divide-ink-800"
                >
                    <li
                        v-for="product in prepaidProducts"
                        :key="product.id"
                        class="flex items-center justify-between py-2 text-sm"
                    >
                        <span class="text-ink-700 dark:text-ink-300">{{
                            product.name
                        }}</span>
                        <span
                            class="font-bold text-ink-950 tabular-nums dark:text-white"
                            >{{ product.available_units }}</span
                        >
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-ink-600 dark:text-ink-400">
                    No tienes firmas disponibles. Adquiere un paquete para
                    seguir vendiendo.
                </p>
            </template>
        </template>
    </Card>
</template>
