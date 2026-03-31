<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import TagSelect from '@/components/links/TagSelect.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Link, Bucket, Tag } from '@/types/dashboard';

type Props = {
    buckets: Bucket[];
    tags: Tag[];
};

defineProps<Props>();

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
                    Titel
                </Label>
                <Input
                    :id="`edit-title-${link.id}`"
                    name="title"
                    v-model="link.title"
                    placeholder="Link Titel"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`edit-desc-${link.id}`" class="sr-only">
                    Beschreibung
                </Label>
                <Textarea
                    :id="`edit-desc-${link.id}`"
                    name="description"
                    :default-value="link.description ?? ''"
                    v-model="link.description"
                    placeholder="Optionale Beschreibung"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`edit-notes-${link.id}`" class="sr-only">
                    Notizen (privat)
                </Label>
                <Textarea
                    :id="`edit-notes-${link.id}`"
                    name="notes"
                    v-model="link.notes"
                    placeholder="Private Notizen"
                    class="resize-none"
                    rows="2"
                />
                <InputError :message="errors.notes" />
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <div class="flex flex-col gap-2">
                <Label :for="`edit-bucket-${link.id}`"> Bucket </Label>
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

            <div v-if="tags.length > 0" class="flex flex-col gap-2">
                <Label>Tags</Label>
                <TagSelect :tags="tags" v-model="editTagIds" />
            </div>
        </div>

        <div class="flex gap-2">
            <Button type="submit" size="sm" :disabled="processing">
                Speichern
            </Button>
            <Button
                type="button"
                size="sm"
                variant="outline"
                @click="cancelEdit"
            >
                Abbrechen
            </Button>
        </div>
    </Form>
</template>
