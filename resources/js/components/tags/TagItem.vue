<script setup lang="ts">
import { ref } from 'vue';
import { Copy } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Link as LinkIcon } from 'lucide-vue-next';
import { COLOR_BG } from '@/lib/colors';
import type { Tag } from '@/types/dashboard';
import { useToast } from '@/composables/useToast';
import PublicTagController from '@/actions/App/Http/Controllers/TagController';
import TagInlineEditForm from '@/components/tags/TagInlineEditForm.vue';
import TagNormalActions from '@/components/tags/TagNormalActions.vue';
import TagTrashAction from '@/components/tags/TagTrashAction.vue';

const { toast } = useToast();

type Props = {
    tag: Tag;
    showTrashed: boolean;
};
const props = defineProps<Props>();

const editingTag = ref<Tag | null>(null);

function startEdit(tag: Tag) {
    editingTag.value = tag;
}

function cancelEdit() {
    editingTag.value = null;
}

const events = defineEmits<{
    restore: [tag: Tag];
    'confirm-delete': [tag: Tag];
    'force-delete': [tag: Tag];
    delete: [tag: Tag];
}>();

function restoreTag(tag: Tag) {
    events('restore', tag);
}

function confirmForceDelete(tag: Tag) {
    events('force-delete', tag);
}

function confirmDelete(tag: Tag) {
    events('confirm-delete', tag);
}

async function copyUrl(slug: string) {
    const url = `${window.location.origin}${PublicTagController.show.url(slug)}`;
    await navigator.clipboard.writeText(url);

    toast('URL copied', 'success');
}
</script>

<template>
    <li
        class="flex flex-col gap-3 rounded-lg border px-4 py-3"
        :class="showTrashed ? 'opacity-60' : ''"
    >
        <!-- Inline edit form (only in normal view) -->
        <TagInlineEditForm
            v-if="!showTrashed && editingTag?.id === tag.id"
            :tag="editingTag"
            v-model:color="tag.color"
            v-model:is_public="tag.is_public"
            v-model:name="tag.name"
            v-model:description="tag.description"
            @cancel="cancelEdit"
        />

        <template v-else>
            <div class="flex items-center gap-3">
                <span
                    class="size-4 shrink-0 rounded-full"
                    :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                />

                <span
                    class="flex-1 font-medium"
                    :class="showTrashed ? 'line-through' : ''"
                >
                    {{ tag.name }}
                </span>

                <span
                    class="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    :aria-label="`${tag.links_count} links`"
                >
                    <LinkIcon class="size-3 shrink-0" />
                    {{ tag.links_count }}
                </span>

                <code
                    class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                >
                    /{{ tag.slug }}
                </code>

                <!-- Normal actions -->
                <TagNormalActions
                    v-if="!showTrashed"
                    :tag="tag"
                    @edit="startEdit"
                    @delete="confirmDelete"
                />

                <!-- Trash actions -->
                <TagTrashAction
                    v-else
                    :tag="tag"
                    @restore="restoreTag"
                    @force-delete="confirmForceDelete"
                />
            </div>

            <div
                v-if="!showTrashed && tag.description"
                class="text-sm text-muted-foreground"
            >
                {{ tag.description }}
            </div>

            <div
                v-if="!showTrashed && tag.is_public"
                class="flex items-center gap-2"
            >
                <span
                    class="rounded bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
                >
                    Öffentlich
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
                    :aria-label="`Copy URL for ${tag.name}`"
                    @click="copyUrl(tag.slug ?? '')"
                >
                    <Copy class="size-3.5" />
                </Button>
            </div>
        </template>
    </li>
</template>
