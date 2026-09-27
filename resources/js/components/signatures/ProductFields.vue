<script setup lang="ts">
import { ui } from '@/lib/ui';

/**
 * Fields of a signature product, for use inside an Inertia <Form>.
 */
defineProps<{
    validities: Record<string, string>;
    containers: Record<string, string>;
    errors: Record<string, string>;
    product?: {
        name: string;
        validity: string;
        container: string;
        credit_unit_price: string;
        suggested_retail_price: string | null;
    };
}>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" :class="ui.label">Nombre comercial</label>
            <input
                id="name"
                name="name"
                required
                :value="product?.name ?? ''"
                placeholder="Firma 1 año"
                :class="ui.input"
            />
            <p v-if="errors.name" :class="ui.error">{{ errors.name }}</p>
        </div>
        <div>
            <label for="validity" :class="ui.label">Vigencia</label>
            <select id="validity" name="validity" :class="ui.input">
                <option
                    v-for="(label, value) in validities"
                    :key="value"
                    :value="value"
                    :selected="value === (product?.validity ?? 'OneYear')"
                >
                    {{ label }}
                </option>
            </select>
        </div>
        <div>
            <label for="container" :class="ui.label">Entrega</label>
            <select id="container" name="container" :class="ui.input">
                <option
                    v-for="(label, value) in containers"
                    :key="value"
                    :value="value"
                    :selected="value === product?.container"
                >
                    {{ label }}
                </option>
            </select>
            <p v-if="errors.container" :class="ui.error">
                {{ errors.container }}
            </p>
        </div>
        <div>
            <label for="credit_unit_price" :class="ui.label"
                >Precio por firma a crédito (USD)</label
            >
            <input
                id="credit_unit_price"
                name="credit_unit_price"
                type="number"
                step="0.01"
                min="0"
                required
                :value="product?.credit_unit_price ?? ''"
                :class="ui.input"
            />
            <p v-if="errors.credit_unit_price" :class="ui.error">
                {{ errors.credit_unit_price }}
            </p>
        </div>
        <div>
            <label for="suggested_retail_price" :class="ui.label"
                >PVP sugerido (USD)</label
            >
            <input
                id="suggested_retail_price"
                name="suggested_retail_price"
                type="number"
                step="0.01"
                min="0"
                :value="product?.suggested_retail_price ?? ''"
                :class="ui.input"
            />
        </div>
    </div>
</template>
