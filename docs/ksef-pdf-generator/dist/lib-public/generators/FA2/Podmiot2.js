import { createHeader, createLabelText, formatText } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { generateAdres } from './Adres.js';
import { generateDaneIdentyfikacyjneTPodmiot2Dto } from './PodmiotDaneIdentyfikacyjneTPodmiot2Dto.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
export function generatePodmiot2(podmiot2) {
    const result = createHeader('Nabywca');
    result.push(createLabelText('Identyfikator nabywcy: ', podmiot2.IDNabywcy), createLabelText('Numer EORI: ', podmiot2.NrEORI));
    if (podmiot2.DaneIdentyfikacyjne) {
        result.push(...generateDaneIdentyfikacyjneTPodmiot2Dto(podmiot2.DaneIdentyfikacyjne));
    }
    if (podmiot2.Adres) {
        result.push(formatText('Adres', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot2.Adres));
    }
    if (podmiot2.AdresKoresp) {
        result.push(formatText('Adres do korespondencji', [FormatTyp.Label, FormatTyp.LabelMargin]), ...generateAdres(podmiot2.AdresKoresp));
    }
    if (podmiot2.DaneKontaktowe) {
        result.push(formatText('Dane kontaktowe', [FormatTyp.Label, FormatTyp.LabelMargin]), ...generateDaneKontaktowe(podmiot2.DaneKontaktowe), createLabelText('Numer klienta: ', podmiot2.NrKlienta));
    }
    return result;
}
//# sourceMappingURL=Podmiot2.js.map