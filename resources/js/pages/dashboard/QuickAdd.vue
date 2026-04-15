<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Loader2, RotateCcw } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import LinkSimilarMessage from '@/components/links/LinkSimilarMessage.vue';
import TagSelect from '@/components/links/TagSelect.vue';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useMetaFetch } from '@/composables/useMetaFetch';
import { useTagCreate } from '@/composables/useTagCreate';
import type { Bucket, Tag, TagCreatePayload } from '@/types/dashboard';

const props = defineProps<{
    prefillUrl: string;
    prefillTitle: string;
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

const { t } = useI18n();
const { createError: tagCreateError, createTag } = useTagCreate();

const url = ref(props.prefillUrl);
const title = ref(props.prefillTitle);
const description = ref('');
const notes = ref('');
const bucketId = ref<number>(props.inboxBucketId);
const tagIds = ref<number[]>([]);
const saved = ref(false);
const localTags = ref<Tag[]>([...props.tags]);

const isFormDirty = computed(
    () =>
        url.value !== props.prefillUrl ||
        title.value !== props.prefillTitle ||
        description.value !== '' ||
        notes.value !== '' ||
        bucketId.value !== props.inboxBucketId ||
        tagIds.value.length > 0,
);

function resetForm() {
    url.value = props.prefillUrl;
    title.value = props.prefillTitle;
    description.value = '';
    notes.value = '';
    bucketId.value = props.inboxBucketId;
    tagIds.value = [];
    resetDuplicate();
    resetMeta();
}

const {
    fetching: metaFetching,
    faviconUrl,
    fetch: fetchMeta,
    reset: resetMeta,
} = useMetaFetch((meta) => {
    if (meta.title && !title.value) {
title.value = meta.title;
}

    if (meta.description && !description.value) {
description.value = meta.description;
}
});

const {
    exists: duplicateExists,
    similar: duplicateSimilar,
    check: checkDuplicate,
    reset: resetDuplicate,
} = useDuplicateCheck();

const duplicateConfirmOpen = ref(false);
const pendingSubmit = ref<(() => void) | null>(null);

onMounted(() => {
    if (url.value) {
        fetchMeta(url.value);
        checkDuplicate(url.value);
    }
});

function handleSubmit(submit: () => void) {
    if (duplicateExists.value) {
        pendingSubmit.value = submit;
        duplicateConfirmOpen.value = true;
    } else {
        submit();
    }
}

function confirmDuplicateSubmit() {
    duplicateConfirmOpen.value = false;
    pendingSubmit.value?.();
    pendingSubmit.value = null;
}

function onSuccess() {
    resetDuplicate();
    resetMeta();
    saved.value = true;
    setTimeout(() => window.close(), 1500);
}

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
        tagIds.value = [...tagIds.value, tag.id];
    }
}
</script>

<template>
    <Head :title="t('quickAdd.pageTitle')" />

    <div class="flex min-h-screen flex-col gap-4 p-4">
        <h1 class="text-base font-semibold">{{ t('quickAdd.heading') }}</h1>

        <div
            v-if="saved"
            class="flex flex-col items-center justify-center gap-3 py-8 text-center"
        >
            <CheckCircle2 class="size-10 text-green-500" />
            <p class="font-medium">{{ t('quickAdd.saved') }}</p>
            <p class="text-sm text-muted-foreground">
                {{ t('quickAdd.autoClose') }}
            </p>
        </div>

        <Form
            v-else
            v-bind="LinkController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-3"
            @success="onSuccess"
            #default="{ errors, processing, submit }"
        >
            <div class="flex flex-col gap-1.5">
                <Label for="qa-url">URL</Label>
                <div class="relative">
                    <img
                        v-if="faviconUrl"
                        :src="faviconUrl"
                        class="absolute top-2.5 left-2.5 size-4 rounded-sm object-contain"
                        alt=""
                        @error="
                            ($event.target as HTMLImageElement).style.display =
                                'none'
                        "
                    />
                    <Input
                        id="qa-url"
                        v-model="url"
                        name="url"
                        type="url"
                        placeholder="https://example.com"
                        autocomplete="off"
                        :class="faviconUrl ? 'pl-8' : ''"
                        @input="
                            fetchMeta(url);
                            checkDuplicate(url);
                        "
                    />
                    <Loader2
                        v-if="metaFetching"
                        class="absolute top-2.5 right-2.5 size-4 animate-spin text-muted-foreground"
                    />
                </div>
                <LinkSimilarMessage
                    :similar="duplicateSimilar"
                    :existis="duplicateExists"
                />
                <InputError :message="errors.url" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-title">{{ t('fields.title') }}</Label>
                <Input
                    id="qa-title"
                    v-model="title"
                    name="title"
                    :placeholder="t('placeholders.linkTitle')"
                    autocomplete="off"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-description">{{ t('fields.description') }}</Label>
                <Textarea
                    id="qa-description"
                    v-model="description"
                    name="description"
                    :placeholder="t('placeholders.optionalDescription')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-notes">
                    {{ t('fields.notes') }}
                    <span class="text-muted-foreground">({{ t('fields.notesPrivate') }})</span></Label
                >
                <Textarea
                    id="qa-notes"
                    v-model="notes"
                    name="notes"
                    :placeholder="t('placeholders.privateNotes')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.notes" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-bucket">{{ t('fields.bucket') }}</Label>
                <select
                    id="qa-bucket"
                    :value="bucketId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    @change="
                        bucketId = Number(
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
                <input type="hidden" name="bucket_id" :value="bucketId" />
                <input
                    v-if="faviconUrl"
                    type="hidden"
                    name="favicon_url"
                    :value="faviconUrl"
                />
                <InputError :message="errors.bucket_id" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label>{{ t('fields.tags') }}</Label>
                <TagSelect
                    :tags="localTags"
                    v-model="tagIds"
                    :create-error="tagCreateError"
                    @tag-created="handleTagCreated"
                />
            </div>

            <div class="mt-1 flex gap-2">
                <Button
                    type="button"
                    :disabled="processing"
                    class="flex-1"
                    @click="handleSubmit(submit)"
                >
                    {{ processing ? t('quickAdd.saving') : t('quickAdd.save') }}
                </Button>
                <Button
                    v-if="isFormDirty"
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('quickAdd.resetForm')"
                    @click="resetForm"
                >
                    <RotateCcw class="size-4" />
                </Button>
            </div>
        </Form>

        <ConfirmModal
            :open="duplicateConfirmOpen"
            :title="t('links.duplicate.title')"
            :description="t('links.duplicate.description')"
            :confirm-label="t('links.duplicate.confirm')"
            @update:open="duplicateConfirmOpen = $event"
            @confirm="confirmDuplicateSubmit"
        />
    </div>
</template>
