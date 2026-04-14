<script setup lang="ts">
import { X } from 'lucide-vue-next';
import {
    ToastClose,
    ToastDescription,
    ToastProvider,
    ToastRoot,
    ToastViewport,
} from 'reka-ui';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const { toasts, dismiss } = useToast();
</script>

<template>
    <ToastProvider :duration="4000">
        <ToastRoot
            v-for="toast in toasts"
            :key="toast.id"
            :open="true"
            class="flex w-full items-center justify-between gap-3 overflow-hidden rounded-lg border p-4 shadow-lg transition-all data-[state=closed]:animate-out data-[state=closed]:fade-out-80 data-[state=closed]:slide-out-to-right-full data-[state=open]:animate-in data-[state=open]:slide-in-from-bottom-full data-[swipe=end]:animate-out"
            :class="{
                'border-border bg-background text-foreground':
                    toast.variant === 'default',
                'border-green-200 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100':
                    toast.variant === 'success',
                'border-destructive/30 bg-destructive text-destructive-foreground':
                    toast.variant === 'destructive',
            }"
            @update:open="
                (open) => {
                    if (!open) dismiss(toast.id);
                }
            "
        >
            <ToastDescription class="text-sm font-medium">
                {{ toast.title }}
            </ToastDescription>

            <ToastClose
                class="shrink-0 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:outline-none"
                @click="dismiss(toast.id)"
            >
                <X class="size-4" />
                <span class="sr-only">{{ t('common.close') }}</span>
            </ToastClose>
        </ToastRoot>

        <ToastViewport
            class="fixed right-4 bottom-4 z-50 flex w-full max-w-sm flex-col gap-2"
        />
    </ToastProvider>
</template>
