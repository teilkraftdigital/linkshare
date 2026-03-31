<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';
import { computed } from 'vue';
import * as LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import EntrySortButtons from '@/components/dashboard/EntrySortButtons.vue';
import { useTagSortAndFilter } from '@/composables/useTagSortAndFilter';
import { COLOR_BG, COLOR_BG_OPACITY, HAS_COLOR } from '@/lib/colors';
import type { DashboardTag } from '@/types/dashboard';
import { TAG_SEARCH_THRESHOLD } from '@/types/dashboard';

type Props = {
    tags: DashboardTag[];
};

const props = defineProps<Props>();
const allTags = computed(() => props.tags);

const {
    sortKey: allTagSort,
    search: allTagSearch,
    sorted: sortedAllTags,
} = useTagSortAndFilter(allTags);

function openTagLinks(tag: DashboardTag): void {
    router.visit(
        LinkController.index.url({ query: { tag_id: String(tag.id) } }),
    );
}
</script>

<template>
    <section>
        <h2
            class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Alle Tags
        </h2>

        <div v-if="allTags.length === 0" class="text-sm text-muted-foreground">
            Noch keine Tags vorhanden.
        </div>

        <template v-else>
            <!-- Search -->
            <input
                v-if="allTags.length >= TAG_SEARCH_THRESHOLD"
                v-model="allTagSearch"
                type="search"
                aria-label="Alle Tags suchen"
                placeholder="Suchen…"
                class="mb-2 w-64 rounded-md border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-ring"
            />

            <!-- Sort -->
            <EntrySortButtons v-model="allTagSort" class="mb-3" />

            <ul class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="tag in sortedAllTags" :key="tag.id">
                    <button
                        class="flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                        @click="openTagLinks(tag)"
                    >
                        <span
                            class="size-2 shrink-0 rounded-full"
                            :class="
                                HAS_COLOR(tag.color)
                                    ? COLOR_BG[tag.color]
                                    : 'bg-gray-400'
                            "
                        />
                        <span
                            class="min-w-0 flex-1 truncate font-medium"
                            :class="
                                HAS_COLOR(tag.color)
                                    ? COLOR_BG_OPACITY[tag.color]
                                    : ''
                            "
                        >
                            {{ tag.name }}
                        </span>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{ tag.links_count }}
                        </span>
                        <Globe
                            v-if="tag.is_public"
                            class="size-3 shrink-0 text-muted-foreground/60"
                            aria-hidden="true"
                        />
                    </button>
                </li>
                <li
                    v-if="sortedAllTags.length === 0"
                    class="col-span-full text-sm text-muted-foreground"
                >
                    Keine Ergebnisse.
                </li>
            </ul>
        </template>
    </section>
</template>
