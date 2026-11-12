<script setup lang="ts">
import type { Trip, TripStatus } from '@/types/fleet';
import { Check, CircleCheck, ClipboardList, Navigation, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ trip: Pick<Trip, 'status' | 'trip_type'> }>();

const steps = computed(() => {
    const all: { key: TripStatus; label: string; icon: typeof Check }[] = [
        { key: 'requested', label: 'Solicitado', icon: ClipboardList },
        { key: 'authorized', label: 'Autorizado', icon: ShieldCheck },
        { key: 'in_progress', label: 'En curso', icon: Navigation },
        { key: 'completed', label: 'Finalizado', icon: CircleCheck },
    ];
    return props.trip.trip_type === 'spontaneous' ? all.slice(2) : all;
});

const currentIndex = computed(() => steps.value.findIndex((s) => s.key === props.trip.status));
</script>

<template>
    <ol class="flex w-full items-center">
        <li
            v-for="(step, i) in steps"
            :key="step.key"
            class="flex items-center"
            :class="i < steps.length - 1 && 'flex-1'"
        >
            <div class="flex flex-col items-center gap-1.5">
                <div
                    class="flex size-9 items-center justify-center rounded-full border-2 transition-colors"
                    :class="
                        i < currentIndex || trip.status === 'completed'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : i === currentIndex
                              ? 'border-primary bg-primary/10 text-primary'
                              : 'border-border text-muted-foreground'
                    "
                >
                    <Check v-if="i < currentIndex || trip.status === 'completed'" class="size-4" />
                    <component :is="step.icon" v-else class="size-4" />
                </div>
                <span class="text-xs font-medium" :class="i <= currentIndex ? 'text-foreground' : 'text-muted-foreground'">
                    {{ step.label }}
                </span>
            </div>
            <div
                v-if="i < steps.length - 1"
                class="mx-2 mb-5 h-0.5 flex-1 rounded-full"
                :class="i < currentIndex ? 'bg-primary' : 'bg-border'"
            />
        </li>
    </ol>
</template>
