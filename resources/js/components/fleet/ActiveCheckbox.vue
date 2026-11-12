<script setup lang="ts">
const model = defineModel<boolean>({ required: true });

withDefaults(defineProps<{ label?: string; hint?: string }>(), {
    label: 'Activo',
    hint: 'Los registros inactivos no aparecen para nuevas asignaciones.',
});
</script>

<template>
    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3">
        <input v-model="model" type="checkbox" class="mt-0.5 size-4 rounded border-input accent-primary" />
        <span class="grid gap-0.5">
            <span class="text-sm font-medium">{{ label }}</span>
            <span class="text-xs text-muted-foreground">{{ hint }}</span>
        </span>
    </label>
</template>
