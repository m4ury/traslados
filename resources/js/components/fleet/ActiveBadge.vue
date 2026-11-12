<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{ active: boolean }>();
</script>

<template>
    <Badge
        variant="outline"
        :class="
            active
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                : 'text-muted-foreground'
        "
    >
        <span class="mr-1 size-1.5 rounded-full" :class="active ? 'bg-emerald-500' : 'bg-zinc-400'" />
        {{ active ? 'Activo' : 'Inactivo' }}
    </Badge>
</template>
