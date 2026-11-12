<script setup lang="ts">
import type { Paginated } from '@/types/fleet';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

defineProps<{ meta: Paginated<unknown> }>();
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm sm:flex-row">
        <p class="text-muted-foreground">
            <template v-if="meta.total">Mostrando {{ meta.from }}–{{ meta.to }} de {{ meta.total }}</template>
            <template v-else>Sin resultados</template>
        </p>

        <nav v-if="meta.last_page > 1" class="flex flex-wrap items-center gap-1" aria-label="Paginación">
            <template v-for="(link, i) in meta.links" :key="i">
                <component
                    :is="link.url ? Link : 'span'"
                    :href="link.url ?? undefined"
                    preserve-scroll
                    preserve-state
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs transition-colors"
                    :class="[
                        link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-accent',
                        !link.url && 'pointer-events-none opacity-40',
                    ]"
                >
                    <ChevronLeft v-if="i === 0" class="size-4" />
                    <ChevronRight v-else-if="i === meta.links.length - 1" class="size-4" />
                    <span v-else>{{ link.label }}</span>
                </component>
            </template>
        </nav>
    </div>
</template>
