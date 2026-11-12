<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import type { Component } from 'vue';

withDefaults(defineProps<{ title: string; description?: string; icon?: Component }>(), { icon: () => Inbox });
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
            <component :is="icon" class="size-6" />
        </div>
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <slot />
    </div>
</template>
