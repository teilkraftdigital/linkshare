<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import BucketController from '@/actions/App/Http/Controllers/Dashboard/BucketController';
import ColorPalette from '@/components/ColorPalette.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { COLOR_BG } from '@/lib/colors';
import { index } from '@/routes/dashboard/buckets';

type Bucket = {
    id: number;
    name: string;
    color: string;
    is_inbox: boolean;
};

type Props = {
    buckets: Bucket[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Buckets',
                href: index(),
            },
        ],
    },
});

const createColor = ref<string>('gray');
const editingBucket = ref<Bucket | null>(null);
const editColor = ref<string>('gray');
const deleteTarget = ref<Bucket | null>(null);

function startEdit(bucket: Bucket) {
    editingBucket.value = bucket;
    editColor.value = bucket.color;
}

function cancelEdit() {
    editingBucket.value = null;
}

function confirmDelete(bucket: Bucket) {
    deleteTarget.value = bucket;
}

function deleteBucket() {
    if (!deleteTarget.value) {
        return;
    }
    router.delete(BucketController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Buckets" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Buckets"
            description="Organise your links into buckets"
        />

        <!-- Create form -->
        <Form
            v-bind="BucketController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-start"
            v-slot="{ errors, processing }"
            @success="createColor = 'gray'"
        >
            <div class="flex flex-1 flex-col gap-2">
                <Label for="bucket-name">New bucket</Label>
                <Input
                    id="bucket-name"
                    name="name"
                    placeholder="Bucket name"
                    autocomplete="off"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label>Color</Label>
                <input type="hidden" name="color" :value="createColor" />
                <ColorPalette v-model="createColor" class="mt-1.5" />
                <InputError :message="errors.color" />
            </div>

            <Button type="submit" :disabled="processing" class="self-end"
                >Add</Button
            >
        </Form>

        <!-- Bucket list -->
        <ul class="flex flex-col gap-2">
            <li
                v-for="bucket in buckets"
                :key="bucket.id"
                class="flex items-center gap-3 rounded-lg border px-4 py-3"
            >
                <!-- Color dot -->
                <span
                    class="size-4 shrink-0 rounded-full"
                    :class="COLOR_BG[bucket.color] ?? 'bg-gray-400'"
                />

                <!-- Inline edit form -->
                <template v-if="editingBucket?.id === bucket.id">
                    <Form
                        v-bind="BucketController.update.form(bucket)"
                        :options="{ preserveScroll: true }"
                        class="flex flex-1 flex-wrap items-start justify-between gap-3"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <div class="flex flex-col gap-2">
                            <Label
                                :for="`bucket-name-${bucket.id}`"
                                class="sr-only"
                            >
                                Bucket name
                            </Label>
                            <Input
                                :id="`bucket-name-${bucket.id}`"
                                name="name"
                                :default-value="bucket.name"
                                placeholder="Bucket name"
                                autocomplete="off"
                                class="h-8 w-48"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label
                                :for="`bucket-color-${bucket.id}`"
                                class="sr-only"
                            >
                                Color
                            </Label>
                            <input
                                type="hidden"
                                :id="`bucket-color-${bucket.id}`"
                                name="color"
                                :value="editColor"
                            />
                            <ColorPalette v-model="editColor" class="mt-1.5" />
                            <InputError :message="errors.color" />
                        </div>

                        <div class="flex gap-2 self-end">
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
                    </Form>
                </template>

                <template v-else>
                    <span class="flex-1 font-medium">{{ bucket.name }}</span>
                    <span
                        v-if="bucket.is_inbox"
                        class="text-xs text-muted-foreground"
                        >Inbox</span
                    >

                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="`Edit ${bucket.name}`"
                        @click="startEdit(bucket)"
                    >
                        <Pencil class="size-4" />
                    </Button>

                    <Button
                        v-if="!bucket.is_inbox"
                        variant="ghost"
                        size="icon"
                        :aria-label="`Delete ${bucket.name}`"
                        @click="confirmDelete(bucket)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </template>
            </li>
        </ul>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Delete bucket?"
        :description="`Delete '${deleteTarget?.name}'? This action cannot be undone.`"
        confirm-label="Delete"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteBucket"
    />
</template>
