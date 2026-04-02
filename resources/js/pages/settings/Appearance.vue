<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppearanceTabs from '@/components/settings/AppearanceTabs.vue';
import Heading from '@/components/shared/Heading.vue';
import { edit } from '@/routes/appearance';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: t('settings.appearance.breadcrumb'),
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="$t('settings.appearance.pageTitle')" />

    <h1 class="sr-only">{{ $t('settings.appearance.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="$t('settings.appearance.title')"
            :description="$t('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
