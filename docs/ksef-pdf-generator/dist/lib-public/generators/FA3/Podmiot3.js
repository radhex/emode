import { createHeader, createLabelText, formatText, generateLine, generateTwoColumns, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { generateDaneIdentyfikacyjneTPodmiot3Dto } from './PodmiotDaneIdentyfikacyjneTPodmiot3Dto.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
import { getRolaString } from '../../../shared/generators/common/functions.js';
import { generateAdres } from '../FA2/Adres.js';
export function generatePodmiot3(podmiot, index) {
    const result = [];
    result.push(generateLine());
    const column1 = [
        ...createHeader(`Podmiot inny ${index + 1}`),
        createLabelText('Identyfikator nabywcy: ', podmiot.IDNabywcy),
        createLabelText('Numer EORI: ', podmiot.NrEORI),
        ...generateDaneIdentyfikacyjneTPodmiot3Dto(podmiot.DaneIdentyfikacyjne),
        createLabelText('Rola: ', getRolaString(podmiot.Rola, 3)),
        createLabelText('Rola inna: ', podmiot.OpisRoli),
        createLabelText('Udział: ', podmiot.Udzial, [FormatTyp.Percentage]),
    ];
    const column2 = [];
    if (podmiot.Adres) {
        column2.push(formatText('Adres', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot.Adres));
    }
    if (podmiot.AdresKoresp) {
        column2.push(formatText('Adres do korespondencji', [FormatTyp.Label, FormatTyp.LabelMargin]), ...generateAdres(podmiot.AdresKoresp));
    }
    if (podmiot.DaneKontaktowe) {
        column2.push(formatText('Dane kontaktowe', [FormatTyp.Label, FormatTyp.LabelMargin]), ...generateDaneKontaktowe(podmiot.DaneKontaktowe), createLabelText('Numer klienta: ', podmiot.NrKlienta));
    }
    result.push(generateTwoColumns(column1, column2));
    return result;
}
//# sourceMappingURL=Podmiot3.js.map