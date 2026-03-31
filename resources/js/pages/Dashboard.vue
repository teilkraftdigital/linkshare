<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Check, Copy, ExternalLink, Globe, Link2, Tag as TagIcon } from 'lucide-vue-next';
import * as LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import * as TagsRoute from '@/routes/tags';
import * as TagExportController from '@/actions/App/Http/Controllers/TagExportController';
import { index as dashboardRoute } from '@/routes/dashboard';
import LinkCardCondensed from '@/components/links/LinkCardCondensed.vue';
import { COLOR_BG, COLOR_BG_OPACITY, HAS_COLOR } from '@/lib/colors';
import type { Bucket, Tag } from '@/types/dashboard';

const TAG_SEARCH_THRESHOLD = 8;

type Stat = { count: number; delta: number };

type DashboardLink = {
    id: number;
    url: string;
    title: string;
    favicon_url: string | null;
    bucket: Bucket | null;
    tags: Tag[];
};

type DashboardTag = Tag & {
    links_count: number;
    updated_at: string;
};

type SortKey = 'updated_at' | 'name' | 'links_count';

const props = defineProps<{
    stats: {
        links: Stat;
        tags: Stat;
        public_tags: Stat;
        last_link_date: string | null;
    };
    recent_links: DashboardLink[];
    tags: DashboardTag[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboardRoute() }],
    },
});

// --- Copied URL feedback ---
const copiedTagId = ref<number | null>(null);

function copyTagUrl(tag: DashboardTag): void {
    const url = new URL(TagsRoute.show.url(tag.slug ?? ''), window.location.origin).href;
    navigator.clipboard.writeText(url).then(() => {
        copiedTagId.value = tag.id;
        setTimeout(() => {
            copiedTagId.value = null;
        }, 1500);
    });
}

function openTagLinks(tag: DashboardTag): void {
    router.visit(LinkController.index.url({ query: { tag_id: String(tag.id) } }));
}

// --- Tag sorting & filtering ---
const publicTagSort = ref<SortKey>('updated_at');
const allTagSort = ref<SortKey>('updated_at');
const publicTagSearch = ref('');
const allTagSearch = ref('');

const publicTags = computed(() =>
    props.tags.filter((t) => t.is_public),
);

const allTags = computed(() => props.tags);

function sortedAndFiltered(tags: DashboardTag[], sort: SortKey, search: string): DashboardTag[] {
    let result = tags;

    if (search.trim()) {
        const q = search.trim().toLowerCase();
        result = result.filter((t) => t.name.toLowerCase().includes(q));
    }

    return [...result].sort((a, b) => {
        if (sort === 'name') {
            return a.name.localeCompare(b.name);
        }
        if (sort === 'links_count') {
            return b.links_count - a.links_count;
        }
        // updated_at desc
        return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime();
    });
}

const sortedPublicTags = computed(() =>
    sortedAndFiltered(publicTags.value, publicTagSort.value, publicTagSearch.value),
);

const sortedAllTags = computed(() =>
    sortedAndFiltered(allTags.value, allTagSort.value, allTagSearch.value),
);

// --- Stat helpers ---
function formatDate(iso: string | null): string {
    if (!iso) { return '—'; }
    return new Date(iso).toLocaleDateString('de-DE', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <!-- Stat tiles -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div class="mb-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <Link2 class="size-4" aria-hidden="true" />
                    Links
                </div>
                <div class="text-3xl font-bold">{{ stats.links.count }}</div>
                <div class="mt-1 text-xs text-muted-foreground">
                    +{{ stats.links.delta }} letzte 7 Tage
                </div>
            </div>

            <div class="rounded-xl border bg-card p-4">
                <div class="mb-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <TagIcon class="size-4" aria-hidden="true" />
                    Tags
                </div>
                <div class="text-3xl font-bold">{{ stats.tags.count }}</div>
                <div class="mt-1 text-xs text-muted-foreground">
                    +{{ stats.tags.delta }} letzte 7 Tage
                </div>
            </div>

            <div class="rounded-xl border bg-card p-4">
                <div class="mb-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <Globe class="size-4" aria-hidden="true" />
                    Öffentliche Tags
                </div>
                <div class="text-3xl font-bold">{{ stats.public_tags.count }}</div>
                <div class="mt-1 text-xs text-muted-foreground">
                    +{{ stats.public_tags.delta }} letzte 7 Tage
                </div>
            </div>

            <div class="rounded-xl border bg-card p-4">
                <div class="mb-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <Link2 class="size-4" aria-hidden="true" />
                    Zuletzt hinzugefügt
                </div>
                <div class="text-3xl font-bold tabular-nums">
                    {{ formatDate(stats.last_link_date) }}
                </div>
                <div class="mt-1 text-xs text-muted-foreground">letzter Link</div>
            </div>
        </div>

        <!-- Middle: recent links + public tags -->
        <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
            <!-- Recent links -->
            <section>
                <h2 class="mb-3 text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                    Zuletzt hinzugefügt
                </h2>
                <div v-if="recent_links.length > 0" class="flex flex-col gap-1.5">
                    <LinkCardCondensed
                        v-for="link in recent_links"
                        :key="link.id"
                        :title="link.title"
                        :url="link.url"
                        :favicon_url="link.favicon_url"
                        :bucket="link.bucket ?? undefined"
                        :tags="link.tags"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">Noch keine Links vorhanden.</p>
            </section>

            <!-- Public tags quick-access -->
            <section class="lg:w-72 xl:w-80">
                <h2 class="mb-3 text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                    Öffentliche Tags
                </h2>

                <div v-if="publicTags.length === 0" class="text-sm text-muted-foreground">
                    Noch keine öffentlichen Tags.
                </div>

                <template v-else>
                    <!-- Search -->
                    <input
                        v-if="publicTags.length >= TAG_SEARCH_THRESHOLD"
                        v-model="publicTagSearch"
                        type="search"
                        placeholder="Suchen…"
                        class="mb-2 w-full rounded-md border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-ring"
                    />

                    <!-- Sort -->
                    <div class="mb-3 flex gap-1">
                        <button
                            v-for="opt in ([['updated_at', 'Zuletzt'], ['name', 'A–Z'], ['links_count', 'Links']] as [SortKey, string][])"
                            :key="opt[0]"
                            class="rounded px-2 py-0.5 text-xs transition-colors"
                            :class="publicTagSort === opt[0] ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                            @click="publicTagSort = opt[0]"
                        >
                            {{ opt[1] }}
                        </button>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div
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
                            <span class="shrink-0 text-xs text-muted-foreground">{{ tag.links_count }}</span>
                            <a
                                :href="TagsRoute.show.url(tag.slug ?? '')"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="shrink-0 text-muted-foreground hover:text-foreground"
                                :aria-label="`${tag.name} öffnen`"
                            >
                                <ExternalLink class="size-3.5" aria-hidden="true" />
                            </a>
                            <button
                                class="shrink-0 text-muted-foreground hover:text-foreground"
                                :aria-label="`URL von ${tag.name} kopieren`"
                                @click="copyTagUrl(tag)"
                            >
                                <Check v-if="copiedTagId === tag.id" class="size-3.5 text-green-500" aria-hidden="true" />
                                <Copy v-else class="size-3.5" aria-hidden="true" />
                            </button>
                        </div>
                        <p v-if="sortedPublicTags.length === 0" class="text-sm text-muted-foreground">
                            Keine Ergebnisse.
                        </p>
                    </div>
                </template>
            </section>
        </div>

        <!-- All tags -->
        <section>
            <h2 class="mb-3 text-sm font-semibold text-muted-foreground uppercase tracking-wide">
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
                    placeholder="Suchen…"
                    class="mb-2 w-64 rounded-md border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-ring"
                />

                <!-- Sort -->
                <div class="mb-3 flex gap-1">
                    <button
                        v-for="opt in ([['updated_at', 'Zuletzt'], ['name', 'A–Z'], ['links_count', 'Links']] as [SortKey, string][])"
                        :key="opt[0]"
                        class="rounded px-2 py-0.5 text-xs transition-colors"
                        :class="allTagSort === opt[0] ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                        @click="allTagSort = opt[0]"
                    >
                        {{ opt[1] }}
                    </button>
                </div>

                <div class="grid gap-1.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    <button
                        v-for="tag in sortedAllTags"
                        :key="tag.id"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                        @click="openTagLinks(tag)"
                    >
                        <span
                            class="size-2 shrink-0 rounded-full"
                            :class="HAS_COLOR(tag.color) ? COLOR_BG[tag.color] : 'bg-gray-400'"
                        />
                        <span
                            class="min-w-0 flex-1 truncate font-medium"
                            :class="HAS_COLOR(tag.color) ? COLOR_BG_OPACITY[tag.color] : ''"
                        >
                            {{ tag.name }}
                        </span>
                        <span class="shrink-0 text-xs text-muted-foreground">{{ tag.links_count }}</span>
                        <Globe v-if="tag.is_public" class="size-3 shrink-0 text-muted-foreground/60" aria-hidden="true" />
                    </button>
                    <p v-if="sortedAllTags.length === 0" class="col-span-full text-sm text-muted-foreground">
                        Keine Ergebnisse.
                    </p>
                </div>
            </template>
        </section>
    </div>
</template>
