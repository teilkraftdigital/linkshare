<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/shared/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeRoute } from '@/routes/dashboard/import';
import type { Bucket } from '@/types/dashboard';

const props = defineProps<{
    buckets: Bucket[];
    inboxBucketId: number;
}>();

const { t } = useI18n();
const page = usePage();
const importResult = computed(() => page.props.flash?.import_result ?? null);
const selectedBucketId = ref<number>(props.inboxBucketId);
</script>

<template>
    <div>
        <h2 class="text-sm font-semibold">{{ $t('import.htmlImport.title') }}</h2>
        <p class="mt-0.5 text-sm text-muted-foreground">
            {{ $t('import.htmlImport.description') }}
        </p>
    </div>

    <div
        v-if="importResult"
        class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
    >
        <span class="font-medium">
            {{ t('import.htmlImport.imported', importResult.imported) }}
        </span>
        <span v-if="importResult.skipped > 0">
            · {{ t('import.htmlImport.skipped', { n: importResult.skipped }) }}
        </span>
        <span v-if="importResult.hints > 0">
            · {{ t('import.htmlImport.hints', importResult.hints) }}
        </span>
    </div>

    <Form
        :action="storeRoute().url"
        method="post"
        enctype="multipart/form-data"
        #default="{ errors, processing }"
    >
        <input type="hidden" name="bucket_id" :value="selectedBucketId" />

        <div class="space-y-4">
            <div class="space-y-1.5">
                <Label for="bucket">{{ $t('import.htmlImport.targetBucket') }}</Label>
                <select
                    id="bucket"
                    :value="selectedBucketId"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                    @change="
                        selectedBucketId = Number(
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="bucket in buckets"
                        :key="bucket.id"
                        :value="bucket.id"
                    >
                        {{ bucket.name }}{{ bucket.is_inbox ? ` (${$t('import.htmlImport.inboxSuffix')})` : '' }}
                    </option>
                </select>
                <InputError :message="errors.bucket_id" />
            </div>

            <div class="space-y-1.5">
                <Label for="html-file">{{ $t('import.htmlImport.fileLabel') }}</Label>
                <Input
                    id="html-file"
                    name="file"
                    type="file"
                    accept=".html,.htm"
                    class="cursor-pointer"
                />
                <InputError :message="errors.file" />
            </div>

            <Button type="submit" :disabled="processing">
                <Upload class="mr-2 h-4 w-4" />
                {{ processing ? $t('import.htmlImport.importing') : $t('import.htmlImport.importButton') }}
            </Button>
        </div>
    </Form>
</template>
