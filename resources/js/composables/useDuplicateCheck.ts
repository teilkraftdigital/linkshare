import { ref } from 'vue';
import CheckDuplicateController from '@/actions/App/Http/Controllers/Dashboard/CheckDuplicateController';

type DuplicateResult = {
    exists: boolean;
    similar: boolean;
};

type UseDuplicateCheck = {
    exists: Readonly<ReturnType<typeof ref<boolean>>>;
    similar: Readonly<ReturnType<typeof ref<boolean>>>;
    check: (url: string) => void;
    reset: () => void;
};

export function useDuplicateCheck(): UseDuplicateCheck {
    const exists = ref(false);
    const similar = ref(false);

    let timer: ReturnType<typeof setTimeout> | null = null;

    function reset() {
        exists.value = false;
        similar.value = false;
    }

    function check(url: string) {
        if (timer) clearTimeout(timer);
        reset();

        if (!url) return;

        timer = setTimeout(async () => {
            try {
                const response = await window.fetch(CheckDuplicateController.url(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                                ?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ url }),
                });

                if (!response.ok) return;

                const result: DuplicateResult = await response.json();
                exists.value = result.exists;
                similar.value = result.similar;
            } catch {
                // silently ignore network errors — duplicate check is non-blocking
            }
        }, 500);
    }

    return { exists, similar, check, reset };
}
