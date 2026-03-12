import { createHeader, createSection, formatText, getValue, hasValue, makeBreakable, } from '../../../shared/PDF-functions.js';
import { DEFAULT_TABLE_LAYOUT } from '../../../shared/consts/const.js';
import FormatTyp from '../../../shared/enums/common.enum.js';
import { getTypRachunkowWlasnych } from '../../../shared/generators/common/functions.js';
export const generujRachunekBankowy = (accounts, title) => {
    const result = [];
    if (!accounts?.length) {
        return [];
    }
    accounts.forEach((account, index) => {
        const table = [];
        const base = createHeader(title ? `${title} ${accounts?.length > 1 ? ++index : ''}` : '', [0, 12, 0, 8]);
        table.push([
            formatText('Pełny numer rachunku', FormatTyp.GrayBoldTitle),
            formatText(getValue(account.NrRB), FormatTyp.Default),
        ]);
        table.push([
            formatText('Kod SWIFT', FormatTyp.GrayBoldTitle),
            formatText(getValue(account.SWIFT), FormatTyp.Default),
        ]);
        table.push([
            formatText('Rachunek własny banku', FormatTyp.GrayBoldTitle),
            formatText(makeBreakable(getTypRachunkowWlasnych(account.RachunekWlasnyBanku), 20), FormatTyp.Default),
        ]);
        table.push([
            formatText('Nazwa banku', FormatTyp.GrayBoldTitle),
            formatText(hasValue(account.NazwaBanku)
                ? makeBreakable(getValue(account.NazwaBanku), 20)
                : getValue(account.NazwaBanku), FormatTyp.Default),
        ]);
        table.push([
            formatText('Opis rachunku', FormatTyp.GrayBoldTitle),
            formatText(hasValue(account.OpisRachunku)
                ? makeBreakable(getValue(account.OpisRachunku), 20)
                : getValue(account.OpisRachunku), FormatTyp.Default),
        ]);
        result.push([
            ...base,
            {
                unbreakable: true,
                table: {
                    body: table,
                    widths: ['*', 'auto'],
                },
                layout: DEFAULT_TABLE_LAYOUT,
            },
        ]);
    });
    return createSection(result, false);
};
//# sourceMappingURL=RachunekBankowy.js.map