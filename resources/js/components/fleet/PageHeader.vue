<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description?: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div v-if="icon" class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <component :is="icon" class="size-5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
