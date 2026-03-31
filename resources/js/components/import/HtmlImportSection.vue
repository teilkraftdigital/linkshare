<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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

const page = usePage();
const importResult = computed(() => page.props.flash?.import_result ?? null);
const selectedBucketId = ref<number>(props.inboxBucketId);
</script>

<template>
    <div>
        <h2 class="text-sm font-semibold">Netscape HTML importieren</h2>
        <p class="mt-0.5 text-sm text-muted-foreground">
            Browser-Bookmarks im Netscape HTML Format (Chrome, Firefox, Safari).
        </p>
    </div>

    <div
        v-if="importResult"
        class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
    >
        <span class="font-medium">
            {{ importResult.imported }}
            {{ importResult.imported === 1 ? 'Link' : 'Links' }}
            importiert
        </span>
        <span v-if="importResult.skipped > 0">
            · {{ importResult.skipped }} übersprungen
        </span>
        <span v-if="importResult.hints > 0">
            · {{ importResult.hints }} ähnliche URL{{ importResult.hints === 1 ? '' : 's' }} gefunden
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
                <Label for="bucket">Ziel-Bucket</Label>
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
                        {{ bucket.name }}{{ bucket.is_inbox ? ' (Standard)' : '' }}
                    </option>
                </select>
                <InputError :message="errors.bucket_id" />
            </div>

            <div class="space-y-1.5">
                <Label for="html-file">Bookmark-Datei (.html)</Label>
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
                {{ processing ? 'Importiere…' : 'Importieren' }}
            </Button>
        </div>
    </Form>
</template>
