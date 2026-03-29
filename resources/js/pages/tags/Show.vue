<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import LinkCard from '@/components/LinkCard.vue';

type Link = {
    id: number;
    url: string;
    title: string;
    description: string | null;
};

type Tag = {
    name: string;
    description: string | null;
    slug: string;
};

const props = defineProps<{
    tag: Tag;
    links: Link[];
}>();

setLayoutProps({
    title: props.tag.name,
    description: props.tag.description ?? '',
});
</script>

<template>
    <Head :title="tag.name">
        <meta property="og:title" :content="`${tag.name} – Linkshare`" />
        <meta
            v-if="tag.description"
            property="og:description"
            :content="tag.description"
        />
    </Head>

    <main class="mx-auto max-w-2xl px-4 py-12">
        <ul v-if="links.length > 0" class="flex flex-col gap-2">
            <li v-for="link in links" :key="link.id">
                <LinkCard
                    :title="link.title"
                    :url="link.url"
                    :description="link.description"
                />
            </li>
        </ul>

        <p v-else class="text-sm text-muted-foreground">
            No links in this collection yet.
        </p>
    </main>
</template>
