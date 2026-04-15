<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/shared/InputError.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { i18n } from '@/i18n';
import { store } from '@/routes/password/confirm';

const { t } = useI18n();

defineOptions({
    layout: {
        title: i18n.global.t('auth.confirmPassword.title'),
        description: i18n.global.t('auth.confirmPassword.description'),
    },
});
</script>

<template>
    <Head :title="t('auth.confirmPassword.pageTitle')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">{{ t('fields.password') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.confirmPassword.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
