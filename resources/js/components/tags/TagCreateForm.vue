<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
import type { Tag } from '@/types/dashboard';

const props = defineProps<{
    rootTags: Tag[];
    prefillParentId?: number | null;
}>();

const { t } = useI18n();
const { toast } = useToast();

const createColor = ref<string>('gray');
const createIsPublic = ref<boolean>(false);
const createParentId = ref<number | null>(props.prefillParentId ?? null);

watch(
    () => props.prefillParentId,
    (newVal) => {
        createParentId.value = newVal ?? null;
    },
);

const selectedParent = computed(
    () => props.rootTags.find((t) => t.id === createParentId.value) ?? null,
);

const colorIsInherited = computed(() => !!createParentId.value);
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
                createParentId = prefillParentId ?? null;
                toast(t('tags.created'), 'success');
            }
        "
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label for="tag-name">{{ t('fields.name') }}</Label>
                <Input
                    id="tag-name"
                    name="name"
                    :placeholder="t('tags.form.namePlaceholder')"
                    autocomplete="off"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="tag-description">
                    {{ t('tags.form.descriptionLabel') }}
                </Label>
                <Textarea
                    id="tag-description"
                    name="description"
                    :placeholder="t('tags.form.descriptionPlaceholder')"
                    class="resize-none"
                    rows="1"
                />
                <InputError :message="errors.description" />
            </div>
        </div>

        <!-- Parent selector -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label for="tag-parent">{{ t('tags.form.parentLabel') }}</Label>
                <input
                    type="hidden"
                    name="parent_id"
                    :value="createParentId ?? ''"
                />
                <select
                    id="tag-parent"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs focus:border-ring focus:ring-[3px] focus:ring-ring/50 focus:outline-none"
                    :value="createParentId ?? ''"
                    @change="
                        createParentId = ($event.target as HTMLSelectElement)
                            .value
                            ? Number(($event.target as HTMLSelectElement).value)
                            : null
                    "
                >
                    <option value="">{{ t('tags.form.noParent') }}</option>
                    <option
                        v-for="parent in rootTags"
                        :key="parent.id"
                        :value="parent.id"
                    >
                        {{ parent.name }}
                    </option>
                </select>
                <InputError :message="errors.parent_id" />
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex flex-col gap-2">
                <Label>{{ t('tags.form.colorLabel') }}</Label>
                <input
                    type="hidden"
                    name="color"
                    :value="selectedParent ? selectedParent.color : createColor"
                />
                <ColorPalette
                    v-if="!colorIsInherited"
                    v-model="createColor"
                    :disabled="colorIsInherited"
                />
                <p v-else class="text-xs text-muted-foreground">
                    {{ t('tags.form.colorInherited') }}
                </p>
                <InputError :message="errors.color" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="hidden"
                    name="is_public"
                    :value="
                        (
                            selectedParent
                                ? selectedParent.is_public
                                : createIsPublic
                        )
                            ? '1'
                            : '0'
                    "
                />
                <Checkbox
                    id="create-is-public"
                    v-model="createIsPublic"
                    :disabled="colorIsInherited"
                />
                <Label for="create-is-public">
                    {{ t('tags.form.isPublicLabel') }}
                </Label>
                <InputError :message="errors.is_public" />
            </div>

            <Button type="submit" :disabled="processing" class="ml-auto">
                {{ t('tags.form.addButton') }}
            </Button>
        </div>
    </Form>
</template>
