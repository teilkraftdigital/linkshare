<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    src?: string | null;
}>();

const error = ref(false);

watch(
    () => props.src,
    () => {
        error.value = false;
    },
);
</script>

<template>
    <img
        v-if="src && !error"
        :src="src"
        class="mt-1 size-4 shrink-0 rounded-sm object-contain"
        alt=""
        @error="error = true"
    />
    <Globe
        v-else
        aria-hidden="true"
        class="mt-1 size-4 shrink-0 text-muted-foreground/40"
    />
</template>
