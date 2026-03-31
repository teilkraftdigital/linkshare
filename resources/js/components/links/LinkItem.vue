<script setup lang="ts">
import { ref } from 'vue';

import type { Bucket, Link, Tag } from '@/types/dashboard';
import LinkCard from './LinkCard.vue';
import LinkInlineEditForm from './LinkInlineEditForm.vue';
import LinkNormalAction from './LinkNormalAction.vue';
import LinkTrashAction from './LinkTrashAction.vue';

type Props = {
    link: Link;
    buckets: Bucket[];
    tags: Tag[];
    showTrashed: boolean;
    refetchingLinkId: number | null;
};

defineProps<Props>();

const event = defineEmits<{
    delete: [link: Link];
    restore: [link: Link];
    'force-delete': [link: Link];
    'refetch-meta': [link: Link];
}>();

const editingLink = ref<Link | null>(null);
const editBucketId = ref<number>(0);
const editTagIds = ref<number[]>([]);

function startEdit(link: Link) {
    editingLink.value = link;
    editBucketId.value = link.bucket_id;
    editTagIds.value = link.tags.map((t) => t.id);
}

function cancelEdit() {
    editingLink.value = null;
}

function confirmDelete(link: Link) {
    event('delete', link);
}

function restoreLink(link: Link) {
    event('restore', link);
}

function confirmForceDelete(link: Link) {
    event('force-delete', link);
}

function refetchMeta(link: Link) {
    event('refetch-meta', link);
}
</script>

<template>
    <li>
        <!-- Inline edit form (only in normal view) -->
        <LinkInlineEditForm
            v-if="!showTrashed && editingLink?.id === link.id"
            v-model:link="editingLink"
            v-model:bucket_id="editBucketId"
            v-model:tag_ids="editTagIds"
            :buckets="buckets"
            :tags="tags"
            @cancel="cancelEdit"
        />

        <template v-else>
            <div
                class="group relative"
                :class="showTrashed ? 'opacity-60' : ''"
            >
                <LinkCard
                    :title="link.title"
                    :url="link.url"
                    :description="link.description"
                    :favicon_url="link.favicon_url"
                    :bucket="link.bucket"
                    :tags="link.tags"
                />
                <div
                    class="absolute top-3 right-3 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                >
                    <!-- Normal actions -->
                    <LinkNormalAction
                        v-if="!showTrashed"
                        :link="link"
                        :refetchingLinkId="refetchingLinkId"
                        @edit="startEdit"
                        @delete="confirmDelete"
                        @refetchMeta="refetchMeta"
                    />

                    <!-- Trash actions -->
                    <LinkTrashAction
                        v-else
                        :link="link"
                        @restore="restoreLink"
                        @force-delete="confirmForceDelete"
                    />
                </div>
            </div>
        </template>
    </li>
</template>
