<script setup lang="ts">
import { computed, watch } from 'vue';
import type { Bucket, Tag } from '@/types/dashboard';
import { Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    buckets: Bucket[];
    tags: Tag[];
};

const props = defineProps<Props>();

const filterSearch = defineModel<string>('search', {
    required: true,
});
const filterBucketId = defineModel<string>('bucket_id', {
    required: true,
});
const filterTagId = defineModel<string>('tag_id', {
    required: true,
});

const activeFilter = defineModel<boolean>('hasActiveFilters', {
    required: true,
});

const events = defineEmits<{
    clear: [];
}>();

const clearFilters = () => {
    filterSearch.value = '';
    filterBucketId.value = '';
    filterTagId.value = '';

    events('clear');
};

const hasActiveFilters = computed(
    () => !!filterSearch.value || !!filterBucketId.value || !!filterTagId.value,
);

watch(hasActiveFilters, (value) => {
    activeFilter.value = value;
});
</script>

<template>
    <div class="flex flex-wrap gap-3">
        <div class="relative min-w-48 flex-1">
            <Search
                class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
            />
            <Input
                v-model="filterSearch"
                placeholder="Suche in deinen Links…"
                class="pl-8"
            />
        </div>

        <select
            v-model="filterBucketId"
            class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
            aria-label="Filter nach Bucket"
        >
            <option value="">Alle Buckets</option>
            <option
                v-for="bucket in buckets"
                :key="bucket.id"
                :value="String(bucket.id)"
            >
                {{ bucket.name }}
            </option>
        </select>

        <select
            v-if="tags.length > 0"
            v-model="filterTagId"
            class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
            aria-label="Filter nach Tag"
        >
            <option value="">Alle Tags</option>
            <option v-for="tag in tags" :key="tag.id" :value="String(tag.id)">
                {{ tag.name }}
            </option>
        </select>

        <Button
            v-if="hasActiveFilters"
            variant="ghost"
            size="icon"
            aria-label="Filter zurücksetzen"
            @click="clearFilters"
        >
            <X class="size-4" />
        </Button>
    </div>
</template>
