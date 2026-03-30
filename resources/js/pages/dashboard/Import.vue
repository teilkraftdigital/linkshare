<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { BookmarkPlus, Copy, Download, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    create as importRoute,
    store as storeRoute,
} from '@/routes/dashboard/import';
import ExportController from '@/actions/App/Http/Controllers/Dashboard/ExportController';
import QuickAddController from '@/actions/App/Http/Controllers/Dashboard/QuickAddController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { COLOR_BG } from '@/lib/colors';
import type { Bucket, Tag } from '@/types/dashboard';

const props = defineProps<{
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Import & Export',
                href: importRoute(),
            },
        ],
    },
});

const page = usePage();
const importResult = computed(() => page.props.flash?.import_result ?? null);
const selectedBucketId = ref<number>(props.inboxBucketId);

const quickAddUrl = computed(() => {
    const base = page.props.appUrl.replace(/\/$/, '');
    return base + QuickAddController.url();
});

const bookmarkletCode = computed(() => {
    const url = quickAddUrl.value;
    return `javascript:(function(){window.open('${url}?url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title),'_blank','width=480,height=640,resizable=yes');})();`;
});

const copied = ref(false);

function copyBookmarklet() {
    navigator.clipboard.writeText(bookmarkletCode.value).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

// Export selections — all checked by default
const selectedBucketIds = ref<Set<number>>(new Set(props.buckets.map((b) => b.id)));
const selectedTagIds = ref<Set<number>>(new Set(props.tags.map((t) => t.id)));
const includesNotes = ref(false);

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

const exporting = ref(false);

async function downloadExport() {
    exporting.value = true;
    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const response = await fetch(ExportController.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Inertia': 'false',
            },
            body: JSON.stringify({
                bucket_ids: [...selectedBucketIds.value],
                tag_ids: [...selectedTagIds.value],
                includes_notes: includesNotes.value,
            }),
        });

        if (!response.ok) {
            return;
        }

        const blob = await response.blob();
        const disposition = response.headers.get('Content-Disposition') ?? '';
        const match = disposition.match(/filename="([^"]+)"/);
        const filename = match ? match[1] : `linkshare-export-${new Date().toISOString().slice(0, 10)}.json`;

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    } finally {
        exporting.value = false;
    }
}
</script>

<template>
    <Head title="Import & Export" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Import & Export"
            description="Exportiere oder importiere deine Links, Buckets und Tags."
        />

        <!-- Export section -->
        <div class="space-y-4">
            <div>
                <h2 class="text-sm font-semibold">Exportieren</h2>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Wähle aus, welche Buckets und Tags exportiert werden sollen. Einträge im Papierkorb sind nicht enthalten.
                </p>
            </div>

            <!-- Bucket selection -->
            <div v-if="buckets.length" class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Buckets</p>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <div
                        v-for="bucket in buckets"
                        :key="bucket.id"
                        class="flex items-center gap-2"
                    >
                        <Checkbox
                            :id="`export-bucket-${bucket.id}`"
                            :model-value="selectedBucketIds.has(bucket.id)"
                            @update:model-value="toggleBucket(bucket.id, $event as boolean)"
                        />
                        <Label :for="`export-bucket-${bucket.id}`" class="flex cursor-pointer items-center gap-1.5 font-normal">
                            <span
                                v-if="bucket.color"
                                class="size-2.5 rounded-full"
                                :class="COLOR_BG[bucket.color]"
                            />
                            {{ bucket.name }}
                        </Label>
                    </div>
                </div>
            </div>

            <!-- Tag selection -->
            <div v-if="tags.length" class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tags</p>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <div
                        v-for="tag in tags"
                        :key="tag.id"
                        class="flex items-center gap-2"
                    >
                        <Checkbox
                            :id="`export-tag-${tag.id}`"
                            :model-value="selectedTagIds.has(tag.id)"
                            @update:model-value="toggleTag(tag.id, $event as boolean)"
                        />
                        <Label :for="`export-tag-${tag.id}`" class="flex cursor-pointer items-center gap-1.5 font-normal">
                            <span
                                v-if="tag.color"
                                class="size-2.5 rounded-full"
                                :class="COLOR_BG[tag.color]"
                            />
                            {{ tag.name }}
                        </Label>
                    </div>
                </div>
            </div>

            <!-- Private notes option -->
            <div class="flex items-center gap-2">
                <Checkbox id="export-includes-notes" v-model="includesNotes" />
                <Label for="export-includes-notes" class="cursor-pointer font-normal">
                    Private Notizen einschließen
                </Label>
            </div>

            <Button variant="outline" :disabled="exporting" @click="downloadExport">
                <Download class="mr-2 size-4" />
                {{ exporting ? 'Exportiere…' : 'Exportieren' }}
            </Button>
        </div>

        <hr class="border-border" />

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
            <span class="font-medium">{{ importResult.imported }} {{ importResult.imported === 1 ? 'Link' : 'Links' }} importiert</span>
            <span v-if="importResult.skipped > 0"> · {{ importResult.skipped }} übersprungen</span>
            <span v-if="importResult.hints > 0"> · {{ importResult.hints }} ähnliche URL{{ importResult.hints === 1 ? '' : 's' }} gefunden</span>
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
                        @change="selectedBucketId = Number(($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="bucket in buckets" :key="bucket.id" :value="bucket.id">
                            {{ bucket.name }}{{ bucket.is_inbox ? ' (Standard)' : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.bucket_id" />
                </div>

                <div class="space-y-1.5">
                    <Label for="file">Bookmark-Datei (.html)</Label>
                    <Input
                        id="file"
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

        <div class="space-y-3">
            <div>
                <h2 class="text-sm font-semibold">Quick-Add Bookmarklet</h2>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Ziehe den Button in deine Lesezeichen-Leiste oder kopiere den Code manuell.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    :href="bookmarkletCode"
                    class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium shadow-xs hover:bg-accent"
                    @click.prevent
                >
                    <BookmarkPlus class="size-4" />
                    Link speichern
                </a>
                <span class="text-xs text-muted-foreground">← In Lesezeichen-Leiste ziehen</span>
            </div>

            <div class="flex items-start gap-2">
                <code class="flex-1 overflow-x-auto rounded-md border bg-muted px-3 py-2 text-xs break-all select-all">{{ bookmarkletCode }}</code>
                <Button variant="outline" size="sm" class="shrink-0 gap-1.5" @click="copyBookmarklet">
                    <Copy class="size-3.5" />
                    {{ copied ? 'Kopiert!' : 'Kopieren' }}
                </Button>
            </div>
        </div>
    </div>
</template>
