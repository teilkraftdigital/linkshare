<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    Copy,
    Link as LinkIcon,
    Pencil,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import PublicTagController from '@/actions/App/Http/Controllers/TagController';
import { useToast } from '@/composables/useToast';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { COLOR_BG } from '@/lib/colors';
import { index } from '@/routes/dashboard/tags';
import type { Tag } from '@/types/dashboard';

type Props = {
    tags: Tag[];
    showTrashed: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tags', href: index() }],
    },
});

const { toast } = useToast();

const createColor = ref<string>('gray');
const createIsPublic = ref<boolean>(false);

const editingTag = ref<Tag | null>(null);
const editColor = ref<string>('gray');
const editIsPublic = ref<boolean>(false);

const deleteTarget = ref<Tag | null>(null);
const forceDeleteTarget = ref<Tag | null>(null);

function toggleTrashed() {
    router.get(index(), props.showTrashed ? {} : { trashed: '1' }, {
        preserveState: false,
    });
}

function startEdit(tag: Tag) {
    editingTag.value = tag;
    editColor.value = tag.color;
    editIsPublic.value = tag.is_public;
}

function cancelEdit() {
    editingTag.value = null;
}

function confirmDelete(tag: Tag) {
    deleteTarget.value = tag;
}

function deleteTag() {
    if (!deleteTarget.value) {
        return;
    }
    router.delete(TagController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
            toast('Tag gelöscht', 'success');
        },
    });
}

function restoreTag(tag: Tag) {
    router.post(
        TagController.restore.url(tag),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast('Tag wiederhergestellt', 'success'),
        },
    );
}

function confirmForceDelete(tag: Tag) {
    forceDeleteTarget.value = tag;
}

function forceDeleteTag() {
    if (!forceDeleteTarget.value) {
        return;
    }
    router.delete(TagController.forceDelete.url(forceDeleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            forceDeleteTarget.value = null;
            toast('Tag endgültig gelöscht', 'success');
        },
    });
}

async function copyUrl(slug: string) {
    const url = `${window.location.origin}${PublicTagController.show.url(slug)}`;
    await navigator.clipboard.writeText(url);

    toast('URL copied', 'success');
}
</script>

<template>
    <Head title="Tags" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Tags"
                description="Verwalte deine Tags und deren Sichtbarkeit."
            />
            <Button
                variant="ghost"
                size="sm"
                :class="
                    showTrashed ? 'text-destructive' : 'text-muted-foreground'
                "
                @click="toggleTrashed"
            >
                <Trash2 class="size-4" />
                Papierkorb
            </Button>
        </div>

        <!-- Create form (hidden in trash view) -->
        <Form
            v-if="!showTrashed"
            v-bind="TagController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4"
            v-slot="{ errors, processing }"
            @success="
                () => {
                    createColor = 'gray';
                    createIsPublic = false;
                    toast('Tag created', 'success');
                }
            "
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <Label for="tag-name">Name</Label>
                    <Input
                        id="tag-name"
                        name="name"
                        placeholder="Tag name"
                        autocomplete="off"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="tag-description">Description</Label>
                    <Textarea
                        id="tag-description"
                        name="description"
                        placeholder="Optional description"
                        class="resize-none"
                        rows="1"
                    />
                    <InputError :message="errors.description" />
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-6">
                <div class="flex flex-col gap-2">
                    <Label>Color</Label>
                    <input type="hidden" name="color" :value="createColor" />
                    <ColorPalette v-model="createColor" />
                    <InputError :message="errors.color" />
                </div>

                <div class="flex items-center gap-2">
                    <input
                        type="hidden"
                        name="is_public"
                        :value="createIsPublic ? '1' : '0'"
                    />
                    <Checkbox id="create-is-public" v-model="createIsPublic" />
                    <Label for="create-is-public">Public</Label>
                    <InputError :message="errors.is_public" />
                </div>

                <Button type="submit" :disabled="processing" class="ml-auto">
                    Add
                </Button>
            </div>
        </Form>

        <!-- Tag list -->
        <ul class="flex flex-col gap-2">
            <li
                v-for="tag in tags"
                :key="tag.id"
                class="flex flex-col gap-3 rounded-lg border px-4 py-3"
                :class="showTrashed ? 'opacity-60' : ''"
            >
                <!-- Inline edit form (only in normal view) -->
                <template v-if="!showTrashed && editingTag?.id === tag.id">
                    <Form
                        v-bind="TagController.update.form(tag)"
                        :options="{ preserveScroll: true }"
                        class="flex flex-col gap-4"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`tag-name-${tag.id}`"
                                    class="sr-only"
                                    >Name</Label
                                >
                                <Input
                                    :id="`tag-name-${tag.id}`"
                                    name="name"
                                    :default-value="tag.name"
                                    placeholder="Tag name"
                                    autocomplete="off"
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`tag-desc-${tag.id}`"
                                    class="sr-only"
                                    >Description</Label
                                >
                                <Textarea
                                    :id="`tag-desc-${tag.id}`"
                                    name="description"
                                    :default-value="tag.description ?? ''"
                                    placeholder="Optional description"
                                    class="resize-none"
                                    rows="1"
                                />
                                <InputError :message="errors.description" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-end gap-6">
                            <div class="flex flex-col gap-2">
                                <input
                                    type="hidden"
                                    name="color"
                                    :value="editColor"
                                />
                                <ColorPalette v-model="editColor" />
                                <InputError :message="errors.color" />
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    type="hidden"
                                    name="is_public"
                                    :value="editIsPublic ? '1' : '0'"
                                />
                                <Checkbox
                                    :id="`edit-is-public-${tag.id}`"
                                    v-model="editIsPublic"
                                />
                                <Label :for="`edit-is-public-${tag.id}`"
                                    >Public</Label
                                >
                                <InputError :message="errors.is_public" />
                            </div>

                            <div class="ml-auto flex gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="processing"
                                    >Save</Button
                                >
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="cancelEdit"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </Form>
                </template>

                <template v-else>
                    <div class="flex items-center gap-3">
                        <span
                            class="size-4 shrink-0 rounded-full"
                            :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                        />

                        <span
                            class="flex-1 font-medium"
                            :class="showTrashed ? 'line-through' : ''"
                            >{{ tag.name }}</span
                        >

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
                        <template v-if="!showTrashed">
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Edit ${tag.name}`"
                                @click="startEdit(tag)"
                            >
                                <Pencil class="size-4" />
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Delete ${tag.name}`"
                                @click="confirmDelete(tag)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
                        </template>

                        <!-- Trash actions -->
                        <template v-else>
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Restore ${tag.name}`"
                                @click="restoreTag(tag)"
                            >
                                <RotateCcw class="size-4" />
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Permanently delete ${tag.name}`"
                                @click="confirmForceDelete(tag)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
                        </template>
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
                            public
                        </span>
                        <code
                            class="flex-1 truncate text-xs text-muted-foreground"
                        >
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
        </ul>

        <p v-if="tags.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed ? 'Keine gelöschten Tags.' : 'Keine Tags vorhanden.'
            }}
        </p>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Tag Löschen?"
        :description="`Tag '${deleteTarget?.name}' löschen? Diese Aktion kann rückgängig gemacht werden.`"
        confirm-label="Löschen"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteTag"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        title="Endgültig löschen?"
        :description="`'${forceDeleteTarget?.name}' wird permanent gelöscht und kann nicht wiederhergestellt werden.`"
        confirm-label="Endgültig löschen"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteTag"
    />
</template>
