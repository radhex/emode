import { createHeader, createLabelText, formatText, generateColumns, getTable, verticalSpacing, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { generateAdres } from './Adres.js';
import { generateDaneIdentyfikacyjneTPodmiot1Dto } from './PodmiotDaneIdentyfikacyjneTPodmiot1Dto.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
export function generatePodmiot1Podmiot1K(podmiot1, podmiot1K) {
    const result = createHeader('Sprzedawca');
    let firstColumn = [];
    let secondColumn = [];
    firstColumn.push(createHeader('Dane identyfikacyjne'), createLabelText('Numer EORI: ', podmiot1.NrEORI));
    if (podmiot1.DaneIdentyfikacyjne) {
        firstColumn.push(...generateDaneIdentyfikacyjneTPodmiot1Dto(podmiot1.DaneIdentyfikacyjne));
    }
    if (podmiot1.DaneKontaktowe) {
        firstColumn.push(generateDaneKontaktowe(getTable(podmiot1.DaneKontaktowe)));
    }
    if (podmiot1.StatusInfoPodatnika) {
        firstColumn.push(createLabelText('Status podatnika: ', podmiot1.StatusInfoPodatnika));
    }
    if (firstColumn.length) {
        result.push({
            columns: [firstColumn, []],
            columnGap: 20,
        });
    }
    firstColumn = generateCorrectedContent(podmiot1K, 'Treść korygowana');
    secondColumn = generateCorrectedContent(podmiot1, 'Treść korygująca');
    if (podmiot1.AdresKoresp) {
        secondColumn.push(formatText('Adres do korespondencji', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot1.AdresKoresp));
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
    if (podmiot.PrefiksPodatnika?._text) {
        result.push(createLabelText('Prefiks VAT: ', podmiot.PrefiksPodatnika));
    }
    if (podmiot.DaneIdentyfikacyjne) {
        result.push(...generateDaneIdentyfikacyjneTPodmiot1Dto(podmiot.DaneIdentyfikacyjne));
    }
    if (podmiot.Adres) {
        result.push(formatText('Adres', [FormatTyp.Label, FormatTyp.LabelMargin]), generateAdres(podmiot.Adres));
    }
    return result;
}
//# sourceMappingURL=Podmiot1Podmiot1K.js.map