<script setup lang="ts">
import { computed, ref } from 'vue';
import { X } from 'lucide-vue-next';
import {
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxRoot,
    ComboboxViewport,
} from 'reka-ui';
import { cn } from '@/lib/utils';
import { COLOR_BG } from '@/lib/colors';

type Tag = {
    id: number;
    name: string;
    color: string;
};

const props = defineProps<{
    tags: Tag[];
    modelValue: number[];
    name?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const searchTerm = ref('');
const open = ref(false);

const selectedTags = computed(() =>
    props.tags.filter((t) => props.modelValue.includes(t.id)),
);

const filteredTags = computed(() => {
    const q = searchTerm.value.toLowerCase();
    return q
        ? props.tags.filter((t) => t.name.toLowerCase().includes(q))
        : props.tags;
});

function toggle(tag: Tag) {
    const next = props.modelValue.includes(tag.id)
        ? props.modelValue.filter((id) => id !== tag.id)
        : [...props.modelValue, tag.id];
    emit('update:modelValue', next);
    searchTerm.value = '';
}

function remove(id: number) {
    emit('update:modelValue', props.modelValue.filter((i) => i !== id));
}
</script>

<template>
    <!-- Hidden inputs for native form submission -->
    <template v-for="id in modelValue" :key="id">
        <input type="hidden" :name="`${name ?? 'tag_ids'}[]`" :value="id" />
    </template>

    <ComboboxRoot
        :open="open"
        :model-value="[]"
        :reset-search-term-on-blur="false"
        @update:open="open = $event"
    >
        <!-- Trigger: pill container + search input -->
        <div
            :class="
                cn(
                    'border-input bg-background flex min-h-9 flex-wrap items-center gap-1 rounded-md border px-2 py-1 text-sm shadow-xs',
                    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                )
            "
            @click="open = true"
        >
            <span
                v-for="tag in selectedTags"
                :key="tag.id"
                class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-1 text-xs font-medium"
                :class="COLOR_BG[tag.color] ? `bg-${tag.color}-100 text-${tag.color}-800 dark:bg-${tag.color}-900/30 dark:text-${tag.color}-300` : 'bg-muted text-muted-foreground'"
            >
                {{ tag.name }}
                <button
                    type="button"
                    class="rounded-full p-0.5 hover:bg-black/10 dark:hover:bg-white/20"
                    :aria-label="`Remove ${tag.name}`"
                    @click.stop="remove(tag.id)"
                >
                    <X class="size-3" />
                </button>
            </span>

            <ComboboxInput
                v-model="searchTerm"
                class="min-w-24 flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                placeholder="Search tags…"
                @focus="open = true"
            />
        </div>

        <ComboboxContent
            class="border-input bg-popover z-50 mt-1 max-h-60 overflow-y-auto rounded-md border shadow-md"
            :avoid-collisions="true"
            position="popper"
        >
            <ComboboxViewport class="p-1">
                <ComboboxEmpty class="py-2 text-center text-sm text-muted-foreground">
                    No tags found
                </ComboboxEmpty>

                <ComboboxGroup>
                    <ComboboxItem
                        v-for="tag in filteredTags"
                        :key="tag.id"
                        :value="tag"
                        class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent data-[highlighted]:bg-accent"
                        @select.prevent="toggle(tag)"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                        />
                        {{ tag.name }}
                        <ComboboxItemIndicator class="ml-auto">
                            <span v-if="modelValue.includes(tag.id)" class="text-xs text-muted-foreground">✓</span>
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
