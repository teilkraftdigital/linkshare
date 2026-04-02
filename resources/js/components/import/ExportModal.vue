<script setup lang="ts">
import { Download } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ExportController from '@/actions/App/Http/Controllers/Dashboard/ExportController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { COLOR_BG } from '@/lib/colors';
import type { Bucket, Tag } from '@/types/dashboard';

const props = defineProps<{
    open: boolean;
    buckets: Bucket[];
    tags: Tag[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// Reset state every time the modal opens
const allBuckets = ref(true);
const selectedBucketIds = ref<Set<number>>(new Set());
const allTags = ref(true);
const selectedTagIds = ref<Set<number>>(new Set());
const includesNotes = ref(false);

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            allBuckets.value = true;
            selectedBucketIds.value = new Set(props.buckets.map((b) => b.id));
            allTags.value = true;
            selectedTagIds.value = new Set(props.tags.map((t) => t.id));
            includesNotes.value = false;
        }
    },
);

function toggleBucket(id: number, checked: boolean) {
    if (checked) {
        selectedBucketIds.value.add(id);
    } else {
        selectedBucketIds.value.delete(id);
    }
}

function toggleTag(id: number, checked: boolean) {
    if (checked) {
        selectedTagIds.value.add(id);
    } else {
        selectedTagIds.value.delete(id);
    }
}

const { t } = useI18n();
const exporting = ref(false);

async function downloadExport() {
    exporting.value = true;

    try {
        const csrfToken =
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.content ?? '';
        const response = await fetch(ExportController.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Inertia': 'false',
            },
            body: JSON.stringify({
                bucket_ids: allBuckets.value
                    ? []
                    : [...selectedBucketIds.value],
                tag_ids: allTags.value ? [] : [...selectedTagIds.value],
                includes_notes: includesNotes.value,
            }),
        });

        if (!response.ok) {
            return;
        }

        const blob = await response.blob();
        const disposition = response.headers.get('Content-Disposition') ?? '';
        const match = disposition.match(/filename="([^"]+)"/);
        const filename = match
            ? match[1]
            : `linkshare-export-${new Date().toISOString().slice(0, 10)}.json`;

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);

        emit('update:open', false);
    } finally {
        exporting.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('import.export.modalTitle') }}</DialogTitle>
            </DialogHeader>

            <div class="space-y-5 py-2">
                <!-- Bucket selection -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="export-all-buckets"
                            :model-value="allBuckets"
                            @update:model-value="allBuckets = $event as boolean"
                        />
                        <Label for="export-all-buckets" class="cursor-pointer">
                            {{ t('import.export.allBuckets') }}
                        </Label>
                    </div>

                    <div
                        v-if="!allBuckets && buckets.length"
                        class="ml-6 max-h-40 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="bucket in buckets"
                            :key="bucket.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`export-bucket-${bucket.id}`"
                                :model-value="selectedBucketIds.has(bucket.id)"
                                @update:model-value="
                                    toggleBucket(bucket.id, $event as boolean)
                                "
                            />
                            <Label
                                :for="`export-bucket-${bucket.id}`"
                                class="flex cursor-pointer items-center gap-1.5 font-normal"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="
                                        COLOR_BG[bucket.color] ?? 'bg-gray-400'
                                    "
                                />
                                {{ bucket.name }}
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Tag selection -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="export-all-tags"
                            :model-value="allTags"
                            @update:model-value="allTags = $event as boolean"
                        />
                        <Label for="export-all-tags" class="cursor-pointer">
                            {{ t('import.export.allTags') }}
                        </Label>
                    </div>

                    <div
                        v-if="!allTags && tags.length"
                        class="ml-6 max-h-40 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`export-tag-${tag.id}`"
                                :model-value="selectedTagIds.has(tag.id)"
                                @update:model-value="
                                    toggleTag(tag.id, $event as boolean)
                                "
                            />
                            <Label
                                :for="`export-tag-${tag.id}`"
                                class="flex cursor-pointer items-center gap-1.5 font-normal"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="
                                        COLOR_BG[tag.color] ?? 'bg-gray-400'
                                    "
                                />
                                {{ tag.name }}
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Private notes -->
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="export-includes-notes"
                        v-model="includesNotes"
                    />
                    <Label
                        for="export-includes-notes"
                        class="cursor-pointer font-normal"
                    >
                        {{ t('import.export.includeNotes') }}
                    </Label>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('import.export.cancel') }}
                </Button>
                <Button :disabled="exporting" @click="downloadExport">
                    <Download class="mr-2 size-4" />
                    {{
                        exporting
                            ? t('import.export.exporting')
                            : t('import.export.button')
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
