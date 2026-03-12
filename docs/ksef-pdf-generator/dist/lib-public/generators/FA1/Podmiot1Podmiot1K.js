import { createHeader, createLabelText, createSubHeader, generateColumns, getTable, verticalSpacing, } from '../../../shared/PDF-functions.js';
import { generatePodmiotAdres } from './PodmiotAdres.js';
import { generateDaneIdentyfikacyjne } from './PodmiotDaneIdentyfikacyjne.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
export function generatePodmiot1Podmiot1K(podmiot1, podmiot1K) {
    const result = createHeader('Sprzedawca');
    let firstColumn = [];
    let secondColumn = [];
    firstColumn.push(createSubHeader('Dane identyfikacyjne'), createLabelText('Numer EORI: ', podmiot1.NrEORI));
    if (podmiot1.DaneIdentyfikacyjne) {
        firstColumn.push(...generateDaneIdentyfikacyjne(podmiot1.DaneIdentyfikacyjne));
    }
    if (podmiot1.Email || podmiot1.Telefon) {
        firstColumn.push(generateDaneKontaktowe(podmiot1.Email, getTable(podmiot1.Telefon)));
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
        secondColumn.push(generatePodmiotAdres(podmiot1.AdresKoresp, 'Adres do korespondencji', true, [0, 12, 0, 1.3]));
    }
    if (firstColumn.length || secondColumn.length) {
        result.push(generateColumns([firstColumn, secondColumn]));
    }
    if (result.length) {
        result.push(verticalSpacing(1));
    }
    return result;
}
export function generateCorrectedContent(podmiot, headerText) {
    const result = [];
    result.push(createSubHeader(headerText));
    if (podmiot.PrefiksPodatnika?._text) {
        result.push(createLabelText('Prefiks VAT: ', podmiot.PrefiksPodatnika));
    }
    if (podmiot.DaneIdentyfikacyjne) {
        result.push(...generateDaneIdentyfikacyjne(podmiot.DaneIdentyfikacyjne));
    }
    if (podmiot.Adres) {
        result.push(generatePodmiotAdres(podmiot.Adres, 'Adres', true, [0, 12, 0, 1.3]));
    }
    return result;
}
//# sourceMappingURL=Podmiot1Podmiot1K.js.map