import { createLabelText, getTable } from '../../../shared/PDF-functions.js';
export function generateDaneKontaktowe(daneKontaktowe) {
    return getTable(daneKontaktowe)?.map((daneKontaktowe) => {
        return [
            createLabelText('E-mail: ', daneKontaktowe.Email),
            createLabelText('Tel.: ', daneKontaktowe.Telefon),
        ];
    });
}
//# sourceMappingURL=PodmiotDaneKontaktowe.js.map