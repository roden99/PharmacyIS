import { computed } from 'vue';

export function useFieldGroupSkeleton(colSpans: number[]) {
    // Builds an array of column counts per row, e.g. [12,12,4,4,4,12,12] → [1,1,3,1,1]
    const skeletonLayout = computed(() => {
        const rows: number[] = [];
        let rowCols = 0;
        let used = 0;
        for (const span of colSpans) {
            used += span;
            rowCols++;
            if (used >= 12) {
                rows.push(rowCols);
                rowCols = 0;
                used = 0;
            }
        }
        if (rowCols > 0) rows.push(rowCols);
        return rows;
    });

    return { skeletonLayout };
}
