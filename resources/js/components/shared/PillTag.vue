<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import { COLOR_BG, COLOR_BG_OPACITY, HAS_COLOR } from '@/lib/colors';
import type { Tag } from '@/types/dashboard';

type Props = {
    tag: Tag;
};

defineProps<Props>();
</script>

<template>
    <span
        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
        :class="
            HAS_COLOR(tag.color)
                ? `${COLOR_BG_OPACITY[tag.color]}`
                : 'bg-gray-100 dark:bg-gray-800'
        "
    >
        <span
            class="size-1.5 rounded-full"
            :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
        />
        {{ tag.name }}
        <Globe
            v-if="tag.is_public"
            class="size-3 opacity-60"
            :aria-label="`${tag.name} ist öffentlich`"
        />
    </span>
</template>
