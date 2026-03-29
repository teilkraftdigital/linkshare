<script setup lang="ts">
import { computed, ref } from 'vue';
import { Globe, X } from 'lucide-vue-next';
import {
    ComboboxAnchor,
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
import type { Tag } from '@/types/dashboard';

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
    const q = searchTerm.value.toLowerCase().trim();
    return q
        ? props.tags.filter((t) => t.name.toLowerCase().includes(q))
        : props.tags;
});

// Bypass reka-ui's internal filtering — we handle it via filteredTags
function filterFunction() {
    return true;
}

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
        v-model:open="open"
        :model-value="[]"
        :filter-function="filterFunction"
        :reset-search-term-on-blur="false"
        :open-on-focus="true"
        :open-on-click="true"
    >
        <ComboboxAnchor
            :class="
                cn(
                    'border-input bg-background flex min-h-9 flex-wrap items-center gap-1 rounded-md border px-2 py-1 text-sm shadow-xs',
                    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                )
            "
        >
            <span
                v-for="tag in selectedTags"
                :key="tag.id"
                class="bg-muted flex items-center gap-1 rounded-full py-0.5 pl-2 pr-1 text-xs font-medium"
            >
                <span
                    class="size-2 rounded-full"
                    :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                />
                {{ tag.name }}
                <button
                    type="button"
                    class="text-muted-foreground rounded-full p-0.5 hover:text-foreground"
                    :aria-label="`Remove ${tag.name}`"
                    @click.stop="remove(tag.id)"
                >
                    <X class="size-3" />
                </button>
            </span>

            <ComboboxInput
                v-model="searchTerm"
                class="min-w-24 flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                placeholder="Tags suchen…"
            />
        </ComboboxAnchor>

        <ComboboxContent
            class="border-input bg-popover z-50 mt-1 w-(--reka-combobox-trigger-width) max-h-60 overflow-y-auto rounded-md border shadow-md"
            position="popper"
            :avoid-collisions="true"
        >
            <ComboboxViewport class="p-1">
                <ComboboxEmpty class="py-2 text-center text-sm text-muted-foreground">
                    Keine Tags gefunden
                </ComboboxEmpty>

                <ComboboxGroup>
                    <ComboboxItem
                        v-for="tag in filteredTags"
                        :key="tag.id"
                        :value="tag"
                        class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none data-highlighted:bg-accent"
                        @select.prevent="toggle(tag)"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                        />
                        {{ tag.name }}
                        <Globe
                            v-if="tag.is_public"
                            class="size-3 opacity-60 shrink-0"
                            :aria-label="`${tag.name} is public`"
                        />
                        <ComboboxItemIndicator class="ml-auto">
                            <span class="text-xs opacity-60">✓</span>
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
