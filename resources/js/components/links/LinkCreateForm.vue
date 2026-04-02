<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { RotateCcw, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import LinkSimilarMessage from '@/components/links/LinkSimilarMessage.vue';
import TagSelect from '@/components/links/TagSelect.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useMetaFetch } from '@/composables/useMetaFetch';
import { useTagCreate } from '@/composables/useTagCreate';
import { useToast } from '@/composables/useToast';
import type { Bucket, Tag } from '@/types/dashboard';

const props = defineProps<{
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

const { t } = useI18n();
const { toast } = useToast();
const { createError: tagCreateError, createTag } = useTagCreate();

const localTags = ref<Tag[]>([...props.tags]);
watch(
    () => props.tags,
    (tags) => {
        localTags.value = [...tags];
    },
);

const createUrl = ref('');
const createTitle = ref('');
const createDescription = ref('');
const createNotes = ref('');
const createBucketId = ref<number>(props.inboxBucketId);
const createTagIds = ref<number[]>([]);

const isCreateFormDirty = computed(
    () =>
        createUrl.value !== '' ||
        createTitle.value !== '' ||
        createDescription.value !== '' ||
        createNotes.value !== '' ||
        createBucketId.value !== props.inboxBucketId ||
        createTagIds.value.length > 0,
);

const {
    exists: duplicateExists,
    similar: duplicateSimilar,
    check: checkDuplicate,
    reset: resetDuplicate,
} = useDuplicateCheck();

const {
    fetching: metaFetching,
    failed: metaFailed,
    faviconUrl: createFaviconUrl,
    fetch: fetchMeta,
    reset: resetMeta,
} = useMetaFetch((meta) => {
    if (meta.title && !createTitle.value) {
createTitle.value = meta.title;
}

    if (meta.description && !createDescription.value) {
createDescription.value = meta.description;
}
});

function resetCreateForm() {
    createUrl.value = '';
    createTitle.value = '';
    createDescription.value = '';
    createNotes.value = '';
    createBucketId.value = props.inboxBucketId;
    createTagIds.value = [];
    resetDuplicate();
    resetMeta();
}

const events = defineEmits<{
    created: [value: any];
}>();

const handleCreateSubmit = (value: any) => {
    events('created', value);
};

async function handleTagCreated(name: string) {
    const tag = await createTag(name);

    if (tag) {
        localTags.value = [...localTags.value, tag];
        createTagIds.value = [...createTagIds.value, tag.id];
    }
}
</script>

<template>
    <Form
        v-bind="LinkController.store.form()"
        :options="{ preserveScroll: true }"
        class="flex flex-col gap-4 rounded-lg border p-4"
        v-slot="{ errors, processing, submit }"
        @success="
            () => {
                resetCreateForm();
                toast(t('links.saved'), 'success');
            }
        "
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label for="link-url">URL</Label>
                <div class="relative">
                    <img
                        v-if="createFaviconUrl"
                        :src="createFaviconUrl"
                        class="absolute top-2.5 left-2.5 size-4 rounded-sm object-contain"
                        alt=""
                        @error="
                            ($event.target as HTMLImageElement).style.display =
                                'none'
                        "
                    />
                    <Input
                        id="link-url"
                        v-model="createUrl"
                        name="url"
                        type="url"
                        placeholder="https://example.com"
                        autocomplete="off"
                        :class="createFaviconUrl ? 'pl-8' : ''"
                        @input="
                            fetchMeta(createUrl);
                            checkDuplicate(createUrl);
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
                <p v-if="metaFailed" class="text-xs text-muted-foreground">
                    {{ $t('links.metaLoadFailed') }}
                </p>
                <InputError :message="errors.url" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="link-title">{{ $t('fields.title') }}</Label>
                <Input
                    id="link-title"
                    v-model="createTitle"
                    name="title"
                    :placeholder="$t('placeholders.linkTitle')"
                    autocomplete="off"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="link-description">{{ $t('fields.description') }}</Label>
                <Textarea
                    id="link-description"
                    v-model="createDescription"
                    name="description"
                    :placeholder="$t('placeholders.optionalDescription')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="link-notes">
                    {{ $t('fields.notes') }}
                    <span class="text-muted-foreground"> ({{ $t('fields.notesPrivate') }})</span>
                </Label>
                <Textarea
                    id="link-notes"
                    v-model="createNotes"
                    name="notes"
                    :placeholder="$t('placeholders.privateNotes')"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.notes" />
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <div class="flex flex-col gap-2">
                <Label for="link-bucket">{{ $t('fields.bucket') }}</Label>
                <select
                    id="link-bucket"
                    name="bucket_id"
                    :value="createBucketId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    @change="
                        createBucketId = Number(
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
                <Label>{{ $t('fields.tags') }}</Label>
                <TagSelect
                    :tags="localTags"
                    v-model="createTagIds"
                    :create-error="tagCreateError"
                    @tag-created="handleTagCreated"
                />
                <InputError :message="errors['tag_ids']" />
            </div>
        </div>

        <input
            v-if="createFaviconUrl"
            type="hidden"
            name="favicon_url"
            :value="createFaviconUrl"
        />

        <div class="flex gap-2 self-start">
            <Button
                type="button"
                :disabled="processing"
                @click="handleCreateSubmit(submit)"
            >
                {{ $t('common.save') }}
            </Button>
            <Button
                v-if="isCreateFormDirty"
                type="button"
                variant="ghost"
                size="icon"
                :aria-label="$t('common.clearForm')"
                @click="resetCreateForm"
            >
                <RotateCcw class="size-4" />
            </Button>
        </div>
    </Form>
</template>
