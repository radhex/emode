import { createHeader, createLabelText, createSubHeader, generateTwoColumns, getTable, getValue, hasValue, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { getRolaString } from '../../../shared/generators/common/functions.js';
import { generatePodmiotAdres } from './PodmiotAdres.js';
import { generateDaneIdentyfikacyjne } from './PodmiotDaneIdentyfikacyjne.js';
import { generateDaneKontaktowe } from './PodmiotDaneKontaktowe.js';
export function generateDaneIdentyfikacyjneTPodmiot3Dto(podmiot2KDto, index) {
    if (!podmiot2KDto) {
        return [];
    }
    const podmiot1 = podmiot2KDto.fakturaPodmiotNDto;
    const podmiot1K = podmiot2KDto.podmiot2KDto;
    const result = createHeader(`Podmiot inny ${index + 1}`);
    if (hasValue(podmiot1.NrEORI) ||
        hasValue(podmiot1.Rola) ||
        hasValue(podmiot1.OpisRoli) ||
        hasValue(podmiot1?.Udzial)) {
        result.push(...createSubHeader('Dane identyfikacyjne'), createLabelText('Numer EORI: ', podmiot1.NrEORI), createLabelText('Rola: ', getRolaString(podmiot1.Rola, 1)), createLabelText('Rola inna: ', podmiot1.OpisRoli), createLabelText('Udział: ', podmiot1.Udzial, FormatTyp.Currency6));
    }
    if (podmiot1.Email || podmiot1.Telefon) {
        result.push(generateDaneKontaktowe(podmiot1.Email, getTable(podmiot1.Telefon)));
    }
    if (hasValue(podmiot1.NrKlienta)) {
        result.push(createLabelText('Numer klienta: ', podmiot1.NrKlienta));
    }
    const columns1 = [...createSubHeader('Treść korygowana')];
    if (hasValue(podmiot1K?.DaneIdentyfikacyjne?.NrID)) {
        columns1.push(createLabelText('Identyfikator podatkowy inny: ', podmiot1K?.DaneIdentyfikacyjne?.NrID));
    }
    if (getValue(podmiot1K?.DaneIdentyfikacyjne?.BrakID) === '1') {
        columns1.push(createLabelText('Brak identyfikatora ', ' '));
    }
    if (podmiot1K?.DaneIdentyfikacyjne) {
        columns1.push(generateDaneIdentyfikacyjne(podmiot1K.DaneIdentyfikacyjne));
    }
    if (podmiot1K?.Adres) {
        columns1.push(generatePodmiotAdres(podmiot1K.Adres, 'Adres', true));
    }
    const columns2 = [...createSubHeader('Treść korygująca')];
    if (hasValue(podmiot1.DaneIdentyfikacyjne?.NrID)) {
        columns2.push(createLabelText('Identyfikator podatkowy inny: ', podmiot1.DaneIdentyfikacyjne?.NrID));
    }
    if (getValue(podmiot1.DaneIdentyfikacyjne?.BrakID) === '1') {
        columns2.push(createLabelText('Brak identyfikatora ', ' '));
    }
    if (podmiot1?.DaneIdentyfikacyjne) {
        columns2.push(generateDaneIdentyfikacyjne(podmiot1.DaneIdentyfikacyjne));
    }
    if (podmiot1?.Adres) {
        columns2.push(generatePodmiotAdres(podmiot1.Adres, 'Adres', true));
    }
    if (podmiot1.AdresKoresp != null) {
        columns2.push(generatePodmiotAdres(podmiot1.AdresKoresp, 'Adres korespondencyjny', true));
    }
    result.push(generateTwoColumns(columns1, columns2));
    return result;
}
//# sourceMappingURL=Podmiot3Podmiot2k.js.map