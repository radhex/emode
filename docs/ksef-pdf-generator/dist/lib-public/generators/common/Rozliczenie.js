import { createHeader, createLabelText, createLabelTextArray, createSection, createSubHeader, generateColumns, generateTwoColumns, getContentTable, getTable, } from '../../../shared/PDF-functions.js';
import FormatTyp, { Position } from '../../../shared/enums/common.enum.js';
export function generateRozliczenie(rozliczenie, KodWaluty) {
    if (!rozliczenie) {
        return [];
    }
    const obciazenia = getTable(rozliczenie?.Obciazenia);
    const odliczenia = getTable(rozliczenie?.Odliczenia);
    const result = [];
    const headerOdliczenia = [
        {
            title: 'Powód odliczenia',
            name: 'Powod',
            format: FormatTyp.Default,
        },
        {
            title: 'Kwota odliczenia',
            name: 'Kwota',
            format: FormatTyp.Currency,
        },
    ];
    const headerObciazenia = [
        {
            name: 'Powod',
            title: 'Powód obciążenia',
            format: FormatTyp.Default,
        },
        {
            name: 'Kwota',
            title: 'Kwota obciążenia',
            format: FormatTyp.Currency,
        },
    ];
    const tableObciazenia = getContentTable(headerObciazenia, obciazenia, '*', undefined, 20);
    const tableOdliczenia = getContentTable(headerOdliczenia, odliczenia, '*', undefined, 20);
    const SumaObciazen = createLabelText('Suma kwot obciążenia: ', rozliczenie.SumaObciazen, FormatTyp.Currency, {
        alignment: Position.RIGHT,
    });
    const Sumaodliczen = createLabelText('Suma kwot odliczenia: ', rozliczenie?.SumaOdliczen, FormatTyp.Currency, {
        alignment: Position.RIGHT,
    });
    const resultObciazenia = [
        createSubHeader('Obciążenia'),
        tableObciazenia.content ?? [],
        SumaObciazen,
    ];
    const resultOdliczenia = [
        createSubHeader('Odliczenia'),
        tableOdliczenia.content ?? [],
        Sumaodliczen,
    ];
    result.push(createHeader('Rozliczenie', [0, 8, 0, 4]));
    if (obciazenia.length > 0 && odliczenia.length > 0) {
        result.push(generateColumns([resultObciazenia, resultOdliczenia]));
    }
    else if (obciazenia.length > 0) {
        result.push(generateTwoColumns([resultObciazenia], []));
    }
    else if (odliczenia.length > 0) {
        result.push(generateTwoColumns([], [resultOdliczenia]));
    }
    if (rozliczenie?.DoZaplaty?._text) {
        result.push({
            stack: createLabelTextArray([
                { value: 'Do zapłaty: ', formatTyp: FormatTyp.LabelGreater },
                { value: rozliczenie?.DoZaplaty, formatTyp: FormatTyp.CurrencyGreater, currency: KodWaluty },
            ]),
            alignment: Position.RIGHT,
            margin: [0, 8, 0, 0],
        });
    }
    else if (rozliczenie?.DoRozliczenia?._text) {
        result.push({
            stack: createLabelTextArray([
                { value: 'Do rozliczenia: ', formatTyp: FormatTyp.LabelGreater },
                { value: rozliczenie?.DoRozliczenia, formatTyp: FormatTyp.CurrencyGreater, currency: KodWaluty },
            ]),
            alignment: Position.RIGHT,
            marginTop: 8,
        });
    }
    return createSection(result, true);
}
//# sourceMappingURL=Rozliczenie.js.map