import { createHeader, generateTwoColumns } from '../../../shared/PDF-functions.js';
import { generatePodmiotAdres } from './PodmiotAdres.js';
import { generateDaneIdentyfikacyjne } from './PodmiotDaneIdentyfikacyjne.js';
export function generatePrzewoznik(przewoznik) {
    if (!przewoznik) {
        return [];
    }
    return [
        ...createHeader('Przewoźnik'),
        [
            generateTwoColumns(generateDaneIdentyfikacyjne(przewoznik.DaneIdentyfikacyjne), generatePodmiotAdres(przewoznik.AdresPrzewoznika, 'Adres przewoźnika', true, [0, 0, 0, 0]), [0, 0, 0, 8]),
        ],
    ];
}
//# sourceMappingURL=Przewoznik.js.map