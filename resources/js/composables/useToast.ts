import { ref } from 'vue';

type ToastVariant = 'default' | 'success' | 'destructive';

type Toast = {
    id: number;
    title: string;
    variant: ToastVariant;
};

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
    function toast(title: string, variant: ToastVariant = 'default') {
        const id = ++nextId;
        toasts.value.push({ id, title, variant });
    }

    function dismiss(id: number) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    return { toasts, toast, dismiss };
}
