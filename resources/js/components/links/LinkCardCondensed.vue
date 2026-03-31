<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import type { Bucket, Tag } from '@/types/dashboard';
import PillBucket from '@/components/shared/PillBucket.vue';
import PillTag from '@/components/shared/PillTag.vue';
import LinkFavicon from './LinkFavicon.vue';

defineProps<{
    title: string;
    url: string;
    favicon_url?: string | null;
    bucket?: Bucket;
    tags?: Tag[];
}>();
</script>

<template>
    <div class="flex items-center gap-3 rounded-lg border px-3 py-2">
        <LinkFavicon :src="favicon_url" />
        <div class="min-w-0 flex-1">
            <a
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-sm font-medium hover:underline"
            >
                <span class="truncate">{{ title }}</span>
                <ExternalLink
                    aria-hidden="true"
                    class="size-3 shrink-0 text-muted-foreground"
                />
            </a>
        </div>
        <div v-if="bucket || tags?.length" class="flex shrink-0 flex-wrap items-center gap-1.5">
            <PillBucket v-if="bucket" :bucket="bucket" />
            <PillTag v-for="tag in tags" :key="tag.id" :tag="tag" />
        </div>
    </div>
</template>
