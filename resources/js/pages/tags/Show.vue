<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import TagController from '@/actions/App/Http/Controllers/TagController';
import LinkCard from '@/components/links/LinkCard.vue';
import { Button } from '@/components/ui/button';
import type { Link, Tag } from '@/types/dashboard';

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
        <meta
            property="og:title"
            :content="`${tag.name} – ${links.length} link${links.length !== 1 ? 's' : ''} – ${$page.props.name}`"
        />
        <meta
            v-if="tag.description"
            property="og:description"
            :content="tag.description"
        />
    </Head>

    <main class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">{{ tag.name }}</h1>
            <Button variant="outline" size="sm" as-child>
                <a :href="TagController.exportMethod.url(tag.slug ?? '')">
                    <Download class="mr-2 size-4" />
                    Als Bookmarks exportieren
                </a>
            </Button>
        </div>

        <ul v-if="links.length > 0" class="flex flex-col gap-2">
            <li v-for="link in links" :key="link.id">
                <LinkCard
                    :title="link.title"
                    :url="link.url"
                    :description="link.description"
                    :favicon_url="link.favicon_url"
                />
            </li>
        </ul>

        <p v-else class="text-sm text-muted-foreground">
            Es wurden noch keine Links mit diesem Tag versehen.
        </p>
    </main>
</template>
