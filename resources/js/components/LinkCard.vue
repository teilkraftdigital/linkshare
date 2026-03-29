<script setup lang="ts">
import { ExternalLink, Globe } from 'lucide-vue-next';
import { COLOR_BG } from '@/lib/colors';
import type { Bucket, Tag } from '@/types/dashboard';

type Props = {
    title: string;
    url: string;
    description?: string | null;
    bucket?: Bucket;
    tags?: Tag[];
};

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-2 rounded-lg border px-4 py-3">
        <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
                <a
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 font-medium hover:underline"
                >
                    {{ title }}
                    <ExternalLink class="size-3.5 shrink-0 text-muted-foreground" />
                </a>
                <p
                    v-if="description"
                    class="mt-0.5 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </div>

        <div v-if="bucket || tags?.length" class="flex flex-wrap items-center gap-2">
            <span v-if="bucket" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span
                    class="size-2.5 rounded-full"
                    :class="COLOR_BG[bucket.color] ?? 'bg-gray-400'"
                />
                {{ bucket.name }}
            </span>

            <span
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                :class="COLOR_BG[tag.color] ? `${COLOR_BG[tag.color]}/20` : 'bg-gray-100 dark:bg-gray-800'"
            >
                <span
                    class="size-1.5 rounded-full"
                    :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                />
                {{ tag.name }}
                <Globe
                    v-if="tag.is_public"
                    class="size-3 opacity-60"
                    :aria-label="`${tag.name} is public`"
                />
            </span>
        </div>
    </div>
</template>
