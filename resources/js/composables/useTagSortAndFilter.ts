import { computed, ref, type ComputedRef } from 'vue';
import type { DashboardTag, SortKey } from '@/types/dashboard';

export function useTagSortAndFilter(tags: ComputedRef<DashboardTag[]>) {
    const sortKey = ref<SortKey>('updated_at');
    const search = ref('');

    function sortedAndFiltered(
        tags: DashboardTag[],
        sort: SortKey,
        search: string,
    ): DashboardTag[] {
        let result = tags;

        if (search.trim()) {
            const q = search.trim().toLowerCase();
            result = result.filter((t) => t.name.toLowerCase().includes(q));
        }

        return [...result].sort((a, b) => {
            if (sort === 'name') {
                return a.name.localeCompare(b.name);
            }
            if (sort === 'links_count') {
                return b.links_count - a.links_count;
            }
            // updated_at desc
            return (
                new Date(b.updated_at).getTime() -
                new Date(a.updated_at).getTime()
            );
        });
    }
    const sorted = computed(() =>
        sortedAndFiltered(tags.value, sortKey.value, search.value),
    );

    return {
        sorted,
        sortKey,
        search,
    };
}
