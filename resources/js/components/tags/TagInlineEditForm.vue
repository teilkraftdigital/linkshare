<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import InputError from '@/components/shared/InputError.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import type { Tag } from '@/types/dashboard';
import Button from '../ui/button/Button.vue';

defineProps<{
    tag: Tag;
}>();

const event = defineEmits<{
    (e: 'cancel'): void;
}>();

function cancelEdit() {
    event('cancel');
}

const editName = defineModel<Tag['name']>('name', {
    required: true,
});

const editDescription = defineModel<Tag['description']>('description', {
    required: false,
});

const editColor = defineModel<Tag['color']>('color', {
    required: true,
});

const editIsPublic = defineModel<Tag['is_public']>('is_public', {
    required: true,
});
</script>

<template>
    <Form
        v-bind="TagController.update.form(tag)"
        :options="{ preserveScroll: true }"
        class="flex flex-col gap-4"
        v-slot="{ errors, processing }"
        @success="cancelEdit"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label :for="`tag-name-${tag.id}`" class="sr-only">{{ $t('fields.name') }}</Label>
                <Input
                    :id="`tag-name-${tag.id}`"
                    name="name"
                    :placeholder="$t('tags.form.namePlaceholder')"
                    autocomplete="off"
                    v-model="editName"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label :for="`tag-desc-${tag.id}`" class="sr-only">
                    {{ $t('tags.form.descriptionLabel') }}
                </Label>
                <Textarea
                    :id="`tag-desc-${tag.id}`"
                    name="description"
                    :placeholder="$t('tags.form.descriptionPlaceholder')"
                    class="resize-none"
                    rows="1"
                    v-model="editDescription"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex flex-col gap-2">
                <input type="hidden" name="color" :value="editColor" />
                <ColorPalette v-model="editColor" />
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
                />
                <Label :for="`edit-is-public-${tag.id}`">{{ $t('tags.form.isPublicLabel') }}</Label>
                <InputError :message="errors.is_public" />
            </div>

            <div class="ml-auto flex gap-2">
                <Button type="submit" size="sm" :disabled="processing">
                    {{ $t('common.save') }}
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="cancelEdit"
                >
                    {{ $t('common.cancel') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
