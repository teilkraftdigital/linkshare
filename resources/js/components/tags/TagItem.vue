<script setup lang="ts">
import { ChevronDown, Link as LinkIcon, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PublicTagController from '@/actions/App/Http/Controllers/TagController';
import TagInlineEditForm from '@/components/tags/TagInlineEditForm.vue';
import TagNormalActions from '@/components/tags/TagNormalActions.vue';
import TagTrashAction from '@/components/tags/TagTrashAction.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { COLOR_BG } from '@/lib/colors';
import type { Tag } from '@/types/dashboard';

const { t } = useI18n();
const { toast } = useToast();

type Props = {
    tag: Tag;
    showTrashed: boolean;
    /** When true this item is rendered as a child row (no expand toggle) */
    isChild?: boolean;
    /** Slug of the parent tag, used to build the display slug for child tags */
    parentSlug?: string;
};

const props = defineProps<Props>();

defineEmits<{
    restore: [tag: Tag];
    'confirm-delete': [tag: Tag];
    'force-delete': [tag: Tag];
    'add-child': [parentTag: Tag];
}>();

const editingTag = ref<Tag | null>(null);
const expanded = ref(false);

const children = props.tag.children ?? [];

function startEdit(tag: Tag) {
    editingTag.value = { ...tag };
}

function cancelEdit() {
    editingTag.value = null;
}

async function copyUrl(slug: string) {
    const url = `${window.location.origin}${PublicTagController.show.url(slug)}`;
    await navigator.clipboard.writeText(url);
    toast(t('tags.actions.urlCopied'), 'success');
}

function displaySlug(tag: Tag) {
    return props.parentSlug ? `${props.parentSlug}#${tag.slug}` : tag.slug;
}
</script>

<template>
    <li
        class="flex flex-col rounded-lg border"
        :class="[
            showTrashed ? 'opacity-60' : '',
            isChild ? 'rounded-none border-0 border-t' : '',
        ]"
    >
        <!-- Inline edit form -->
        <TagInlineEditForm
            v-if="!showTrashed && editingTag?.id === tag.id"
            :tag="editingTag!"
            :is-child="isChild"
            v-model:color="editingTag!.color"
            v-model:is_public="editingTag!.is_public"
            v-model:name="editingTag!.name"
            v-model:description="editingTag!.description"
            v-model:parent_id="editingTag!.parent_id"
            @cancel="cancelEdit"
        />

        <template v-else>
            <div
                class="flex items-center gap-3 px-4"
                :class="isChild ? 'py-1.5 pl-8' : 'py-3'"
            >
                <!-- Expand toggle (only for root tags with children) -->
                <button
                    v-if="!isChild && children.length > 0"
                    type="button"
                    class="shrink-0 text-muted-foreground transition-transform"
                    :class="expanded ? 'rotate-180' : ''"
                    :aria-label="
                        t('tags.children.toggleAriaLabel', { name: tag.name })
                    "
                    @click="expanded = !expanded"
                >
                    <ChevronDown class="size-4" aria-hidden="true" />
                </button>
                <!-- Spacer when no toggle (root without children) -->
                <span v-else-if="!isChild" class="size-4 shrink-0" />

                <span
                    class="size-4 shrink-0 rounded-full"
                    :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                />

                <span
                    class="flex-1 font-medium"
                    :class="[
                        showTrashed ? 'line-through' : '',
                        isChild ? 'text-sm font-normal' : '',
                    ]"
                >
                    {{ tag.name }}
                </span>

                <!-- Children counter (root tags only) -->
                <span
                    v-if="!isChild && children.length > 0"
                    class="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                >
                    {{ t('tags.children.count', children.length) }}
                </span>

                <!-- Link counter -->
                <span
                    class="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    :aria-label="`${tag.links_count} links`"
                >
                    <LinkIcon class="size-3 shrink-0" aria-hidden="true" />
                    {{ tag.links_count }}
                </span>

                <!-- Slug -->
                <code
                    class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                >
                    /{{ displaySlug(tag) }}
                </code>

                <!-- Normal actions -->
                <TagNormalActions
                    v-if="!showTrashed"
                    :tag="tag"
                    @edit="startEdit"
                    @delete="$emit('confirm-delete', tag)"
                />

                <!-- Trash actions -->
                <TagTrashAction
                    v-else
                    :tag="tag"
                    @restore="$emit('restore', tag)"
                    @force-delete="$emit('force-delete', tag)"
                />
            </div>

            <!-- Description -->
            <div
                v-if="!showTrashed && tag.description"
                class="px-4 pb-2 text-sm text-muted-foreground"
                :class="isChild ? 'pl-8' : 'pl-15'"
            >
                {{ tag.description }}
            </div>

            <!-- Public URL row -->
            <div
                v-if="!showTrashed && tag.is_public && !isChild"
                class="flex items-center gap-2 px-4 pb-3 pl-15"
            >
                <span
                    class="rounded bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
                >
                    {{ t('tags.item.publicBadge') }}
                </span>
                <code class="flex-1 truncate text-xs text-muted-foreground">
                    {{
                        `${$page.props.appUrl}${PublicTagController.show.url(tag.slug ?? '')}`
                    }}
                </code>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7 shrink-0"
                    :aria-label="
                        t('tags.actions.copyUrlAriaLabel', { name: tag.name })
                    "
                    @click="copyUrl(tag.slug ?? '')"
                >
                    <Plus class="size-3.5" />
                </Button>
            </div>
        </template>

        <!-- Children (expanded) -->
        <ul
            v-if="!isChild && expanded && children.length > 0"
            class="flex flex-col"
        >
            <TagItem
                v-for="child in children"
                :key="child.id"
                :tag="child"
                :is-child="true"
                :parent-slug="tag.slug"
                :show-trashed="showTrashed"
                @confirm-delete="$emit('confirm-delete', $event)"
                @restore="$emit('restore', $event)"
                @force-delete="$emit('force-delete', $event)"
            />

            <!-- Add child button -->
            <li v-if="!showTrashed" class="border-t">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 px-8 py-2 text-sm text-muted-foreground hover:text-foreground"
                    @click="$emit('add-child', tag)"
                >
                    <Plus class="size-3.5" />
                    {{ t('tags.children.addButton') }}
                </button>
            </li>
        </ul>

        <!-- Add child button when collapsed but has children (or no children yet, root only) -->
        <div
            v-if="!isChild && !showTrashed && children.length === 0"
            class="border-t"
        >
            <button
                type="button"
                class="flex w-full items-center gap-2 px-15 py-2 text-sm text-muted-foreground hover:text-foreground"
                @click="$emit('add-child', tag)"
            >
                <Plus class="size-3.5" />
                {{ t('tags.children.addButton') }}
            </button>
        </div>
    </li>
</template>
