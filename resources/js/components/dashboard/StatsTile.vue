<script setup lang="ts">
type Props = {
    eyebrow: string;
    stat: string | number;
    delta?: string | number;
};

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="mb-1 flex items-center gap-2 text-sm text-muted-foreground">
            <slot name="icon" />
            {{ eyebrow }}
        </div>
        <div class="text-3xl font-bold">{{ stat }}</div>
        <div class="mt-1 text-xs text-muted-foreground">
            <slot name="delta"> {{ $t('dashboard.stats.deltaLabel', { delta }) }} </slot>
        </div>
    </div>
</template>
