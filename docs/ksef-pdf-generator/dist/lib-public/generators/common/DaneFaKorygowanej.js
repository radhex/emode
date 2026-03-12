import { TypKorekty } from '../../../shared/consts/const.js';
import { createHeader, createLabelText, createSection, generateTwoColumns, getTable, } from '../../../shared/PDF-functions.js';
export function generateDaneFaKorygowanej(invoice) {
    const result = [];
    let firstColumn = [];
    let secondColumn = [];
    let previousSection = false;
    if (invoice) {
        const daneFakturyKorygowanej = getTable(invoice.DaneFaKorygowanej ?? []);
        if (invoice.NrFaKorygowany) {
            firstColumn.push(createLabelText('Poprawny numer faktury korygowanej: ', invoice.NrFaKorygowany));
        }
        if (invoice.PrzyczynaKorekty) {
            firstColumn.push(createLabelText('Przyczyna korekty dla faktur korygujących: ', invoice.PrzyczynaKorekty));
        }
        if (invoice.TypKorekty?._text) {
            firstColumn.push(createLabelText('Typ skutku korekty: ', TypKorekty[invoice.TypKorekty._text]));
        }
        if (firstColumn.length) {
            firstColumn.unshift(createHeader('Dane faktury korygowanej'));
        }
        if (daneFakturyKorygowanej?.length === 1) {
            secondColumn.push(createHeader('Dane identyfikacyjne faktury korygowanej'));
            generateCorrectiveData(daneFakturyKorygowanej[0], secondColumn);
            if (firstColumn.length > 0 || secondColumn.length) {
                if (firstColumn.length) {
                    result.push(generateTwoColumns(firstColumn, secondColumn));
                }
                else {
                    result.push(generateTwoColumns(secondColumn, []));
                }
                previousSection = true;
            }
            firstColumn = [];
            secondColumn = [];
        }
        else {
            if (firstColumn.length > 1) {
                result.push(generateTwoColumns(firstColumn, []));
                previousSection = true;
            }
            firstColumn = [];
            daneFakturyKorygowanej?.forEach((item, index) => {
                if (index % 2 === 0) {
                    firstColumn.push(createHeader(`Dane identyfikacyjne faktury korygowanej ${index + 1}`));
                    generateCorrectiveData(item, firstColumn);
                }
                else {
                    secondColumn.push(createHeader(`Dane identyfikacyjne faktury korygowanej ${index + 1}`));
                    generateCorrectiveData(item, secondColumn);
                }
            });
        }
    }
    if (firstColumn.length && secondColumn.length) {
        result.push(createSection([generateTwoColumns(firstColumn, secondColumn)], previousSection));
    }
    return createSection(result, true);
}
function generateCorrectiveData(data, column) {
    if (data.DataWystFaKorygowanej) {
        column.push(createLabelText('Data wystawienia faktury, której dotyczy faktura korygująca: ', data.DataWystFaKorygowanej));
    }
    if (data.NrFaKorygowanej) {
        column.push(createLabelText('Numer faktury korygowanej: ', data.NrFaKorygowanej));
    }
    if (data.NrKSeFFaKorygowanej) {
        column.push(createLabelText('Numer KSeF faktury korygowanej: ', data.NrKSeFFaKorygowanej));
    }
}
//# sourceMappingURL=DaneFaKorygowanej.js.map