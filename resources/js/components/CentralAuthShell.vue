<script setup lang="ts">
import AuthShell from '@/components/AuthShell.vue';
import { brand } from '@/lib/brand';

defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <AuthShell
        :title="title"
        :description="description"
        :context="brand.consoleName"
    >
        <slot />
    </AuthShell>
</template>
