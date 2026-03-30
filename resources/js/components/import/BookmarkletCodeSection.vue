<script setup lang="ts">
import { BookmarkPlus, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import QuickAddController from '@/actions/App/Http/Controllers/Dashboard/QuickAddController';
import { Button } from '@/components/ui/button';

const page = usePage();

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
    <div class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold">Quick-Add Bookmarklet</h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                Ziehe den Button in deine Lesezeichen-Leiste oder kopiere den
                Code manuell.
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
            <span class="text-xs text-muted-foreground"
                >← In Lesezeichen-Leiste ziehen</span
            >
        </div>

        <div class="flex items-start gap-2">
            <code
                class="flex-1 overflow-x-auto rounded-md border bg-muted px-3 py-2 text-xs break-all select-all"
                >{{ bookmarkletCode }}</code
            >
            <Button
                variant="outline"
                size="sm"
                class="shrink-0 gap-1.5"
                @click="copyBookmarklet"
            >
                <Copy class="size-3.5" />
                {{ copied ? 'Kopiert!' : 'Kopieren' }}
            </Button>
        </div>
    </div>
</template>
