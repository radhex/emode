import { formatText, generateLine } from '../../../shared/PDF-functions.js';
import { TRodzajFaktury } from '../../../shared/consts/const.js';
import FormatTyp, { Position } from '../../../shared/enums/common.enum.js';
export function generateNaglowek(fa, additionalData, zalacznik) {
    let invoiceName = '???';
    switch (fa?.RodzajFaktury?._text) {
        case TRodzajFaktury.VAT:
            invoiceName = 'Faktura podstawowa';
            break;
        case TRodzajFaktury.ZAL:
            invoiceName = 'Faktura zaliczkowa';
            break;
        case TRodzajFaktury.ROZ:
            invoiceName = 'Faktura rozliczeniowa';
            break;
        case TRodzajFaktury.KOR_ROZ:
            invoiceName = 'Faktura korygująca rozliczeniową';
            break;
        case TRodzajFaktury.KOR_ZAL:
            invoiceName = 'Faktura korygująca zaliczkową';
            break;
        case TRodzajFaktury.KOR:
            if (fa?.OkresFaKorygowanej != null) {
                invoiceName = 'Faktura korygująca zbiorcza (rabat)';
            }
            else {
                invoiceName = 'Faktura korygująca';
            }
            break;
        case TRodzajFaktury.UPR:
            invoiceName = 'Faktura uproszczona';
            break;
    }
    return [
        {
            text: [
                { text: 'Krajowy System ', fontSize: 18 },
                { text: 'e', color: 'red', bold: true, fontSize: 18 },
                { text: '-Faktur', bold: true, fontSize: 18 },
            ],
        },
        { ...formatText('Numer Faktury:', FormatTyp.ValueMedium), alignment: Position.RIGHT },
        { ...formatText(fa?.P_2?._text, FormatTyp.HeaderPosition), alignment: Position.RIGHT },
        {
            ...formatText(invoiceName, [FormatTyp.ValueMedium, FormatTyp.Default]),
            alignment: Position.RIGHT,
        },
        ...(additionalData?.nrKSeF
            ? [
                {
                    text: [
                        formatText('Numer KSEF:', FormatTyp.LabelMedium),
                        formatText(additionalData?.nrKSeF, FormatTyp.ValueMedium),
                    ],
                    alignment: Position.RIGHT,
                },
            ]
            : []),
        ...(additionalData?.isMobile && zalacznik
            ? [
                { stack: [generateLine()], margin: [0, 8, 0, 8] },
                {
                    text: [
                        formatText('Uwaga, faktura zawiera załącznik, jednak ze względu na ograniczenia wizualizacji, nie został on uwzględniony w pliku PDF', FormatTyp.Bold),
                    ],
                },
            ]
            : []),
    ];
}
//# sourceMappingURL=Naglowek.js.map