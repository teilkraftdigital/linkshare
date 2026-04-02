<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { SortKey } from '@/types/dashboard';

const { t } = useI18n();

const sortKey = defineModel<SortKey>({
    default: 'updated_at',
});

const updateSortKey = (newKey: SortKey) => {
    sortKey.value = newKey;
};
</script>

<template>
    <section class="flex gap-1" :aria-label="$t('dashboard.sort.ariaLabel')">
        <button
            v-for="[key, labelKey] in [
                ['updated_at', 'dashboard.sort.recent'],
                ['name', 'dashboard.sort.alphabetical'],
                ['links_count', 'dashboard.sort.linksCount'],
            ] as [SortKey, string][]"
            :key="key"
            class="rounded px-2 py-0.5 text-xs transition-colors"
            :class="
                sortKey === key
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted'
            "
            @click="updateSortKey(key)"
        >
            {{ t(labelKey) }}
        </button>
    </section>
</template>
