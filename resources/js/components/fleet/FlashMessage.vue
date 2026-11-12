<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const page = usePage();

const flash = computed(() => (page.props.flash ?? {}) as { success?: string | null; error?: string | null });
const message = computed(() => flash.value.error ?? flash.value.success ?? null);
const isError = computed(() => !!flash.value.error);

const visible = ref(true);
watch(message, () => (visible.value = true));
</script>

<template>
    <div
        v-if="message && visible"
        class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm"
        :class="
            isError
                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
        "
        role="status"
    >
        <component :is="isError ? CircleAlert : CircleCheck" class="mt-0.5 size-4 shrink-0" />
        <p class="flex-1">{{ message }}</p>
        <button type="button" class="opacity-70 hover:opacity-100" aria-label="Cerrar" @click="visible = false">
            <X class="size-4" />
        </button>
    </div>
</template>
