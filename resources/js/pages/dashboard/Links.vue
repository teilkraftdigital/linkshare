<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import { useToast } from '@/composables/useToast';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LinkCard from '@/components/LinkCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { index } from '@/routes/dashboard/links';

type Bucket = {
    id: number;
    name: string;
    color: string;
    is_inbox: boolean;
};

type Tag = {
    id: number;
    name: string;
    color: string;
};

type Link = {
    id: number;
    url: string;
    title: string;
    description: string | null;
    notes: string | null;
    bucket_id: number;
    bucket: Bucket;
    tags: Tag[];
};

type Props = {
    links: Link[];
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Links', href: index() }],
    },
});

const { toast } = useToast();

const createBucketId = ref<number>(0);
const createTagIds = ref<number[]>([]);

const editingLink = ref<Link | null>(null);
const editBucketId = ref<number>(0);
const editTagIds = ref<number[]>([]);

const deleteTarget = ref<Link | null>(null);

function initCreate(inboxBucketId: number) {
    if (createBucketId.value === 0) {
        createBucketId.value = inboxBucketId;
    }
}

function toggleCreateTag(id: number) {
    createTagIds.value = createTagIds.value.includes(id)
        ? createTagIds.value.filter((t) => t !== id)
        : [...createTagIds.value, id];
}

function startEdit(link: Link) {
    editingLink.value = link;
    editBucketId.value = link.bucket_id;
    editTagIds.value = link.tags.map((t) => t.id);
}

function cancelEdit() {
    editingLink.value = null;
}

function toggleEditTag(id: number) {
    editTagIds.value = editTagIds.value.includes(id)
        ? editTagIds.value.filter((t) => t !== id)
        : [...editTagIds.value, id];
}

function confirmDelete(link: Link) {
    deleteTarget.value = link;
}

function deleteLink() {
    if (!deleteTarget.value) {
        return;
    }
    router.delete(LinkController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Links" />

    <div class="flex flex-col gap-8 p-4">
        <Heading title="Links" description="Manage your saved links" />

        <!-- Create form -->
        <Form
            v-bind="LinkController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4"
            v-slot="{ errors, processing }"
            @vue:mounted="initCreate(inboxBucketId)"
            @success="
                () => {
                    createBucketId = inboxBucketId;
                    createTagIds = [];
                    toast('Link added', 'success');
                }
            "
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <Label for="link-url">URL</Label>
                    <Input
                        id="link-url"
                        name="url"
                        type="url"
                        placeholder="https://example.com"
                        autocomplete="off"
                    />
                    <InputError :message="errors.url" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-title">Title</Label>
                    <Input
                        id="link-title"
                        name="title"
                        placeholder="Link title"
                        autocomplete="off"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-description">Description</Label>
                    <Textarea
                        id="link-description"
                        name="description"
                        placeholder="Optional description"
                        class="resize-none"
                        rows="2"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-notes">Notes <span class="text-muted-foreground">(private)</span></Label>
                    <Textarea
                        id="link-notes"
                        name="notes"
                        placeholder="Private notes"
                        class="resize-none"
                        rows="2"
                    />
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <div class="flex flex-col gap-2">
                    <Label for="link-bucket">Bucket</Label>
                    <select
                        id="link-bucket"
                        name="bucket_id"
                        :value="createBucketId"
                        @change="createBucketId = Number(($event.target as HTMLSelectElement).value)"
                        class="border-input bg-background rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    >
                        <option v-for="bucket in buckets" :key="bucket.id" :value="bucket.id">
                            {{ bucket.name }}
                        </option>
                    </select>
                    <InputError :message="errors.bucket_id" />
                </div>

                <div v-if="tags.length > 0" class="flex flex-col gap-2">
                    <Label>Tags</Label>
                    <template v-for="tagId in createTagIds" :key="tagId">
                        <input type="hidden" name="tag_ids[]" :value="tagId" />
                    </template>
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`create-tag-${tag.id}`"
                                :model-value="createTagIds.includes(tag.id)"
                                @update:model-value="toggleCreateTag(tag.id)"
                            />
                            <Label :for="`create-tag-${tag.id}`">{{ tag.name }}</Label>
                        </div>
                    </div>
                    <InputError :message="errors['tag_ids']" />
                </div>
            </div>

            <Button type="submit" :disabled="processing" class="self-start">
                Add
            </Button>
        </Form>

        <!-- Link list -->
        <ul class="flex flex-col gap-2">
            <li
                v-for="link in links"
                :key="link.id"
            >
                <!-- Inline edit form -->
                <template v-if="editingLink?.id === link.id">
                    <Form
                        v-bind="LinkController.update.form(link)"
                        :options="{ preserveScroll: true }"
                        class="flex flex-col gap-4 rounded-lg border px-4 py-3"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-url-${link.id}`" class="sr-only">URL</Label>
                                <Input
                                    :id="`edit-url-${link.id}`"
                                    name="url"
                                    type="url"
                                    :default-value="link.url"
                                    placeholder="https://example.com"
                                />
                                <InputError :message="errors.url" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-title-${link.id}`" class="sr-only">Title</Label>
                                <Input
                                    :id="`edit-title-${link.id}`"
                                    name="title"
                                    :default-value="link.title"
                                    placeholder="Link title"
                                />
                                <InputError :message="errors.title" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-desc-${link.id}`" class="sr-only">Description</Label>
                                <Textarea
                                    :id="`edit-desc-${link.id}`"
                                    name="description"
                                    :default-value="link.description ?? ''"
                                    placeholder="Optional description"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-notes-${link.id}`" class="sr-only">Notes</Label>
                                <Textarea
                                    :id="`edit-notes-${link.id}`"
                                    name="notes"
                                    :default-value="link.notes ?? ''"
                                    placeholder="Private notes"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.notes" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-6">
                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-bucket-${link.id}`">Bucket</Label>
                                <select
                                    :id="`edit-bucket-${link.id}`"
                                    name="bucket_id"
                                    :value="editBucketId"
                                    @change="editBucketId = Number(($event.target as HTMLSelectElement).value)"
                                    class="border-input bg-background rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                                >
                                    <option v-for="bucket in buckets" :key="bucket.id" :value="bucket.id">
                                        {{ bucket.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.bucket_id" />
                            </div>

                            <div v-if="tags.length > 0" class="flex flex-col gap-2">
                                <Label>Tags</Label>
                                <template v-for="tagId in editTagIds" :key="tagId">
                                    <input type="hidden" name="tag_ids[]" :value="tagId" />
                                </template>
                                <div class="flex flex-wrap gap-3">
                                    <div
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        class="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            :id="`edit-tag-${link.id}-${tag.id}`"
                                            :model-value="editTagIds.includes(tag.id)"
                                            @update:model-value="toggleEditTag(tag.id)"
                                        />
                                        <Label :for="`edit-tag-${link.id}-${tag.id}`">{{ tag.name }}</Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button type="submit" size="sm" :disabled="processing">Save</Button>
                            <Button type="button" size="sm" variant="outline" @click="cancelEdit">
                                Cancel
                            </Button>
                        </div>
                    </Form>
                </template>

                <template v-else>
                    <div class="group relative">
                        <LinkCard
                            :title="link.title"
                            :url="link.url"
                            :description="link.description"
                            :bucket="link.bucket"
                            :tags="link.tags"
                        />
                        <div class="absolute right-3 top-3 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Edit ${link.title}`"
                                @click="startEdit(link)"
                            >
                                <Pencil class="size-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Delete ${link.title}`"
                                @click="confirmDelete(link)"
                            >
                                <Trash2 class="size-3.5 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </template>
            </li>
        </ul>

        <p v-if="links.length === 0" class="text-sm text-muted-foreground">
            No links yet. Add your first link above.
        </p>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Delete link?"
        :description="`Delete '${deleteTarget?.title}'? This action cannot be undone.`"
        confirm-label="Delete"
        @update:open="(val) => { if (!val) deleteTarget = null; }"
        @confirm="deleteLink"
    />
</template>
