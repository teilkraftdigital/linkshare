import { ref } from 'vue';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import { useToast } from '@/composables/useToast';
import type { Tag } from '@/types/dashboard';

export function useTagCreate() {
    const { toast } = useToast();
    const createError = ref<string | undefined>(undefined);

    async function createTag(name: string, parentId?: number): Promise<Tag | null> {
        createError.value = undefined;

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        const body: Record<string, unknown> = { name, color: 'gray', is_public: false };
        if (parentId !== undefined) {
            body.parent_id = parentId;
        }

        try {
            const response = await fetch(TagController.store.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });

            const data = await response.json();

            if (response.status !== 201) {
                const message =
                    (data.errors?.name as string[] | undefined)?.[0] ??
                    (data.message as string | undefined) ??
                    'Tag konnte nicht erstellt werden';
                createError.value = message;
                toast(message, 'destructive');

                return null;
            }

            toast(`Tag „${(data as Tag).name}" erstellt`, 'success');

            return data as Tag;
        } catch {
            const message = 'Tag konnte nicht erstellt werden';
            createError.value = message;
            toast(message, 'destructive');

            return null;
        }
    }

    return { createError, createTag };
}
