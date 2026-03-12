import { createHeader, createSection, createSubHeader, formatText, getContentTable, getTable, getValue, } from '../../../shared/PDF-functions.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateDodatkoweInformacje(faVat) {
    const tpLabel = [];
    if (getValue(faVat.TP) === '1') {
        tpLabel.push(formatText('- Istniejące powiązania między nabywcą a dokonującym dostawy towarów lub usługodawcą'));
    }
    const fpLabel = [];
    if (getValue(faVat.FP) === '1') {
        fpLabel.push(formatText('- Faktura, o której mowa w art. 109 ust. 3d ustawy'));
    }
    const zwrotAkcyzyLabel = [];
    if (getValue(faVat.ZwrotAkcyzy) === '1') {
        zwrotAkcyzyLabel.push(formatText('- Informacja dodatkowa związana ze zwrotem podatku akcyzowego zawartego w cenie oleju napędowego'));
    }
    const labels = [tpLabel, fpLabel, zwrotAkcyzyLabel].filter((el) => el.length > 0);
    const table = [
        ...createHeader('Dodatkowe informacje'),
        ...labels,
        ...generateDodatkowyOpis(faVat.DodatkowyOpis),
    ];
    return table.length > 1 ? createSection(table, true) : [];
}
function generateDodatkowyOpis(fakturaZaliczkowaData) {
    if (!fakturaZaliczkowaData) {
        return [];
    }
    const fakturaZaliczkowa = getTable(fakturaZaliczkowaData)?.map((item, index) => ({
        ...item,
        lp: { _text: index + 1 },
    }));
    const table = createSubHeader('Dodatkowy opis');
    const fakturaZaliczkowaHeader = [
        {
            name: 'lp',
            title: 'Lp.',
            format: FormatTyp.Default,
            width: 'auto',
        },
        {
            name: 'Klucz',
            title: 'Rodzaj informacji',
            format: FormatTyp.Default,
            width: 'auto',
        },
        {
            name: 'Wartosc',
            title: 'Treść informacji',
            format: FormatTyp.Default,
            width: '*',
        },
    ];
    const tableFakturaZaliczkowa = getContentTable(fakturaZaliczkowaHeader, fakturaZaliczkowa, '*', [0, 0, 0, 0]);
    if (tableFakturaZaliczkowa.content) {
        table.push(tableFakturaZaliczkowa.content);
    }
    return table;
}
//# sourceMappingURL=DodatkoweInformacje.js.map