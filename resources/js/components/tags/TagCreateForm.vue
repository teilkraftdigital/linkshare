<script setup lang="ts">
import { ref } from 'vue';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import { useToast } from '@/composables/useToast';
import ColorPalette from '@/components/shared/ColorPalette.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import { Form } from '@inertiajs/vue3';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import InputError from '@/components/shared/InputError.vue';
import Button from '@/components/ui/button/Button.vue';

const createColor = ref<string>('gray');
const createIsPublic = ref<boolean>(false);

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
                toast('Tag created', 'success');
            }
        "
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label for="tag-name">Name</Label>
                <Input
                    id="tag-name"
                    name="name"
                    placeholder="Tag name"
                    autocomplete="off"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="tag-description">Description</Label>
                <Textarea
                    id="tag-description"
                    name="description"
                    placeholder="Optional description"
                    class="resize-none"
                    rows="1"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex flex-col gap-2">
                <Label>Color</Label>
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
                <Label for="create-is-public">Public</Label>
                <InputError :message="errors.is_public" />
            </div>

            <Button type="submit" :disabled="processing" class="ml-auto">
                Add
            </Button>
        </div>
    </Form>
</template>
