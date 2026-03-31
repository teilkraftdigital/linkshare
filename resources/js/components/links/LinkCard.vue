<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import type { Bucket, Tag } from '@/types/dashboard';
import LinkFavicon from './LinkFavicon.vue';
import PillBucket from '../shared/PillBucket.vue';
import PillTag from '../shared/PillTag.vue';

defineProps<{
    title: string;
    url: string;
    description?: string | null;
    favicon_url?: string | null;
    bucket?: Bucket;
    tags?: Tag[];
}>();
</script>

<template>
    <div class="flex flex-col gap-2 rounded-lg border px-4 py-3">
        <div class="flex items-start gap-3">
            <LinkFavicon :src="favicon_url" />
            <div class="min-w-0 flex-1">
                <a
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 font-medium hover:underline"
                >
                    {{ title }}
                    <ExternalLink
                        aria-hidden="true"
                        class="size-3.5 shrink-0 text-muted-foreground"
                    />
                </a>
                <p
                    v-if="description"
                    class="mt-0.5 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </div>

        <div v-if="bucket || tags?.length" class="flex flex-wrap items-center gap-2 pl-7">
            <PillBucket v-if="bucket" :bucket="bucket" />
            <PillTag v-for="tag in tags" :key="tag.id" :tag="tag" />
        </div>
    </div>
</template>
