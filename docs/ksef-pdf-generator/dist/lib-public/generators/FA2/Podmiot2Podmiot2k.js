import { createHeader, createLabelText, formatText, generateColumns, getTable, hasValue, verticalSpacing, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { generateAdres } from './Adres.js';
import { generateDaneIdentyfikacyjneTPodmiot1Dto } from './PodmiotDaneIdentyfikacyjneTPodmiot1Dto.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
export function generatePodmiot2Podmiot2K(podmiot2, podmiot2K) {
    const result = createHeader('Nabywca');
    let firstColumn = [];
    let secondColumn = [];
    firstColumn.push(createHeader('Dane identyfikacyjne'), createLabelText('Numer EORI: ', podmiot2.NrEORI));
    if (podmiot2.DaneIdentyfikacyjne) {
        firstColumn.push(...generateDaneIdentyfikacyjneTPodmiot1Dto(podmiot2.DaneIdentyfikacyjne));
    }
    if (podmiot2.DaneKontaktowe) {
        firstColumn.push(generateDaneKontaktowe(getTable(podmiot2.DaneKontaktowe)));
    }
    if (podmiot2.NrKlienta) {
        firstColumn.push(createLabelText('Numer klienta: ', podmiot2.NrKlienta));
    }
    if (firstColumn.length) {
        result.push({
            columns: [firstColumn, []],
            columnGap: 20,
        });
    }
    firstColumn = generateCorrectedContent(podmiot2K, 'Treść korygowana');
    secondColumn = generateCorrectedContent(podmiot2, 'Treść korygująca');
    if (podmiot2.AdresKoresp) {
        secondColumn.push(formatText('Adres do korespondencji', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot2.AdresKoresp));
    }
    if (firstColumn.length || secondColumn.length) {
        result.push(generateColumns([firstColumn, secondColumn]));
    }
    if (result.length) {
        result.push(verticalSpacing(1));
    }
    return result;
}
export function generateCorrectedContent(podmiot, header) {
    const result = [];
    result.push(createHeader(header));
    if (hasValue(podmiot.IDNabywcy)) {
        result.push(createLabelText('Identyfikator nabywcy: ', podmiot.IDNabywcy));
    }
    if (podmiot.DaneIdentyfikacyjne) {
        result.push(...generateDaneIdentyfikacyjneTPodmiot1Dto(podmiot.DaneIdentyfikacyjne));
    }
    if (podmiot.Adres) {
        result.push(formatText('Adres', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot.Adres));
    }
    return result;
}
//# sourceMappingURL=Podmiot2Podmiot2k.js.map