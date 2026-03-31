<script lang="ts" setup>
import type { Bucket } from '@/types/dashboard';
import { COLOR_BG } from '@/lib/colors';
type Props = {
    bucket: Bucket;
};

defineProps<Props>();
</script>

<template>
    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
        <span
            class="size-2.5 rounded-full"
            :class="COLOR_BG[bucket.color] ?? 'bg-gray-400'"
        />
        {{ bucket.name }}
    </span>
</template>
