import { CalendarDate } from '@internationalized/date';

export function useDateFormatter() {
    const normalizeDate = (dateObj) => {
        if (!dateObj) return null;

        if (typeof dateObj === 'string') return dateObj;

        if (dateObj instanceof Date) {
            return dateObj.toISOString().slice(0, 10);
        }

        if (dateObj?._isAMomentObject) {
            return dateObj.format('YYYY-MM-DD');
        }

        if ('calendar' in dateObj && 'year' in dateObj) {
            return `${dateObj.year}-${String(dateObj.month).padStart(2, '0')}-${String(dateObj.day).padStart(2, '0')}`;
        }

        return null;
    };

    const reverseDate = (dateString) => {
        if (!dateString || typeof dateString !== 'string') return null;

        const [year, month, day] = dateString.split('-').map(Number);

        if (!year || !month || !day) return null;

        return new CalendarDate(year, month, day);
    };

    const formatToMMDDYYYY = (dateObj) => {
        let year, month, day;
        if (!dateObj) return null;

        if (typeof dateObj === 'string') {
            // Try to parse string in YYYY-MM-DD or similar
            const parts = dateObj.split('-');
            if (parts.length === 3) {
                [year, month, day] = parts;
            } else {
                return null;
            }
        } else if (dateObj instanceof Date) {
            year = dateObj.getFullYear();
            month = String(dateObj.getMonth() + 1).padStart(2, '0');
            day = String(dateObj.getDate()).padStart(2, '0');
        } else if (dateObj?._isAMomentObject) {
            year = dateObj.format('YYYY');
            month = dateObj.format('MM');
            day = dateObj.format('DD');
        } else if ('calendar' in dateObj && 'year' in dateObj) {
            year = dateObj.year;
            month = String(dateObj.month).padStart(2, '0');
            day = String(dateObj.day).padStart(2, '0');
        } else {
            return null;
        }
        return `${month}-${day}-${year}`;
    };

    return { normalizeDate, reverseDate, formatToMMDDYYYY };
}
