<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import TagSelect from '@/components/links/TagSelect.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTagCreate } from '@/composables/useTagCreate';
import type { Link, Bucket, Tag, TagCreatePayload } from '@/types/dashboard';

type Props = {
    buckets: Bucket[];
    tags: Tag[];
};

const props = defineProps<Props>();

const { createError: tagCreateError, createTag } = useTagCreate();
const localTags = ref<Tag[]>([...props.tags]);
const { t } = useI18n();

const event = defineEmits<{
    (e: 'cancel'): void;
}>();

function cancelEdit() {
    event('cancel');
}

const editBucketId = defineModel<Link['bucket_id']>('bucket_id', {
    required: true,
});

const link = defineModel<Link>('link', {
    required: true,
});

const editTagIds = defineModel<number[]>('tag_ids', {
    required: false,
    default: () => [],
});

async function handleTagCreated(payload: TagCreatePayload) {
    let parentId = payload.parentId;

    if (payload.parentName && parentId === undefined) {
        const parent = await createTag(payload.parentName);
        if (!parent) return;
        localTags.value = [...localTags.value, parent];
        parentId = parent.id;
    }

    const tag = await createTag(payload.name, parentId);
    if (tag) {
        localTags.value = [...localTags.value, tag];
        editTagIds.value = [...editTagIds.value, tag.id];
    }
}
</script>

<template>
    <Form
        v-bind="LinkController.update.form(link)"
        :options="{ preserveScroll: true }"
        class="flex flex-col gap-4 rounded-lg border px-4 py-3"
        v-slot="{ errors, processing }"
        @success="cancelEdit"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label :for="`edit-url-${link.id}`" class="sr-only">
                    URL
                </Label>
                <Input
                    :id="`edit-url-${link.id}`"
                    name="url"
                    type="url"
                    v-model="link.url"
                    placeholder="https://example.com"
                />
                <InputError :message="errors.url" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`edit-title-${link.id}`" class="sr-only">
                    {{ t('fields.title') }}
                </Label>
                <Input
                    :id="`edit-title-${link.id}`"
                    name="title"
                    v-model="link.title"
                    :placeholder="t('placeholders.linkTitle')"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`edit-desc-${link.id}`" class="sr-only">
                    {{ t('fields.description') }}
                </Label>
                <Textarea
                    :id="`edit-desc-${link.id}`"
                    name="description"
                    :default-value="link.description ?? ''"
                    v-model="link.description"
                    :placeholder="t('placeholders.optionalDescription')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`edit-notes-${link.id}`" class="sr-only">
                    {{ t('fields.notesPrivate') }}
                </Label>
                <Textarea
                    :id="`edit-notes-${link.id}`"
                    name="notes"
                    v-model="link.notes"
                    :placeholder="t('placeholders.privateNotes')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.notes" />
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <div class="flex flex-col gap-2">
                <Label :for="`edit-bucket-${link.id}`">{{
                    t('fields.bucket')
                }}</Label>
                <select
                    :id="`edit-bucket-${link.id}`"
                    name="bucket_id"
                    :value="editBucketId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    @change="
                        editBucketId = Number(
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="bucket in buckets"
                        :key="bucket.id"
                        :value="bucket.id"
                    >
                        {{ bucket.name }}
                    </option>
                </select>
                <InputError :message="errors.bucket_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label>{{ t('fields.tags') }}</Label>
                <TagSelect
                    :tags="localTags"
                    v-model="editTagIds"
                    :create-error="tagCreateError"
                    @tag-created="handleTagCreated"
                />
            </div>
        </div>

        <div class="flex gap-2">
            <Button type="submit" size="sm" :disabled="processing">
                {{ t('common.save') }}
            </Button>
            <Button
                type="button"
                size="sm"
                variant="outline"
                @click="cancelEdit"
            >
                {{ t('common.cancel') }}
            </Button>
        </div>
    </Form>
</template>
