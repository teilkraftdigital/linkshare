<script setup lang="ts">
import { Globe, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
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

type Preview = {
    buckets: Bucket[];
    tags: Tag[];
    link_count: number;
};

const props = defineProps<{
    open: boolean;
    preview: Preview | null;
    importing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [bucketNames: string[], tagNames: string[]];
}>();

const { t } = useI18n();
const allBuckets = ref(true);
const selectedBucketNames = ref<Set<string>>(new Set());
const allTags = ref(true);
const selectedTagNames = ref<Set<string>>(new Set());

watch(
    () => props.open,
    (opened) => {
        if (opened && props.preview) {
            allBuckets.value = true;
            selectedBucketNames.value = new Set(
                props.preview.buckets.map((b) => b.name),
            );
            allTags.value = true;
            selectedTagNames.value = new Set(
                props.preview.tags.map((t) => t.name),
            );
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    t('import.jsonImport.modal.title')
                }}</DialogTitle>
            </DialogHeader>

            <div v-if="preview" class="space-y-5 py-2">
                <p class="text-sm text-muted-foreground">
                    <span class="mr-0.5 font-medium text-foreground">{{
                        preview.link_count
                    }}</span>
                    {{
                        t(
                            'import.jsonImport.modal.foundLinks',
                            preview.link_count,
                        )
                    }}
                    {{ t('import.jsonImport.modal.selectInstruction') }}
                </p>

                <!-- Bucket selection -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="json-import-all-buckets"
                            :model-value="allBuckets"
                            @update:model-value="allBuckets = $event as boolean"
                        />
                        <Label
                            for="json-import-all-buckets"
                            class="cursor-pointer"
                        >
                            {{ t('import.jsonImport.modal.allBuckets') }}
                        </Label>
                    </div>

                    <div
                        v-if="!allBuckets && preview.buckets.length"
                        class="ml-6 max-h-40 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="bucket in preview.buckets"
                            :key="bucket.name"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`json-import-bucket-${bucket.name}`"
                                :model-value="
                                    selectedBucketNames.has(bucket.name)
                                "
                                @update:model-value="
                                    $event
                                        ? selectedBucketNames.add(bucket.name)
                                        : selectedBucketNames.delete(
                                              bucket.name,
                                          )
                                "
                            />
                            <Label
                                :for="`json-import-bucket-${bucket.name}`"
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
                            id="json-import-all-tags"
                            :model-value="allTags"
                            @update:model-value="allTags = $event as boolean"
                        />
                        <Label
                            for="json-import-all-tags"
                            class="cursor-pointer"
                        >
                            {{ t('import.jsonImport.modal.allTags') }}
                        </Label>
                    </div>

                    <div
                        v-if="!allTags && preview.tags.length"
                        class="ml-6 max-h-40 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="tag in preview.tags"
                            :key="tag.name"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`json-import-tag-${tag.name}`"
                                :model-value="selectedTagNames.has(tag.name)"
                                @update:model-value="
                                    $event
                                        ? selectedTagNames.add(tag.name)
                                        : selectedTagNames.delete(tag.name)
                                "
                            />
                            <Label
                                :for="`json-import-tag-${tag.name}`"
                                class="flex cursor-pointer items-center gap-1.5 font-normal"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="
                                        COLOR_BG[tag.color] ?? 'bg-gray-400'
                                    "
                                />
                                {{ tag.name }}

                                <Globe
                                    v-if="tag.is_public"
                                    class="size-3 opacity-60"
                                    :aria-label="
                                        t('tags.select.publicTagAriaLabel', {
                                            name: tag.name,
                                        })
                                    "
                                />
                            </Label>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('import.jsonImport.modal.cancel') }}
                </Button>
                <Button
                    @click="
                        emit(
                            'confirm',
                            allBuckets
                                ? preview!.buckets.map((b) => b.name)
                                : [...selectedBucketNames],
                            allTags
                                ? preview!.tags.map((t) => t.name)
                                : [...selectedTagNames],
                        )
                    "
                >
                    <Upload class="mr-2 size-4" />
                    {{ t('import.jsonImport.modal.importButton') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
