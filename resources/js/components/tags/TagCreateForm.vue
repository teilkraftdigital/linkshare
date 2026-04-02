<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import InputError from '@/components/shared/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import { useToast } from '@/composables/useToast';

const createColor = ref<string>('gray');
const createIsPublic = ref<boolean>(false);

const { t } = useI18n();
const { toast } = useToast();
</script>

<template>
    <Form
        v-bind="TagController.store.form()"
        :options="{ preserveScroll: true }"
        class="flex flex-col gap-4 rounded-lg border p-4"
        v-slot="{ errors, processing }"
        @success="
            () => {
                createColor = 'gray';
                createIsPublic = false;
                toast(t('tags.created'), 'success');
            }
        "
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label for="tag-name">{{ $t('fields.name') }}</Label>
                <Input
                    id="tag-name"
                    name="name"
                    :placeholder="$t('tags.form.namePlaceholder')"
                    autocomplete="off"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="tag-description">{{ $t('tags.form.descriptionLabel') }}</Label>
                <Textarea
                    id="tag-description"
                    name="description"
                    :placeholder="$t('tags.form.descriptionPlaceholder')"
                    class="resize-none"
                    rows="1"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex flex-col gap-2">
                <Label>{{ $t('tags.form.colorLabel') }}</Label>
                <input type="hidden" name="color" :value="createColor" />
                <ColorPalette v-model="createColor" />
                <InputError :message="errors.color" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="hidden"
                    name="is_public"
                    :value="createIsPublic ? '1' : '0'"
                />
                <Checkbox id="create-is-public" v-model="createIsPublic" />
                <Label for="create-is-public">{{ $t('tags.form.isPublicLabel') }}</Label>
                <InputError :message="errors.is_public" />
            </div>

            <Button type="submit" :disabled="processing" class="ml-auto">
                {{ $t('tags.form.addButton') }}
            </Button>
        </div>
    </Form>
</template>
