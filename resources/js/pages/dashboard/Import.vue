<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { BookmarkPlus, Copy, Download, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    create as importRoute,
    store as storeRoute,
} from '@/routes/dashboard/import';
import QuickAddController from '@/actions/App/Http/Controllers/Dashboard/QuickAddController';
import ExportModal from '@/components/ExportModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
const exportModalOpen = ref(false);

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
</script>

<template>
    <Head title="Import & Export" />

    <ExportModal
        v-model:open="exportModalOpen"
        :buckets="buckets"
        :tags="tags"
    />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Import & Export"
            description="Exportiere oder importiere deine Links, Buckets und Tags."
        />

        <!-- Export section -->
        <div class="space-y-3">
            <div>
                <h2 class="text-sm font-semibold">Exportieren</h2>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Alle aktiven Links, Buckets und Tags als JSON-Datei herunterladen. Einträge im Papierkorb sind nicht enthalten.
                </p>
            </div>
            <Button variant="outline" @click="exportModalOpen = true">
                <Download class="mr-2 size-4" />
                Exportieren
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
