<script setup lang="ts">
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { es } from 'date-fns/locale';
import { onMounted, ref, useAttrs } from 'vue';

/**
 * App-wide wrapper around @vuepic/vue-datepicker: Spanish locale, weeks
 * starting on Monday and Spanish action buttons. Every other prop, event
 * and slot is passed straight through; theming lives in app.css.
 *
 * The picker renders an internal Teleport that does not hydrate cleanly
 * under SSR, so it mounts on the client only; the server renders the
 * `trigger` slot (or an empty field of the same size) in its place.
 */
defineOptions({ inheritAttrs: false });

const attrs = useAttrs();
const isMounted = ref(false);

onMounted(() => (isMounted.value = true));
</script>

<template>
    <VueDatePicker
        v-if="isMounted"
        v-bind="$attrs"
        :locale="es"
        :week-start="1"
        :action-row="{
            selectBtnLabel: 'Aplicar',
            cancelBtnLabel: 'Cancelar',
            nowBtnLabel: 'Ahora',
        }"
    >
        <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps ?? {}" />
        </template>
    </VueDatePicker>
    <div v-else :class="attrs.class">
        <slot name="trigger">
            <div class="form-control h-10 w-full" aria-hidden="true" />
        </slot>
    </div>
</template>
