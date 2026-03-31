<script setup lang="ts">
defineProps<{
    existis: boolean | undefined;
    similar: boolean | undefined;
}>();
</script>

<template>
    <p v-if="existis" class="text-xs text-amber-600 dark:text-amber-400">
        Dieser Link ist bereits vorhanden.
    </p>
    <p v-else-if="similar" class="text-xs text-amber-600 dark:text-amber-400">
        Ein ähnlicher Link ist bereits vorhanden.
    </p>
</template>
