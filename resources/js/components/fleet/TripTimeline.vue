<script setup lang="ts">
import { formatDateTime, formatKm, logEventMeta } from '@/lib/fleet';
import type { Bitacora } from '@/types/fleet';
import { MapPin } from '@lucide/vue';

defineProps<{ logs: Bitacora[] }>();
</script>

<template>
    <ol class="relative space-y-5">
        <li v-for="(log, i) in logs" :key="log.id" class="relative flex gap-3">
            <span v-if="i < logs.length - 1" class="absolute top-9 left-4 h-[calc(100%-1rem)] w-px bg-border" aria-hidden="true" />
            <div class="flex size-8 shrink-0 items-center justify-center rounded-full" :class="logEventMeta[log.event_type]?.class">
                <component :is="logEventMeta[log.event_type]?.icon" class="size-4" />
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex flex-wrap items-baseline justify-between gap-x-2">
                    <p class="text-sm font-medium">{{ logEventMeta[log.event_type]?.label ?? log.event_type }}</p>
                    <time class="text-xs text-muted-foreground">{{ formatDateTime(log.logged_at) }}</time>
                </div>
                <p v-if="log.description" class="mt-0.5 text-sm text-muted-foreground">{{ log.description }}</p>
                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    <span v-if="log.mileage_km !== null" class="font-mono">{{ formatKm(log.mileage_km) }}</span>
                    <span v-if="log.location" class="inline-flex items-center gap-1"><MapPin class="size-3" />{{ log.location }}</span>
                    <span v-if="log.user">por {{ log.user.name }}</span>
                </div>
            </div>
        </li>
    </ol>
</template>
