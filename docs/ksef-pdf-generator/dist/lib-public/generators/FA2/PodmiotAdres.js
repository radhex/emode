import { createHeader, createSubHeader } from '../../../shared/PDF-functions.js';
import { generateAdres } from './Adres.js';
export function generatePodmiotAdres(podmiotAdres, headerTitle = 'Adres', isSubheader = false, headerMargin) {
    if (!podmiotAdres) {
        return [];
    }
    return [
        ...(isSubheader ? createSubHeader(headerTitle, headerMargin) : createHeader(headerTitle, headerMargin)),
        ...generateAdres(podmiotAdres),
    ];
}
//# sourceMappingURL=PodmiotAdres.js.map