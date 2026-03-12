import { createHeader, createLabelText, createLabelTextArray, createSection, generateTwoColumns, getContentTable, getDifferentColumnsValue, getTable, getValue, hasColumnsValue, hasValue, } from '../../../shared/PDF-functions.js';
import { TRodzajFaktury } from '../../../shared/consts/const.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateSzczegoly(faVat) {
    const faWiersze = getTable(faVat.FaWiersze?.FaWiersz);
    const zamowieniaWiersze = getTable(faVat.Zamowienie?.ZamowienieWiersz);
    const LabelP_6 = faVat.RodzajFaktury == TRodzajFaktury.ZAL || faVat.RodzajFaktury == TRodzajFaktury.KOR_ZAL
        ? 'Data otrzymania zapłaty: '
        : 'Data dokonania lub zakończenia dostawy towarów lub wykonania usługi: ';
    const P_6Scope = generateP_6Scope(faVat.OkresFa?.P_6_Od, faVat.OkresFa?.P_6_Do);
    const cenyLabel1 = [];
    const cenyLabel2 = [];
    if (!(faWiersze.length > 0 || zamowieniaWiersze.length > 0)) {
        const Any_P_11 = hasColumnsValue('P_11', faWiersze) || hasColumnsValue('P_11', zamowieniaWiersze);
        if (Any_P_11) {
            cenyLabel1.push(createLabelText('Faktura wystawiona w cenach: ', 'netto'));
        }
        else {
            cenyLabel1.push(createLabelText('Faktura wystawiona w cenach: ', 'brutto'));
        }
        cenyLabel2.push(createLabelText('Kod waluty: ', faVat.KodWaluty));
    }
    const P_12_XIILabel = [];
    if (hasColumnsValue('P_12_XII', faWiersze) || hasColumnsValue('P_12_XII', zamowieniaWiersze)) {
        P_12_XIILabel.push(createLabelText('Procedura One Stop Shop', ' '));
    }
    const kodWalutyLabel1 = [];
    const kodWalutyLabel2 = [];
    if (hasValue(faVat.KodWaluty) && getValue(faVat.KodWaluty) != 'PLN') {
        if (faVat.Zamowienie?.ZamowienieWiersz?.length) {
            const Common_KursWaluty = getDifferentColumnsValue('KursWalutyZ', faVat.Zamowienie?.ZamowienieWiersz);
            if (Common_KursWaluty.length === 1) {
                kodWalutyLabel1.push(createLabelText('Kurs waluty wspólny dla wszystkich wierszy faktury', ' '));
                kodWalutyLabel2.push(createLabelText('Kurs waluty: ', Common_KursWaluty[0].value, FormatTyp.Currency6));
            }
        }
        else {
            const Common_KursWaluty = getDifferentColumnsValue('KursWaluty', faWiersze);
            if (Common_KursWaluty.length === 1) {
                kodWalutyLabel1.push(createLabelText('Kurs waluty wspólny dla wszystkich wierszy faktury', ' '));
                kodWalutyLabel2.push(createLabelText('Kurs waluty: ', Common_KursWaluty[0].value, FormatTyp.Currency6));
            }
        }
    }
    const tpLabel1 = [];
    const tpLabel2 = [];
    const forColumns = [
        createLabelText('Numer faktury: ', faVat.P_2),
        createLabelText('Data wystawienia, z zastrzeżeniem art. 106na ust. 1 ustawy: ', faVat.P_1),
        createLabelText('Miejsce wystawienia: ', faVat.P_1M),
        createLabelText('Okres, którego dotyczy rabat: ', faVat.OkresFaKorygowanej),
        createLabelText(LabelP_6, faVat.P_6),
        P_6Scope,
        cenyLabel1,
        cenyLabel2,
        P_12_XIILabel,
        kodWalutyLabel1,
        kodWalutyLabel2,
        tpLabel1,
        tpLabel2,
    ].filter((el) => el.length > 0);
    const columns1 = [];
    const columns2 = [];
    forColumns.forEach((tab, index) => {
        if (index % 2) {
            columns2.push(tab);
        }
        else {
            columns1.push(tab);
        }
    });
    const table = [
        ...createHeader('Szczegóły'),
        generateTwoColumns(columns1, columns2),
        ...generateFakturaZaliczkowa(getTable(faVat.NrFaZaliczkowej)),
    ];
    return createSection(table, true);
}
function generateP_6Scope(P_6_Od, P_6_Do) {
    const table = [];
    if (hasValue(P_6_Od) && hasValue(P_6_Do)) {
        table.push(createLabelTextArray([
            {
                value: 'Data dokonania lub zakończenia dostawy towarów lub wykonania usługi: od ',
            },
            { value: P_6_Od, formatTyp: FormatTyp.Value },
            { value: ' do ' },
            { value: P_6_Do, formatTyp: FormatTyp.Value },
        ]));
    }
    else if (hasValue(P_6_Od)) {
        table.push(createLabelText('Data dokonania lub zakończenia dostawy towarów lub wykonania usługi: od ', P_6_Od));
    }
    else if (hasValue(P_6_Do)) {
        table.push(createLabelText('Data dokonania lub zakończenia dostawy towarów lub wykonania usługi: do ', P_6_Do));
    }
    return table;
}
function generateFakturaZaliczkowa(fakturaZaliczkowa) {
    if (!fakturaZaliczkowa) {
        return [];
    }
    const table = [];
    const fakturaZaliczkowaHeader = [
        {
            name: '',
            title: 'Numery wcześniejszych faktur zaliczkowych',
            format: FormatTyp.Default,
        },
    ];
    const tableFakturaZaliczkowa = getContentTable(fakturaZaliczkowaHeader, fakturaZaliczkowa, '50%', [0, 4, 0, 0]);
    if (tableFakturaZaliczkowa.content) {
        table.push(tableFakturaZaliczkowa.content);
    }
    return table;
}
//# sourceMappingURL=Szczegoly.js.map