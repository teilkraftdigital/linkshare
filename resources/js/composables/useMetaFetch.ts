import { ref } from 'vue';
import MetaFetchController from '@/actions/App/Http/Controllers/Dashboard/MetaFetchController';

type MetaResult = {
    title: string | null;
    description: string | null;
    favicon_url: string | null;
};

type UseMetaFetch = {
    fetching: Readonly<ReturnType<typeof ref<boolean>>>;
    failed: Readonly<ReturnType<typeof ref<boolean>>>;
    faviconUrl: Readonly<ReturnType<typeof ref<string | null>>>;
    fetch: (url: string) => void;
    reset: () => void;
};

export function useMetaFetch(onSuccess: (meta: MetaResult) => void): UseMetaFetch {
    const fetching = ref(false);
    const failed = ref(false);
    const faviconUrl = ref<string | null>(null);

    let timer: ReturnType<typeof setTimeout> | null = null;

    function fetch(url: string) {
        if (timer) {
            clearTimeout(timer);
        }

        failed.value = false;

        if (!url) {
            faviconUrl.value = null;
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
                faviconUrl.value = meta.favicon_url;
                onSuccess(meta);
            } catch {
                failed.value = true;
            } finally {
                fetching.value = false;
            }
        }, 500);
    }

    function reset() {
        faviconUrl.value = null;
        failed.value = false;
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    return { fetching, failed, faviconUrl, fetch, reset };
}
