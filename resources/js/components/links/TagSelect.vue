<script setup lang="ts">
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
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { COLOR_BG } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { Tag } from '@/types/dashboard';
const { t } = useI18n();

const props = defineProps<{
    tags: Tag[];
    modelValue: number[];
    name?: string;
    createError?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
    'tag-created': [name: string];
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
    emit(
        'update:modelValue',
        props.modelValue.filter((i) => i !== id),
    );
}

function handleEnter(e: KeyboardEvent) {
    if (filteredTags.value.length === 0 && searchTerm.value.trim()) {
        e.preventDefault();
        emit('tag-created', searchTerm.value.trim());
        searchTerm.value = '';
    }
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
                    'flex min-h-9 flex-wrap items-center gap-1 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-xs',
                    'focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
                )
            "
        >
            <span
                v-for="tag in selectedTags"
                :key="tag.id"
                class="flex items-center gap-1 rounded-full bg-muted py-0.5 pr-1 pl-2 text-xs font-medium"
            >
                <span
                    class="size-2 rounded-full"
                    :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                />
                {{ tag.name }}
                <button
                    type="button"
                    class="rounded-full p-0.5 text-muted-foreground hover:text-foreground"
                    :aria-label="t('tags.select.removeAriaLabel', { name: tag.name })"
                    @click.stop="remove(tag.id)"
                >
                    <X class="size-3" />
                </button>
            </span>

            <ComboboxInput
                v-model="searchTerm"
                class="min-w-24 flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                :placeholder="t('tags.select.placeholder')"
                @keydown.enter="handleEnter"
            />
        </ComboboxAnchor>

        <ComboboxContent
            class="z-50 mt-1 max-h-60 w-(--reka-combobox-trigger-width) overflow-y-auto rounded-md border border-input bg-popover shadow-md"
            position="popper"
            :avoid-collisions="true"
        >
            <ComboboxViewport class="p-1">
                <ComboboxEmpty
                    class="py-2 text-center text-sm text-muted-foreground"
                >
                    <template v-if="searchTerm.trim()">
                        {{
                            t('tags.select.createHint', {
                                term: searchTerm.trim(),
                            })
                        }}
                    </template>
                    <template v-else>{{ t('tags.select.noResults') }}</template>
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
                            class="size-3 shrink-0 opacity-60"
                            :aria-label="`${t(
                                'tags.select.publicTagAriaLabel',
                                {
                                    name: tag.name,
                                },
                            )}`"
                        />
                        <ComboboxItemIndicator class="ml-auto">
                            <span class="text-xs opacity-60">✓</span>
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>

    <p v-if="createError" class="text-sm text-red-600 dark:text-red-500">
        {{ createError }}
    </p>
</template>
