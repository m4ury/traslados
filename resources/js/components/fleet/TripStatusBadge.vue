<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { tripStatusMeta } from '@/lib/fleet';
import type { TripStatus } from '@/types/fleet';

defineProps<{ status: TripStatus }>();
</script>

<template>
    <Badge variant="outline" :class="tripStatusMeta[status].class">
        {{ tripStatusMeta[status].label }}
    </Badge>
</template>
