<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Label from '@/components/ui/label/Label.vue';
import { COLOR_BG } from '@/lib/colors';
import type { Tag } from '@/types/dashboard';

const { t } = useI18n();

const props = defineProps<{
    open: boolean;
    tag: Tag | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [childIds: number[]];
}>();

const selectedChildIds = ref<number[]>([]);

// Pre-select all children whenever the modal opens or tag changes
watch(
    () => [props.open, props.tag],
    () => {
        if (props.open && props.tag?.children) {
            selectedChildIds.value = props.tag.children.map((c) => c.id);
        }
    },
    { immediate: true },
);

function toggleChild(childId: number, checked: boolean) {
    if (checked) {
        selectedChildIds.value = [...selectedChildIds.value, childId];
    } else {
        selectedChildIds.value = selectedChildIds.value.filter((id) => id !== childId);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('tags.restoreWithChildren.title', { name: tag?.name }) }}</DialogTitle>
                <DialogDescription>{{ t('tags.restoreWithChildren.description') }}</DialogDescription>
            </DialogHeader>

            <ul class="flex flex-col gap-2">
                <li
                    v-for="child in tag?.children ?? []"
                    :key="child.id"
                    class="flex items-center gap-3"
                >
                    <Checkbox
                        :id="`restore-child-${child.id}`"
                        :model-value="selectedChildIds.includes(child.id)"
                        @update:model-value="toggleChild(child.id, $event as boolean)"
                    />
                    <span
                        class="size-3 shrink-0 rounded-full"
                        :class="COLOR_BG[child.color] ?? 'bg-gray-400'"
                    />
                    <Label :for="`restore-child-${child.id}`" class="cursor-pointer font-normal">
                        {{ child.name }}
                    </Label>
                </li>
            </ul>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('common.cancel') }}
                </Button>
                <Button @click="emit('confirm', selectedChildIds)">
                    {{ t('tags.restoreWithChildren.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
