<script setup lang="ts">
const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header v-if="title" class="border-b">
            <div class="mx-auto max-w-2xl px-4 py-8">
                <h1 class="text-2xl font-bold">{{ title }}</h1>
                <p v-if="description" class="mt-1 text-muted-foreground">{{ description }}</p>
            </div>
        </header>
        <slot />
    </div>
</template>
