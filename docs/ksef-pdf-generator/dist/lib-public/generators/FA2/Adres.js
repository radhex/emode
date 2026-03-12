import { createLabelText, formatText, getKraj } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateAdres(adres) {
    const result = [];
    if (adres?.AdresL1) {
        result.push(formatText(adres.AdresL1._text, FormatTyp.Value));
    }
    if (adres?.AdresL2) {
        result.push(formatText(adres.AdresL2._text, FormatTyp.Value));
    }
    if (adres?.KodKraju) {
        result.push(formatText(getKraj(adres.KodKraju._text ?? ''), FormatTyp.Value));
    }
    result.push(...createLabelText('GLN: ', adres.GLN));
    return result;
}
//# sourceMappingURL=Adres.js.map