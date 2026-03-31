<script setup lang="ts">
import type { SortKey } from '@/types/dashboard';

const sortKey = defineModel<SortKey>({
    default: 'updated_at',
});

const updateSortKey = (newKey: SortKey) => {
    sortKey.value = newKey;
};
</script>

<template>
    <section class="flex gap-1" aria-label="Sortiereinträge">
        <button
            v-for="opt in [
                ['updated_at', 'Zuletzt'],
                ['name', 'A–Z'],
                ['links_count', 'Links'],
            ] as [SortKey, string][]"
            :key="opt[0]"
            class="rounded px-2 py-0.5 text-xs transition-colors"
            :class="
                sortKey === opt[0]
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted'
            "
            @click="updateSortKey(opt[0])"
        >
            {{ opt[1] }}
        </button>
    </section>
</template>
