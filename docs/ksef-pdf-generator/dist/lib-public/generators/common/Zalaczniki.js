import { DEFAULT_TABLE_LAYOUT, TableDataType } from '../../../shared/consts/const.js';
import { createHeader, createLabelText, createSection, createSubHeader, formatText, getContentTable, getTable, hasValue, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateZalaczniki(zalacznik) {
    if (!getTable(zalacznik?.BlokDanych).length) {
        return [];
    }
    const result = [];
    const definedHeader = [
        { name: 'PelnaNazwa', title: 'Pełna nazwa', format: FormatTyp.Default },
        { name: 'KRS', title: 'KRS', format: FormatTyp.Default },
        { name: 'REGON', title: 'REGON', format: FormatTyp.Default },
        { name: 'BDO', title: 'BDO', format: FormatTyp.Default },
    ];
    const faWiersze = getTable(zalacznik?.BlokDanych ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*');
    result.push(createHeader('Załącznik do Faktury VAT'));
    getTable(zalacznik?.BlokDanych).forEach((blok, index) => {
        result.push(createSubHeader(`Szczegółowe dane załącznika (${index + 1})`));
        if (blok.ZNaglowek) {
            result.push(createLabelText('Nagłówek bloku danych: ', blok.ZNaglowek, FormatTyp.Value, { marginBottom: 8 }));
        }
        if (getTable(blok.MetaDane)?.length) {
            result.push(generateKluczWartosc(getTable(blok.MetaDane)));
        }
        if (blok.Tekst?.Akapit) {
            result.push(createLabelText('Opis: ', ' '));
            getTable(blok.Tekst.Akapit).forEach((text) => {
                if (hasValue(text)) {
                    result.push(formatText(text._text, FormatTyp.Value));
                }
            });
        }
        if (getTable(blok.Tabela).length) {
            getTable(blok.Tabela).forEach((tabela, index) => {
                if (blok.ZNaglowek?._text) {
                    result.push(createSubHeader(`${blok.ZNaglowek?._text} ${index + 1}`));
                }
                if (getTable(tabela.TMetaDane)?.length) {
                    result.push({
                        stack: generateKluczWartosc(getTable(tabela.TMetaDane).map((item) => ({
                            ZKlucz: item.TKlucz,
                            ZWartosc: item.TWartosc,
                        }))),
                        margin: [0, 8, 0, 0],
                    });
                }
                if (tabela.Opis) {
                    result.push(createLabelText('Opis: ', tabela.Opis));
                }
                if (getTable(tabela.TNaglowek?.Kol).length) {
                    result.push(formatText('Tabela', [FormatTyp.GrayBoldTitle, FormatTyp.LabelSmallMargin]));
                    result.push(generateTable(tabela));
                }
                if (getTable(tabela.Suma?.SKom).length) {
                    result.push(generateSuma(getTable(tabela.Suma?.SKom)));
                }
            });
        }
    });
    if (content.fieldsWithValue.length && content.content) {
        result.push(content.content);
    }
    return createSection(result, false);
}
function generateKluczWartosc(data) {
    const result = [];
    const definedHeader = [
        { name: 'ZKlucz', title: 'Klucz', format: FormatTyp.Default },
        { name: 'ZWartosc', title: 'Wartość', format: FormatTyp.Default },
    ];
    const faWiersze = getTable(data ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*');
    if (content.fieldsWithValue.length && content.content) {
        result.push(content.content);
    }
    return result;
}
function generateTable(tabela) {
    if (!tabela.TNaglowek?.Kol?.length) {
        return [];
    }
    const result = [];
    const Kol = getTable(tabela.TNaglowek.Kol);
    const cutedTableHeader = chunkArray(Kol);
    cutedTableHeader.forEach((table, index) => {
        result.push(createTable(table, tabela.Wiersz ?? [], index, Kol.length));
    });
    return result;
}
function createTable(cols, rows, subTableIndex, totalLength) {
    const definedHeader = cols.map((item) => formatText(item.NKom?._text, FormatTyp.GrayBoldTitle));
    const tableBody = [];
    rows.forEach((item) => {
        const WKom = getTable(item.WKom);
        while (WKom.length < totalLength) {
            WKom.push({ _text: '' });
        }
        const cuttedRows = chunkArray(WKom ?? []);
        if (cuttedRows.length >= subTableIndex + 1) {
            tableBody.push(cuttedRows[subTableIndex].map((subItem, index) => {
                return formatText(subItem._text ?? '', cols[index]._attributes?.Typ ? TableDataType[cols[index]._attributes.Typ] : FormatTyp.Value);
            }));
        }
    });
    const widths = definedHeader.map((index) => {
        if (index) {
            return ['*'];
        }
        else {
            return ['auto'];
        }
    });
    return {
        table: {
            headerRows: 1,
            widths: [...widths],
            heights: 8,
            body: [[...definedHeader], ...tableBody],
        },
        layout: DEFAULT_TABLE_LAYOUT,
        marginTop: 8,
    };
}
function generateSuma(data) {
    const result = [];
    const definedHeader = [
        { name: '', title: 'Podsumowanie tabeli', format: FormatTyp.Default },
    ];
    const faWiersze = getTable(data ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*', [0, 8, 0, 0]);
    if (content.fieldsWithValue.length && content.content) {
        result.push(content.content);
    }
    return result;
}
export function chunkArray(columns) {
    if (!Array.isArray(columns)) {
        return [];
    }
    const n = columns.length;
    if (n <= 7) {
        return [columns];
    }
    else if (n >= 8 && n <= 14) {
        const half = Math.floor(n / 2);
        if (n % 2 === 0) {
            return [columns.slice(0, half), columns.slice(half)];
        }
        else {
            return [columns.slice(0, half + 1), columns.slice(half + 1)];
        }
    }
    else {
        const base = Math.floor(n / 3);
        const remainder = n % 3;
        const splits = [base, base, base];
        for (let i = 0; i < remainder; i++) {
            splits[i] += 1;
        }
        const result = [];
        let idx = 0;
        for (const size of splits) {
            result.push(columns.slice(idx, idx + size));
            idx += size;
        }
        return result;
    }
}
//# sourceMappingURL=Zalaczniki.js.map