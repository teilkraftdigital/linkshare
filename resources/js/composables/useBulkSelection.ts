import { computed, ref } from 'vue';

export function useBulkSelection() {
    const bulkMode = ref(false);
    const selectedIds = ref<Set<number>>(new Set());

    const selectedCount = computed(() => selectedIds.value.size);

    function toggleMode() {
        bulkMode.value = !bulkMode.value;
        if (!bulkMode.value) {
            clearSelection();
        }
    }

    function toggleId(id: number) {
        const next = new Set(selectedIds.value);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        selectedIds.value = next;
    }

    function selectAll(ids: number[]) {
        selectedIds.value = new Set(ids);
    }

    function clearSelection() {
        selectedIds.value = new Set();
    }

    function isSelected(id: number): boolean {
        return selectedIds.value.has(id);
    }

    function getSelectedIds(): number[] {
        return Array.from(selectedIds.value);
    }

    return {
        bulkMode,
        selectedIds,
        selectedCount,
        toggleMode,
        toggleId,
        selectAll,
        clearSelection,
        isSelected,
        getSelectedIds,
    };
}
