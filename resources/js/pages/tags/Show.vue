<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import TagExportController from '@/actions/App/Http/Controllers/TagExportController';
import LinkCard from '@/components/links/LinkCard.vue';
import type { Link, Tag } from '@/types/dashboard';

type ChildTag = Pick<Tag, 'id' | 'name' | 'slug' | 'description'> & {
    links: Link[];
};

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

    <main class="mx-auto max-w-2xl space-y-10 px-4 py-12">
        <p v-if="totalCount === 0" class="text-sm text-muted-foreground">
            {{ t('tags.showEmpty') }}
        </p>

        <template v-else>
            <!-- Parent tag links -->
            <section v-if="links.length > 0" id="allgemein">
                <h2
                    class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
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
                    class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
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
</template>
