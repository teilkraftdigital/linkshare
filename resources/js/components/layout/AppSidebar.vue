<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    Inbox,
    LayoutGrid,
    Link as LinkIcon,
    Tag,
    Upload,
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/layout/AppLogo.vue';
import NavFooter from '@/components/layout/NavFooter.vue';
import NavMain from '@/components/layout/NavMain.vue';
import NavUser from '@/components/layout/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as dashboard } from '@/routes/dashboard';
import { index as bucketsIndex } from '@/routes/dashboard/buckets';
import { create as importCreate } from '@/routes/dashboard/import';
import { index as linksIndex } from '@/routes/dashboard/links';
import { index as tagsIndex } from '@/routes/dashboard/tags';
import type { NavItem } from '@/types';

const { t } = useI18n();

const mainNavItems: NavItem[] = [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const libraryNavItems: NavItem[] = [
    {
        title: t('nav.links'),
        href: linksIndex(),
        icon: LinkIcon,
    },
    {
        title: t('nav.buckets'),
        href: bucketsIndex(),
        icon: Inbox,
    },
    {
        title: t('nav.tags'),
        href: tagsIndex(),
        icon: Tag,
    },
];

const toolNavItems: NavItem[] = [
    {
        title: t('nav.import'),
        href: importCreate(),
        icon: Upload,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="libraryNavItems" :label="$t('nav.library')" />
            <NavMain :items="toolNavItems" :label="$t('nav.tools')" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
