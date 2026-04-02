<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import LinkCardCondensed from '@/components/links/LinkCardCondensed.vue';
import type { DashboardLink } from '@/types/dashboard';
type Props = {
    links: DashboardLink[];
};

defineProps<Props>();

const { t } = useI18n();
</script>

<template>
    <section>
        <h2
            class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ t('dashboard.recentLinks.title') }}
        </h2>
        <ul>
            <li v-if="links.length > 0" class="flex flex-col gap-1.5">
                <LinkCardCondensed
                    v-for="link in links"
                    :key="link.id"
                    :title="link.title"
                    :url="link.url"
                    :favicon_url="link.favicon_url"
                    :bucket="link.bucket ?? undefined"
                    :tags="link.tags"
                />
            </li>
            <li v-else class="text-sm text-muted-foreground">
                {{ t('dashboard.recentLinks.empty') }}
            </li>
        </ul>
    </section>
</template>
