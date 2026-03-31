<script setup lang="ts">
import { COLORS } from '@/lib/colors';

type Props = {
    modelValue: string;
};

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div
        class="flex flex-wrap justify-between gap-2 md:justify-items-start"
        role="group"
        aria-label="Color palette"
    >
        <label
            v-for="color in COLORS"
            :key="color.name"
            :aria-label="color.name"
            :title="color.name"
            class="size-6 cursor-pointer rounded-full ring-offset-2 transition-all focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :class="[
                color.color,
                modelValue === color.name ? 'ring-2 ring-foreground' : '',
            ]"
        >
            <input
                name="color"
                type="radio"
                class="sr-only"
                :value="color.name"
                @change="emit('update:modelValue', color.name)"
            />
        </label>
    </div>
</template>
