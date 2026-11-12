<script setup lang="ts">
import { tripTypeLabel } from '@/lib/fleet';
import type { TripType } from '@/types/fleet';
import { CalendarClock, Zap } from '@lucide/vue';

defineProps<{ type: TripType }>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
        <component :is="type === 'spontaneous' ? Zap : CalendarClock" class="size-3.5" />
        {{ tripTypeLabel[type] }}
    </span>
</template>
