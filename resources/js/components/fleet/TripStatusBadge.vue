<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { tripStatusMeta } from '@/lib/fleet';
import type { EstadoTraslado } from '@/types/fleet';

defineProps<{ status: EstadoTraslado }>();
</script>

<template>
    <Badge variant="outline" :class="tripStatusMeta[status].class">
        {{ tripStatusMeta[status].label }}
    </Badge>
</template>
