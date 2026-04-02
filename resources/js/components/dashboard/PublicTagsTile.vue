<script setup lang="ts">
import { Check, Copy, ExternalLink } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import EntrySortButtons from '@/components/dashboard/EntrySortButtons.vue';
import { useTagSortAndFilter } from '@/composables/useTagSortAndFilter';
import { COLOR_BG } from '@/lib/colors';
import * as TagsRoute from '@/routes/tags';
import { TAG_SEARCH_THRESHOLD } from '@/types/dashboard';
import type { DashboardTag } from '@/types/dashboard';

type Props = {
    tags: DashboardTag[];
};

const props = defineProps<Props>();
const publicTags = computed(() => props.tags.filter((t) => t.is_public));
const { t } = useI18n();

const {
    sortKey: publicTagSort,
    search: publicTagSearch,
    sorted: sortedPublicTags,
} = useTagSortAndFilter(publicTags);

// --- Copied URL feedback ---
const copiedTagId = ref<number | null>(null);

function copyTagUrl(tag: DashboardTag): void {
    const url = new URL(
        TagsRoute.show.url(tag.slug ?? ''),
        window.location.origin,
    ).href;
    navigator.clipboard.writeText(url).then(() => {
        copiedTagId.value = tag.id;
        setTimeout(() => {
            copiedTagId.value = null;
        }, 1500);
    });
}
</script>

<template>
    <section>
        <h2
            class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ t('dashboard.publicTags.title') }}
        </h2>

        <div
            v-if="publicTags.length === 0"
            class="text-sm text-muted-foreground"
        >
            {{ t('dashboard.publicTags.empty') }}
        </div>

        <template v-else>
            <!-- Search -->
            <input
                v-if="publicTags.length >= TAG_SEARCH_THRESHOLD"
                v-model="publicTagSearch"
                :aria-label="t('dashboard.publicTags.searchAriaLabel')"
                type="search"
                :placeholder="t('dashboard.publicTags.searchPlaceholder')"
                class="mb-2 w-full rounded-md border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-ring"
            />

            <!-- Sort -->
            <EntrySortButtons v-model="publicTagSort" class="mb-3" />

            <ul class="flex flex-col gap-1.5">
                <li
                    v-for="tag in sortedPublicTags"
                    :key="tag.id"
                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                >
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                    />
                    <a
                        :href="TagsRoute.show.url(tag.slug ?? '')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="min-w-0 flex-1 truncate font-medium hover:underline"
                    >
                        {{ tag.name }}
                    </a>
                    <span class="shrink-0 text-xs text-muted-foreground">
                        {{ tag.links_count }}
                    </span>
                    <a
                        :href="TagsRoute.show.url(tag.slug ?? '')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="shrink-0 text-muted-foreground hover:text-foreground"
                        :aria-label="
                            t('tags.actions.openAriaLabel', { name: tag.name })
                        "
                    >
                        <ExternalLink class="size-3.5" aria-hidden="true" />
                    </a>
                    <button
                        class="shrink-0 text-muted-foreground hover:text-foreground"
                        :aria-label="
                            t('tags.actions.copyUrlAriaLabel', {
                                name: tag.name,
                            })
                        "
                        @click="copyTagUrl(tag)"
                    >
                        <Check
                            v-if="copiedTagId === tag.id"
                            class="size-3.5 text-green-500"
                            aria-hidden="true"
                        />
                        <Copy v-else class="size-3.5" aria-hidden="true" />
                    </button>
                </li>
                <li
                    v-if="sortedPublicTags.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('dashboard.publicTags.noResults') }}
                </li>
            </ul>
        </template>
    </section>
</template>
