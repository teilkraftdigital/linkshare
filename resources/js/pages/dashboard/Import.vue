<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BookmarkletCodeSection from '@/components/import/BookmarkletCodeSection.vue';
import ExportSection from '@/components/import/ExportSection.vue';
import HtmlImportSection from '@/components/import/HtmlImportSection.vue';
import JsonImportSection from '@/components/import/JsonImportSection.vue';
import Heading from '@/components/shared/Heading.vue';
import { i18n } from '@/i18n';
import { create as importRoute } from '@/routes/dashboard/import';
import type { Bucket, Tag } from '@/types/dashboard';

const { t } = useI18n();

defineProps<{
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: i18n.global.t('import.breadcrumb'), href: importRoute() }],
    },
});
</script>

<template>
    <Head :title="t('import.pageTitle')" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            :title="t('import.pageTitle')"
            :description="t('import.description')"
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
