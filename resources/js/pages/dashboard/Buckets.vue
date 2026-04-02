<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Link as LinkIcon, Pencil, RotateCcw, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import BucketController from '@/actions/App/Http/Controllers/Dashboard/BucketController';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { COLOR_BG } from '@/lib/colors';
import { index } from '@/routes/dashboard/buckets';
import type { Bucket } from '@/types/dashboard';

type Props = {
    buckets: Bucket[];
    showTrashed: boolean;
};

const props = defineProps<Props>();

const { t } = useI18n();

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

const { toast } = useToast();
const createColor = ref<string>('gray');
const editingBucket = ref<Bucket | null>(null);
const editColor = ref<string>('gray');
const deleteTarget = ref<Bucket | null>(null);
const forceDeleteTarget = ref<Bucket | null>(null);

function toggleTrashed() {
    router.get(index(), props.showTrashed ? {} : { trashed: '1' }, {
        preserveState: false,
    });
}

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
            toast(t('buckets.deleted'), 'success');
        },
    });
}

function restoreBucket(bucket: Bucket) {
    router.post(
        BucketController.restore.url(bucket),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast(t('buckets.restored'), 'success'),
        },
    );
}

function confirmForceDelete(bucket: Bucket) {
    forceDeleteTarget.value = bucket;
}

function forceDeleteBucket() {
    if (!forceDeleteTarget.value) {
        return;
    }

    router.delete(BucketController.forceDelete.url(forceDeleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            forceDeleteTarget.value = null;
            toast(t('buckets.forceDeleted'), 'success');
        },
    });
}
</script>

<template>
    <Head :title="t('buckets.pageTitle')" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="t('buckets.pageTitle')"
                :description="t('buckets.description')"
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
                {{ t('common.trash') }}
            </Button>
        </div>

        <!-- Create form (hidden in trash view) -->
        <Form
            v-if="!showTrashed"
            v-bind="BucketController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-start"
            v-slot="{ errors, processing }"
            @success="
                () => {
                    createColor = 'gray';
                    toast(t('buckets.created'), 'success');
                }
            "
        >
            <div class="flex flex-1 flex-col gap-2">
                <Label for="bucket-name">{{ t('buckets.newBucket') }}</Label>
                <Input
                    id="bucket-name"
                    name="name"
                    :placeholder="t('buckets.namePlaceholder')"
                    autocomplete="off"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label>{{ t('fields.color') }}</Label>
                <input type="hidden" name="color" :value="createColor" />
                <ColorPalette v-model="createColor" class="mt-1.5" />
                <InputError :message="errors.color" />
            </div>

            <Button type="submit" :disabled="processing" class="self-end">
                {{ t('common.add') }}
            </Button>
        </Form>

        <!-- Bucket list -->
        <ul class="flex flex-col gap-2">
            <li
                v-for="bucket in buckets"
                :key="bucket.id"
                class="flex items-center gap-3 rounded-lg border px-4 py-3"
                :class="showTrashed ? 'opacity-60' : ''"
            >
                <!-- Color dot -->
                <span
                    class="size-4 shrink-0 rounded-full"
                    :class="COLOR_BG[bucket.color] ?? 'bg-gray-400'"
                />

                <!-- Inline edit form (only in normal view) -->
                <template
                    v-if="!showTrashed && editingBucket?.id === bucket.id"
                >
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
                                {{ t('buckets.nameLabel') }}
                            </Label>
                            <Input
                                :id="`bucket-name-${bucket.id}`"
                                name="name"
                                :default-value="bucket.name"
                                :placeholder="t('buckets.namePlaceholder')"
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
                                {{ t('fields.color') }}
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
                            >
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

                <template v-else>
                    <span
                        class="flex-1 font-medium"
                        :class="showTrashed ? 'line-through' : ''"
                        >{{ bucket.name }}</span
                    >

                    <span
                        class="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        :aria-label="`${bucket.links_count} links`"
                    >
                        <LinkIcon class="size-3 shrink-0" />
                        {{ bucket.links_count }}
                    </span>

                    <span
                        v-if="!showTrashed && bucket.is_inbox"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('buckets.inbox') }}
                    </span>

                    <!-- Normal actions -->
                    <template v-if="!showTrashed">
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="t('buckets.editAriaLabel', { name: bucket.name })"
                            @click="startEdit(bucket)"
                        >
                            <Pencil class="size-4" />
                        </Button>

                        <Button
                            v-if="!bucket.is_inbox"
                            variant="ghost"
                            size="icon"
                            :aria-label="t('buckets.deleteAriaLabel', { name: bucket.name })"
                            @click="confirmDelete(bucket)"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </template>

                    <!-- Trash actions -->
                    <template v-else>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="t('buckets.restoreAriaLabel', { name: bucket.name })"
                            @click="restoreBucket(bucket)"
                        >
                            <RotateCcw class="size-4" />
                        </Button>

                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="t('buckets.forceDeleteAriaLabel', { name: bucket.name })"
                            @click="confirmForceDelete(bucket)"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </template>
                </template>
            </li>
        </ul>

        <p v-if="buckets.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed
                    ? t('buckets.emptyTrashed')
                    : t('buckets.empty')
            }}
        </p>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        :title="t('buckets.delete.title')"
        :description="t('buckets.delete.description', { name: deleteTarget?.name })"
        :confirm-label="t('buckets.delete.confirm')"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteBucket"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        :title="t('buckets.forceDeleteDialog.title')"
        :description="t('buckets.forceDeleteDialog.description', { name: forceDeleteTarget?.name })"
        :confirm-label="t('buckets.forceDeleteDialog.confirm')"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteBucket"
    />
</template>
