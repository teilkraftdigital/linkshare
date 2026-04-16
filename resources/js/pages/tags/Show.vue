<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import TagExportController from '@/actions/App/Http/Controllers/TagExportController';
import LinkCard from '@/components/links/LinkCard.vue';
import type { Link, Tag } from '@/types/dashboard';

type ChildTag = Pick<Tag, 'id' | 'name' | 'slug' | 'description'> & {
    links: Link[];
};

type TocEntry = { id: string; name: string; count: number };

const props = defineProps<{
    tag: Tag;
    links: Link[];
    children: ChildTag[];
}>();

const { t } = useI18n();

const totalCount = computed(
    () =>
        props.links.length +
        props.children.reduce((sum, c) => sum + c.links.length, 0),
);

const tocEntries = computed((): TocEntry[] => {
    const entries: TocEntry[] = [];
    if (props.links.length > 0) {
        entries.push({ id: 'allgemein', name: t('tags.showParentLinks'), count: props.links.length });
    }
    props.children.forEach((child) => {
        if (child.slug) {
            entries.push({ id: child.slug, name: child.name, count: child.links.length });
        }
    });
    return entries;
});

const showToc = computed(() => tocEntries.value.length >= 2);

const activeId = ref<string | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    if (!showToc.value) return;

    const sections = document.querySelectorAll('section[id]');
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeId.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-10% 0px -80% 0px' },
    );
    sections.forEach((s) => observer!.observe(s));
});

onUnmounted(() => observer?.disconnect());

setLayoutProps({
    title: props.tag.name,
    description: props.tag.description ?? '',
    exportUrl: TagExportController.url(props.tag.slug ?? ''),
});
</script>

<template>
    <Head :title="tag.name">
        <meta
            property="og:title"
            :content="`${tag.name} – ${totalCount} link${totalCount !== 1 ? 's' : ''} – ${$page.props.name}`"
        />
        <meta
            v-if="tag.description"
            property="og:description"
            :content="tag.description"
        />
    </Head>

    <div class="mx-auto max-w-5xl py-12">
        <div class="lg:grid lg:grid-cols-12">
            <!-- Content -->
            <main class="space-y-10 px-4 lg:col-span-6 lg:col-start-2 lg:px-0">
                <p v-if="totalCount === 0" class="text-sm text-muted-foreground">
                    {{ t('tags.showEmpty') }}
                </p>

                <template v-else>
                    <!-- Parent tag links -->
                    <section v-if="links.length > 0" id="allgemein">
                        <h2
                            class="sticky top-0 z-10 mb-3 bg-background/90 py-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase backdrop-blur-sm"
                        >
                            {{ t('tags.showParentLinks') }}
                        </h2>
                        <ul class="flex flex-col gap-2">
                            <li v-for="link in links" :key="link.id">
                                <LinkCard
                                    :title="link.title"
                                    :url="link.url"
                                    :description="link.description"
                                    :favicon_url="link.favicon_url"
                                />
                            </li>
                        </ul>
                    </section>

                    <!-- Child tag sections -->
                    <section
                        v-for="child in children"
                        :id="child.slug"
                        :key="child.id"
                    >
                        <h2
                            class="sticky top-0 z-10 mb-3 bg-background/90 py-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase backdrop-blur-sm"
                        >
                            {{ child.name }}
                        </h2>
                        <p
                            v-if="child.description"
                            class="mb-3 text-sm text-muted-foreground"
                        >
                            {{ child.description }}
                        </p>
                        <ul class="flex flex-col gap-2">
                            <li v-for="link in child.links" :key="link.id">
                                <LinkCard
                                    :title="link.title"
                                    :url="link.url"
                                    :description="link.description"
                                    :favicon_url="link.favicon_url"
                                />
                            </li>
                        </ul>
                    </section>
                </template>
            </main>

            <!-- Sticky ToC sidebar -->
            <aside
                v-if="showToc"
                class="hidden lg:col-span-4 lg:col-start-8 lg:block"
            >
                <nav
                    class="sticky top-8 pl-6"
                    aria-label="Inhaltsverzeichnis"
                >
                    <ul class="flex flex-col gap-1">
                        <li v-for="entry in tocEntries" :key="entry.id">
                            <a
                                :href="`#${entry.id}`"
                                :aria-current="activeId === entry.id ? 'true' : undefined"
                                class="flex items-center justify-between gap-2 rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                                :class="activeId === entry.id ? 'bg-muted font-medium text-foreground' : ''"
                            >
                                <span class="truncate">{{ entry.name }}</span>
                                <span
                                    class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs tabular-nums"
                                >
                                    {{ entry.count }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</template>
