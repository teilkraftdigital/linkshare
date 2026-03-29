<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useMetaFetch } from '@/composables/useMetaFetch';
import ConfirmModal from '@/components/ConfirmModal.vue';
import InputError from '@/components/InputError.vue';
import TagSelect from '@/components/TagSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Bucket, Tag } from '@/types/dashboard';

const props = defineProps<{
    prefillUrl: string;
    prefillTitle: string;
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

const url = ref(props.prefillUrl);
const title = ref(props.prefillTitle);
const description = ref('');
const bucketId = ref<number>(props.inboxBucketId);
const tagIds = ref<number[]>([]);
const saved = ref(false);

const {
    fetching: metaFetching,
    fetch: fetchMeta,
} = useMetaFetch((meta) => {
    if (meta.title && !title.value) title.value = meta.title;
    if (meta.description && !description.value) description.value = meta.description;
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
    saved.value = true;
    setTimeout(() => window.close(), 1500);
}
</script>

<template>
    <Head title="Quick Add" />

    <div class="flex min-h-screen flex-col gap-4 p-4">
        <h1 class="text-base font-semibold">Link speichern</h1>

        <div
            v-if="saved"
            class="flex flex-col items-center justify-center gap-3 py-8 text-center"
        >
            <CheckCircle2 class="size-10 text-green-500" />
            <p class="font-medium">Link saved!</p>
            <p class="text-sm text-muted-foreground">Dieses Fenster schließt sich automatisch…</p>
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
                    <Input
                        id="qa-url"
                        v-model="url"
                        name="url"
                        type="url"
                        placeholder="https://example.com"
                        autocomplete="off"
                        @input="fetchMeta(url); checkDuplicate(url)"
                    />
                    <Loader2
                        v-if="metaFetching"
                        class="absolute top-2.5 right-2.5 size-4 animate-spin text-muted-foreground"
                    />
                </div>
                <p v-if="duplicateExists" class="text-xs text-amber-600 dark:text-amber-400">
                    This link already exists.
                </p>
                <p v-else-if="duplicateSimilar" class="text-xs text-amber-600 dark:text-amber-400">
                    A similar link already exists.
                </p>
                <InputError :message="errors.url" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-title">Titel</Label>
                <Input
                    id="qa-title"
                    v-model="title"
                    name="title"
                    placeholder="Link-Titel"
                    autocomplete="off"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-description">Beschreibung</Label>
                <Textarea
                    id="qa-description"
                    v-model="description"
                    name="description"
                    placeholder="Optionale Beschreibung"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-notes">Notizen <span class="text-muted-foreground">(privat)</span></Label>
                <Textarea
                    id="qa-notes"
                    name="notes"
                    placeholder="Private Notizen"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.notes" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="qa-bucket">Bucket</Label>
                <select
                    id="qa-bucket"
                    :value="bucketId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    @change="bucketId = Number(($event.target as HTMLSelectElement).value)"
                >
                    <option v-for="bucket in buckets" :key="bucket.id" :value="bucket.id">
                        {{ bucket.name }}
                    </option>
                </select>
                <input type="hidden" name="bucket_id" :value="bucketId" />
                <InputError :message="errors.bucket_id" />
            </div>

            <div v-if="tags.length > 0" class="flex flex-col gap-1.5">
                <Label>Tags</Label>
                <TagSelect :tags="tags" v-model="tagIds" />
            </div>

            <Button
                type="button"
                :disabled="processing"
                class="mt-1"
                @click="handleSubmit(submit)"
            >
                {{ processing ? 'Speichere…' : 'Speichern' }}
            </Button>
        </Form>

        <ConfirmModal
            :open="duplicateConfirmOpen"
            title="Link already exists"
            description="A link with this URL is already saved. Add it again anyway?"
            confirm-label="Add anyway"
            @update:open="duplicateConfirmOpen = $event"
            @confirm="confirmDuplicateSubmit"
        />
    </div>
</template>
