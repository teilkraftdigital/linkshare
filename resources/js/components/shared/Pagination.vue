<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginator } from '@/types/dashboard';

defineProps<{
    items: Paginator<any>;
}>();
</script>

<template>
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm text-muted-foreground">
            {{ $t('pagination.summary', { from: items.from, to: items.to, total: items.total }) }}
        </p>
        <div class="flex gap-1">
            <Link
                v-for="link in items.links"
                :key="link.label"
                :href="link.url ?? '#'"
                :aria-disabled="!link.active"
                :aria-current="link.active ? 'page' : undefined"
                :class="[
                    'inline-flex h-8 min-w-8 items-center justify-center rounded-md text-sm text-nowrap first:px-2 last:px-2',
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent',
                    !link.url && 'pointer-events-none opacity-40',
                ]"
                preserve-state
            >
                <span v-html="link.label"></span>
            </Link>
        </div>
    </div>
</template>
