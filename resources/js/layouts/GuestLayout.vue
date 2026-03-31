<script setup lang="ts">
import { Download } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type Props = {
    title?: string;
    description?: string;
    exportUrl?: string;
};

const { title = '', description = '', exportUrl } = defineProps<Props>();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header v-if="title" class="border-b">
            <div
                class="items-start-safe mx-auto flex max-w-2xl justify-between px-4 py-8"
            >
                <div class="">
                    <h1 class="text-2xl font-bold">{{ title }}</h1>
                    <p v-if="description" class="mt-1 text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <Button v-if="exportUrl" variant="outline" size="sm" as-child>
                    <a :href="exportUrl">
                        <Download class="mr-2 size-4" aria-hidden="true" />
                        Als Bookmarks exportieren
                    </a>
                </Button>
            </div>
        </header>
        <slot />
    </div>
</template>
