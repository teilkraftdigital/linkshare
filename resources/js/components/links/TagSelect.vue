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
import type { Tag, TagCreatePayload } from '@/types/dashboard';

const { t } = useI18n();

const props = defineProps<{
    tags: Tag[];
    modelValue: number[];
    name?: string;
    createError?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
    'tag-created': [payload: TagCreatePayload];
}>();

const searchTerm = ref('');
const open = ref(false);

const selectedTags = computed(() =>
    props.tags.filter((t) => props.modelValue.includes(t.id)),
);

// Group tags: root tags as group headers, children underneath
const filteredGroups = computed(() => {
    const q = searchTerm.value.toLowerCase().trim();

    const childrenByParentId = new Map<number, Tag[]>();
    for (const tag of props.tags) {
        if (tag.parent_id) {
            const list = childrenByParentId.get(tag.parent_id) ?? [];
            list.push(tag);
            childrenByParentId.set(tag.parent_id, list);
        }
    }

    const rootTags = props.tags.filter((t) => !t.parent_id);

    return rootTags
        .map((parent) => {
            const allChildren = childrenByParentId.get(parent.id) ?? [];

            if (!q) {
                return { parent, children: allChildren };
            }

            const parentMatches = parent.name.toLowerCase().includes(q);
            const matchingChildren = allChildren.filter((c) =>
                c.name.toLowerCase().includes(q),
            );

            if (!parentMatches && matchingChildren.length === 0) {
                return null;
            }

            // If parent name matches show all children; otherwise only matching ones
            return {
                parent,
                children: parentMatches ? allChildren : matchingChildren,
            };
        })
        .filter((g): g is { parent: Tag; children: Tag[] } => g !== null);
});

const hasResults = computed(() => filteredGroups.value.length > 0);

// Bypass reka-ui's internal filtering — we handle it via filteredGroups
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
    const term = searchTerm.value.trim();
    if (!hasResults.value && term) {
        e.preventDefault();

        if (term.includes('/')) {
            const slashIndex = term.indexOf('/');
            const parentPart = term.slice(0, slashIndex).trim();
            const childPart = term.slice(slashIndex + 1).trim();

            if (!parentPart || !childPart) return;

            const existingParent = props.tags.find(
                (t) =>
                    !t.parent_id &&
                    t.name.toLowerCase() === parentPart.toLowerCase(),
            );

            emit('tag-created', {
                name: childPart,
                ...(existingParent
                    ? { parentId: existingParent.id }
                    : { parentName: parentPart }),
            });
        } else {
            emit('tag-created', { name: term });
        }

        searchTerm.value = '';
    }
}

// Hint text for the empty state
const createHintTerm = computed(() => {
    const term = searchTerm.value.trim();
    if (!term) return null;
    return term;
});
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
                    :aria-label="
                        t('tags.select.removeAriaLabel', { name: tag.name })
                    "
                    @click.stop="remove(tag.id)"
                >
                    <X class="size-3" aria-hidden="true" />
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
                    <template v-if="createHintTerm">
                        {{
                            t('tags.select.createHint', {
                                term: createHintTerm,
                            })
                        }}
                    </template>
                    <template v-else>{{ t('tags.select.noResults') }}</template>
                </ComboboxEmpty>

                <ComboboxGroup
                    v-for="group in filteredGroups"
                    :key="group.parent.id"
                >
                    <!-- Root tag — selectable as group header -->
                    <ComboboxItem
                        :value="group.parent"
                        class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm font-medium outline-none data-highlighted:bg-accent"
                        @select.prevent="toggle(group.parent)"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :class="
                                COLOR_BG[group.parent.color] ?? 'bg-gray-400'
                            "
                        />
                        {{ group.parent.name }}
                        <Globe
                            v-if="group.parent.is_public"
                            class="size-3 shrink-0 opacity-60"
                            :aria-label="
                                t('tags.select.publicTagAriaLabel', {
                                    name: group.parent.name,
                                })
                            "
                        />
                        <ComboboxItemIndicator class="ml-auto">
                            <span class="text-xs opacity-60">✓</span>
                        </ComboboxItemIndicator>
                    </ComboboxItem>

                    <!-- Child tags — indented -->
                    <ComboboxItem
                        v-for="child in group.children"
                        :key="child.id"
                        :value="child"
                        class="flex cursor-pointer items-center gap-2 rounded-sm py-1.5 pr-2 pl-7 text-sm outline-none data-highlighted:bg-accent"
                        @select.prevent="toggle(child)"
                    >
                        <span
                            class="size-2.5 shrink-0 rounded-full"
                            :class="COLOR_BG[child.color] ?? 'bg-gray-400'"
                        />
                        {{ child.name }}
                        <Globe
                            v-if="child.is_public"
                            class="size-3 shrink-0 opacity-60"
                            :aria-label="
                                t('tags.select.publicTagAriaLabel', {
                                    name: child.name,
                                })
                            "
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
