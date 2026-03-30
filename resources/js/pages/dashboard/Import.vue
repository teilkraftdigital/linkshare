<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { create as importRoute } from '@/routes/dashboard/import';
import ExportSection from '@/components/import/ExportSection.vue';
import HtmlImportSection from '@/components/import/HtmlImportSection.vue';
import JsonImportSection from '@/components/import/JsonImportSection.vue';
import BookmarkletCodeSection from '@/components/import/BookmarkletCodeSection.vue';
import Heading from '@/components/shared/Heading.vue';
import type { Bucket, Tag } from '@/types/dashboard';

const props = defineProps<{
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Import & Export', href: importRoute() }],
    },
});
</script>

<template>
    <Head title="Import & Export" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Import & Export"
            description="Exportiere oder importiere deine Links, Buckets und Tags."
        />

        <ExportSection :buckets="buckets" :tags="tags" />

        <hr class="border-border" />

        <HtmlImportSection
            :buckets="buckets"
            :inbox-bucket-id="inboxBucketId"
        />

        <hr class="border-border" />

        <JsonImportSection />

        <hr class="border-border" />

        <BookmarkletCodeSection />
    </div>
</template>
