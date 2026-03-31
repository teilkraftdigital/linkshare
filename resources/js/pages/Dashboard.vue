<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Globe, Link2, Tag as TagIcon } from 'lucide-vue-next';
import { index as dashboardRoute } from '@/routes/dashboard';
import type { DashboardTag, DashboardLink } from '@/types/dashboard';
import StatsTile from '@/components/dashboard/StatsTile.vue';
import { formatDate } from '@/lib/datetime';
import PublicTagsTile from '@/components/dashboard/PublicTagsTile.vue';
import AllTagsTile from '@/components/dashboard/AllTagsTile.vue';
import RecentLinksTile from '@/components/dashboard/RecentLinksTile.vue';

type Stat = { count: number; delta: number };

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
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <!-- Stat tiles -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatsTile
                eyebrow="Links"
                :stat="stats.links.count"
                :delta="stats.links.delta"
                use-delta
            >
                <template #icon>
                    <Link2 class="size-4" aria-hidden="true" />
                </template>
            </StatsTile>

            <StatsTile
                eyebrow="Tags"
                :stat="stats.tags.count"
                :delta="stats.tags.delta"
            >
                <template #icon>
                    <TagIcon class="size-4" aria-hidden="true" />
                </template>
            </StatsTile>

            <StatsTile
                eyebrow="Öffentliche Tags"
                :stat="stats.public_tags.count"
                :delta="stats.public_tags.delta"
            >
                <template #icon>
                    <Globe class="size-4" aria-hidden="true" />
                </template>
            </StatsTile>

            <StatsTile
                eyebrow="Zuletzt hinzugefügt"
                :stat="formatDate(stats.last_link_date)"
                delta="letzter Link"
            >
                <template #icon>
                    <Link2 class="size-4" aria-hidden="true" />
                </template>

                <template #delta> letzter Link </template>
            </StatsTile>
        </div>

        <!-- Middle: recent links + public tags -->
        <div class="grid gap-6 lg:grid-cols-5 lg:items-start">
            <!-- Recent links -->
            <RecentLinksTile :links="recent_links" class="col-span-4" />

            <!-- Public tags quick-access -->
            <PublicTagsTile :tags="tags" />
        </div>

        <!-- All tags -->
        <AllTagsTile :tags="tags" />
    </div>
</template>
