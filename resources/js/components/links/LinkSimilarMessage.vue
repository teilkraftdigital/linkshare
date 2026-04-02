<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    existis: boolean | undefined;
    similar: boolean | undefined;
}>();
</script>

<template>
    <p v-if="existis" class="text-xs text-amber-600 dark:text-amber-400">
        {{ t('links.duplicateExists') }}
    </p>
    <p v-else-if="similar" class="text-xs text-amber-600 dark:text-amber-400">
        {{ t('links.duplicateSimilar') }}
    </p>
</template>
