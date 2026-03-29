<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { computed } from 'vue';
import { create as importRoute, store as storeRoute } from '@/routes/dashboard/import';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Import',
                href: importRoute(),
            },
        ],
    },
});

const page = usePage();
const importCount = computed(() => page.props.flash?.import_count ?? null);
</script>

<template>
    <Head title="Import" />

    <div class="space-y-6">
        <Heading title="Import" description="Importiere Browser-Bookmarks im Netscape HTML Format (Chrome, Firefox, Safari)." />

        <div v-if="importCount !== null" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ importCount }} {{ importCount === 1 ? 'Link' : 'Links' }} erfolgreich importiert.
        </div>

        <Form
            :action="storeRoute().url"
            method="post"
            enctype="multipart/form-data"
            #default="{ errors, processing }"
        >
            <div class="space-y-4">
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
    </div>
</template>
