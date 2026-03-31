<script setup lang="ts">
import { Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import JsonImportController from '@/actions/App/Http/Controllers/Dashboard/JsonImportController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import type { Bucket, Tag } from '@/types/dashboard';
import JsonImportModal from './JsonImportModal.vue';

type ParsePreview = {
    buckets: Bucket[];
    tags: Tag[];
    link_count: number;
};

const { toast } = useToast();

const jsonFile = ref<File | null>(null);
const jsonParseError = ref<string | null>(null);
const jsonParsing = ref(false);
const jsonImporting = ref(false);
const jsonImportModalOpen = ref(false);
const jsonPreview = ref<ParsePreview | null>(null);

function onModalClose() {
    jsonImportModalOpen.value = false;
    jsonPreview.value = null;
}

async function parseJsonFile() {
    if (!jsonFile.value) {
return;
}

    jsonParsing.value = true;
    jsonParseError.value = null;

    try {
        const csrfToken =
            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                ?.content ?? '';
        const formData = new FormData();
        formData.append('file', jsonFile.value);

        const response = await fetch(JsonImportController.parse.url(), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            jsonParseError.value = data.error ?? data.message ?? 'Fehler beim Parsen der Datei.';

            return;
        }

        jsonPreview.value = data as ParsePreview;
        jsonImportModalOpen.value = true;
    } catch {
        jsonParseError.value = 'Fehler beim Hochladen der Datei.';
    } finally {
        jsonParsing.value = false;
    }
}

async function executeJsonImport(bucketNames: string[], tagNames: string[]) {
    if (!jsonFile.value) {
return;
}

    jsonImporting.value = true;
    jsonImportModalOpen.value = false;
    jsonPreview.value = null;

    const csrfToken =
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
            ?.content ?? '';
    const formData = new FormData();
    formData.append('file', jsonFile.value);
    bucketNames.forEach((n) => formData.append('bucket_names[]', n));
    tagNames.forEach((n) => formData.append('tag_names[]', n));

    try {
        const response = await fetch(JsonImportController.store.url(), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: formData,
        });

        if (response.ok) {
            const result = await response.json();
            const { imported, skipped, buckets_created, tags_created } = result;

            let message = `${imported} ${imported === 1 ? 'Link' : 'Links'} importiert`;

            if (skipped > 0) {
message += ` · ${skipped} übersprungen`;
}

            if (buckets_created > 0) {
message += ` · ${buckets_created} ${buckets_created === 1 ? 'Bucket' : 'Buckets'} erstellt`;
}

            if (tags_created > 0) {
message += ` · ${tags_created} ${tags_created === 1 ? 'Tag' : 'Tags'} erstellt`;
}

            toast(message, 'success');
            jsonFile.value = null;
        } else {
            jsonParseError.value = 'Fehler beim Importieren der Datei.';
        }
    } catch {
        jsonParseError.value = 'Fehler beim Importieren der Datei.';
    } finally {
        jsonImporting.value = false;
    }
}
</script>

<template>
    <JsonImportModal
        :open="jsonImportModalOpen"
        :preview="jsonPreview"
        :importing="jsonImporting"
        @update:open="onModalClose"
        @confirm="executeJsonImport"
    />

    <div>
        <h2 class="text-sm font-semibold">Linkshare JSON importieren</h2>
        <p class="mt-0.5 text-sm text-muted-foreground">
            Importiere eine zuvor exportierte Linkshare JSON-Datei.
        </p>
    </div>

    <div class="space-y-4">
        <div class="space-y-1.5">
            <Label for="json-file">JSON-Datei (.json)</Label>
            <Input
                id="json-file"
                type="file"
                accept=".json"
                class="cursor-pointer"
                @change="
                    jsonFile =
                        ($event.target as HTMLInputElement).files?.[0] ?? null;
                    jsonParseError = null;
                "
            />
            <p v-if="jsonParseError" class="text-sm text-destructive">
                {{ jsonParseError }}
            </p>
        </div>

        <Button
            variant="outline"
            :disabled="!jsonFile || jsonParsing || jsonImporting"
            @click="parseJsonFile"
        >
            <Upload class="mr-2 size-4" />
            {{ jsonImporting ? 'Importiere…' : jsonParsing ? 'Lese Datei…' : 'Datei analysieren' }}
        </Button>
    </div>
</template>
