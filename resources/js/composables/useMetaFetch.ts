import { ref } from 'vue';
import MetaFetchController from '@/actions/App/Http/Controllers/Dashboard/MetaFetchController';

type MetaResult = {
    title: string | null;
    description: string | null;
};

type UseMetaFetch = {
    fetching: Readonly<ReturnType<typeof ref<boolean>>>;
    failed: Readonly<ReturnType<typeof ref<boolean>>>;
    fetch: (url: string) => void;
};

export function useMetaFetch(onSuccess: (meta: MetaResult) => void): UseMetaFetch {
    const fetching = ref(false);
    const failed = ref(false);

    let timer: ReturnType<typeof setTimeout> | null = null;

    function fetch(url: string) {
        if (timer) {
            clearTimeout(timer);
        }

        failed.value = false;

        if (!url) {
            return;
        }

        timer = setTimeout(async () => {
            fetching.value = true;

            try {
                const response = await window.fetch(MetaFetchController.url(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ url }),
                });

                if (!response.ok) {
                    failed.value = true;
                    return;
                }

                const meta: MetaResult = await response.json();
                onSuccess(meta);
            } catch {
                failed.value = true;
            } finally {
                fetching.value = false;
            }
        }, 500);
    }

    return { fetching, failed, fetch };
}
