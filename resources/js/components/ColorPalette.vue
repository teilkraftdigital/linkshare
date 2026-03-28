<script setup lang="ts">
type Props = {
    modelValue: string;
};

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const colors: { name: string; bg: string }[] = [
    { name: 'gray', bg: 'bg-gray-400' },
    { name: 'red', bg: 'bg-red-500' },
    { name: 'orange', bg: 'bg-orange-500' },
    { name: 'amber', bg: 'bg-amber-500' },
    { name: 'yellow', bg: 'bg-yellow-400' },
    { name: 'lime', bg: 'bg-lime-500' },
    { name: 'green', bg: 'bg-green-500' },
    { name: 'teal', bg: 'bg-teal-500' },
    { name: 'cyan', bg: 'bg-cyan-500' },
    { name: 'blue', bg: 'bg-blue-500' },
    { name: 'indigo', bg: 'bg-indigo-500' },
    { name: 'violet', bg: 'bg-violet-500' },
];
</script>

<template>
    <div class="flex flex-wrap gap-2" role="group" aria-label="Color palette">
        <button
            v-for="color in colors"
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
