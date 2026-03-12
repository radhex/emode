import { createLabelText, createLabelTextArray, formatText } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateDaneIdentyfikacyjneTPodmiot2Dto(daneIdentyfikacyjne) {
    const result = [];
    result.push(createLabelText('NIP: ', daneIdentyfikacyjne.NIP));
    if (daneIdentyfikacyjne.NrVatUE?._text) {
        result.push(createLabelTextArray([
            { value: 'Numer VAT-UE: ', formatTyp: FormatTyp.Label },
            { value: daneIdentyfikacyjne.KodUE, formatTyp: FormatTyp.Value },
            { value: ' ' },
            { value: daneIdentyfikacyjne.NrVatUE, formatTyp: FormatTyp.Value },
        ]));
    }
    if (daneIdentyfikacyjne.KodKraju?._text) {
        result.push(createLabelTextArray([
            { value: 'Identyfikator podatkowy inny: ', formatTyp: FormatTyp.Label },
            { value: daneIdentyfikacyjne.KodKraju, formatTyp: FormatTyp.Value },
            { value: ' ' },
            { value: daneIdentyfikacyjne.NrID, formatTyp: FormatTyp.Value },
        ]));
    }
    if (daneIdentyfikacyjne.BrakID?._text === '1') {
        result.push(formatText('Brak identyfikatora', FormatTyp.Label));
    }
    result.push(createLabelText('Nazwa: ', daneIdentyfikacyjne.Nazwa));
    return result;
}
//# sourceMappingURL=PodmiotDaneIdentyfikacyjneTPodmiot2Dto.js.map