import { createLabelText, formatText, getTable, hasValue, verticalSpacing, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generatePodmiotUpowaznionyDaneKontaktowe(daneKontaktoweSource) {
    if (!daneKontaktoweSource) {
        return [];
    }
    const result = [formatText('Dane kontaktowe', FormatTyp.Description)];
    const daneKontaktowe = getTable(daneKontaktoweSource);
    if (daneKontaktowe.length === 0) {
        return [];
    }
    daneKontaktowe.forEach((kontakt) => {
        if (hasValue(kontakt.EmailPU)) {
            result.push(createLabelText('E-mail: ', kontakt.EmailPU));
        }
        if (hasValue(kontakt.TelefonPU)) {
            result.push(createLabelText('Tel.: ', kontakt.TelefonPU));
        }
        result.push(verticalSpacing(1));
    });
    return result;
}
//# sourceMappingURL=PodmiotUpowaznionyDaneKontaktowe.js.map