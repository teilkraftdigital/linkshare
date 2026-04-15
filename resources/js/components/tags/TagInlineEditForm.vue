<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import InputError from '@/components/shared/InputError.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import type { Tag } from '@/types/dashboard';
import Button from '../ui/button/Button.vue';

const { t } = useI18n();

const props = defineProps<{
    tag: Tag;
    isChild?: boolean;
}>();

const page = usePage<{ rootTags?: Tag[] }>();

const event = defineEmits<{
    (e: 'cancel'): void;
}>();

function cancelEdit() {
    event('cancel');
}

const editName = defineModel<Tag['name']>('name', { required: true });
const editDescription = defineModel<Tag['description']>('description', { required: false });
const editColor = defineModel<Tag['color']>('color', { required: true });
const editIsPublic = defineModel<Tag['is_public']>('is_public', { required: true });
const editParentId = defineModel<Tag['parent_id']>('parent_id', { required: false });

const hasChildren = (props.tag.children?.length ?? 0) > 0;

const availableParents = computed(() =>
    (page.props.rootTags ?? []).filter((t) => t.id !== props.tag.id),
);

const selectedParent = computed(() =>
    availableParents.value.find((t) => t.id === editParentId.value) ?? null,
);

const isPublicWarning = computed(() => {
    if (!selectedParent.value) return null;
    if (selectedParent.value.is_public && !props.tag.is_public) {
        return t('tags.form.isPublicWillBePublic', { parent: selectedParent.value.name });
    }
    if (!selectedParent.value.is_public && props.tag.is_public) {
        return t('tags.form.isPublicWillBePrivate', { parent: selectedParent.value.name });
    }
    return null;
});

const colorIsInherited = computed(() => !!editParentId.value);
</script>

<template>
    <Form
        v-bind="TagController.update.form(tag)"
        :options="{ preserveScroll: true }"
        class="flex flex-col gap-4 p-4"
        :class="isChild ? 'pl-8' : ''"
        v-slot="{ errors, processing }"
        @success="cancelEdit"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label :for="`tag-name-${tag.id}`" class="sr-only">{{ t('fields.name') }}</Label>
                <Input
                    :id="`tag-name-${tag.id}`"
                    name="name"
                    :placeholder="t('tags.form.namePlaceholder')"
                    autocomplete="off"
                    v-model="editName"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`tag-desc-${tag.id}`" class="sr-only">
                    {{ t('tags.form.descriptionLabel') }}
                </Label>
                <Textarea
                    :id="`tag-desc-${tag.id}`"
                    name="description"
                    :placeholder="t('tags.form.descriptionPlaceholder')"
                    class="resize-none"
                    rows="1"
                    v-model="editDescription"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <!-- Parent selector (not shown for child tags since we don't support nesting) -->
        <div v-if="!isChild" class="flex flex-col gap-2">
            <Label :for="`tag-parent-${tag.id}`">{{ t('tags.form.parentLabel') }}</Label>
            <input type="hidden" name="parent_id" :value="editParentId ?? ''" />
            <select
                :id="`tag-parent-${tag.id}`"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs focus:border-ring focus:ring-[3px] focus:ring-ring/50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="hasChildren"
                :value="editParentId ?? ''"
                @change="editParentId = ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null"
            >
                <option value="">{{ t('tags.form.noParent') }}</option>
                <option
                    v-for="parent in availableParents"
                    :key="parent.id"
                    :value="parent.id"
                >
                    {{ parent.name }}
                </option>
            </select>
            <p v-if="hasChildren" class="text-xs text-muted-foreground">
                {{ t('tags.form.hasChildrenHint') }}
            </p>
            <p v-if="isPublicWarning" class="text-xs text-amber-600 dark:text-amber-400">
                {{ isPublicWarning }}
            </p>
            <InputError :message="errors.parent_id" />
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex flex-col gap-2">
                <input type="hidden" name="color" :value="editColor" />
                <ColorPalette
                    v-model="editColor"
                    :disabled="colorIsInherited"
                />
                <p v-if="colorIsInherited" class="text-xs text-muted-foreground">
                    {{ t('tags.form.colorInherited') }}
                </p>
                <InputError :message="errors.color" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="hidden"
                    name="is_public"
                    :value="editIsPublic ? '1' : '0'"
                />
                <Checkbox
                    :id="`edit-is-public-${tag.id}`"
                    v-model="editIsPublic"
                    :disabled="colorIsInherited"
                />
                <Label :for="`edit-is-public-${tag.id}`">{{ t('tags.form.isPublicLabel') }}</Label>
                <InputError :message="errors.is_public" />
            </div>

            <div class="ml-auto flex gap-2">
                <Button type="submit" size="sm" :disabled="processing">
                    {{ t('common.save') }}
                </Button>
                <Button type="button" size="sm" variant="outline" @click="cancelEdit">
                    {{ t('common.cancel') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
