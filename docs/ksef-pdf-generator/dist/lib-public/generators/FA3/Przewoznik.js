import { createHeader, generateTwoColumns } from '../../../shared/PDF-functions.js';
import { generatePodmiotAdres } from './PodmiotAdres.js';
import { generateDaneIdentyfikacyjneTPodmiot2Dto } from './PodmiotDaneIdentyfikacyjneTPodmiot2Dto.js';
export function generatePrzewoznik(przewoznik) {
    if (!przewoznik) {
        return [];
    }
    return [
        ...createHeader('Przewoźnik'),
        [
            generateTwoColumns(generateDaneIdentyfikacyjneTPodmiot2Dto(przewoznik.DaneIdentyfikacyjne), generatePodmiotAdres(przewoznik.AdresPrzewoznika, 'Adres przewoźnika', true, [0, 0, 0, 0]), [0, 0, 0, 8]),
        ],
    ];
}
//# sourceMappingURL=Przewoznik.js.map