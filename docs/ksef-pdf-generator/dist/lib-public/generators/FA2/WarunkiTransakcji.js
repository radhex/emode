import { createHeader, createLabelText, createSection, createSubHeader, formatText, generateTwoColumns, getContentTable, getTable, } from '../../../shared/PDF-functions.js';
import { generateTransport } from './Transport.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
export function generateWarunkiTransakcji(warunkiTransakcji) {
    if (!warunkiTransakcji) {
        return [];
    }
    const table = [];
    const Kolumny = { umowy: [], zamowienia: [] };
    const umowy = getTable(warunkiTransakcji?.Umowy);
    const zamowienia = getTable(warunkiTransakcji?.Zamowienia);
    const partiaTowaru = getTable(warunkiTransakcji?.NrPartiiTowaru);
    const definedHeaderUmowy = [
        { name: 'DataUmowy', title: 'Data umowy', format: FormatTyp.Default },
        { name: 'NrUmowy', title: 'Numer umowy', format: FormatTyp.Default },
    ];
    const definedHeaderZamowienia = [
        { name: 'DataZamowienia', title: 'Data zamówienia', format: FormatTyp.Default },
        { name: 'NrZamowienia', title: 'Numer zamówienia', format: FormatTyp.Default },
    ];
    const definedHeaderPartiaTowaru = [
        { name: '', title: 'Numer partii towaru', format: FormatTyp.Default },
    ];
    table.push(createHeader('Warunki transakcji', [0, 8, 0, 4]));
    if (umowy.length > 0) {
        const tabUmowy = getContentTable(definedHeaderUmowy, umowy, '*');
        if (tabUmowy.content) {
            Kolumny.umowy = [createSubHeader('Umowa'), tabUmowy.content];
        }
    }
    if (zamowienia.length > 0) {
        const tabZamowienia = getContentTable(definedHeaderZamowienia, zamowienia, '*');
        if (tabZamowienia.content && tabZamowienia.fieldsWithValue.length > 0) {
            Kolumny.zamowienia = [createSubHeader('Zamówienie'), tabZamowienia.content];
        }
    }
    if (Kolumny.zamowienia.length > 0 || Kolumny.umowy.length > 0) {
        table.push(generateTwoColumns(Kolumny.umowy, Kolumny.zamowienia));
    }
    if (warunkiTransakcji.WalutaUmowna?._text || warunkiTransakcji.KursUmowny?._text) {
        table.push(createHeader('Waluta umowna i kurs umowny', [0, 8, 0, 4]));
        table.push(createLabelText('Waluta umowna: ', warunkiTransakcji.WalutaUmowna));
        table.push(createLabelText('Kurs umowny: ', warunkiTransakcji.KursUmowny));
    }
    if (partiaTowaru.length > 0) {
        const tabPartiaTowaru = getContentTable(definedHeaderPartiaTowaru, partiaTowaru, '*', [0, 4]);
        if (tabPartiaTowaru.content) {
            table.push(generateTwoColumns(tabPartiaTowaru.content, ''));
        }
    }
    table.push(createLabelText('Warunki dostawy towarów: ', warunkiTransakcji.WarunkiDostawy, FormatTyp.MarginTop4));
    if (warunkiTransakcji.PodmiotPosredniczacy?._text === '1') {
        table.push(formatText('Dostawa dokonana przez podmiot, o którym mowa w art. 22 ust. 2d ustawy. Pole dotyczy sytuacji, w której podmiot uczestniczy w transakcji łańcuchowej innej niż procedura trójstronna uproszczona, o której mowa w art. 135 ust. 1 pkt 4 ustawy', [FormatTyp.Label, FormatTyp.MarginTop4]));
    }
    if (warunkiTransakcji.Transport) {
        getTable(warunkiTransakcji.Transport).forEach((transport, index) => {
            table.push(generateTransport(transport, getTable(warunkiTransakcji.Transport).length !== 0 ? index + 1 : null));
        });
    }
    return createSection(table, true);
}
//# sourceMappingURL=WarunkiTransakcji.js.map