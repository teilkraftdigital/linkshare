<script setup lang="ts">
import { BUCKET_COLORS } from '@/lib/bucketColors';

type Props = {
    modelValue: string;
};

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div class="flex flex-wrap gap-2" role="group" aria-label="Color palette">
        <button
            v-for="color in BUCKET_COLORS"
            :key="color.name"
            type="button"
            :aria-label="color.name"
            :aria-pressed="modelValue === color.name"
            :title="color.name"
            class="size-7 rounded-full ring-offset-2 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="[
                color.bg,
                modelValue === color.name ? 'ring-2 ring-foreground' : '',
            ]"
            @click="emit('update:modelValue', color.name)"
        />
    </div>
</template>
