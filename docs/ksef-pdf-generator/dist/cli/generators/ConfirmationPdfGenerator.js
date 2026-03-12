import pdfMake from 'pdfmake/build/pdfmake.js';
import { generateQRCodeData } from '../../lib-public/generators/common/Stopka.js';
import { generatePodmioty } from '../../lib-public/generators/FA3/Podmioty.js';
import { TRodzajFaktury } from '../../shared/consts/const.js';
import FormatTyp, { Position } from '../../shared/enums/common.enum.js';
import { createLabelText, createLabelTextArray, createSection, formatText, generateStyle, getValue, verticalSpacing, } from '../../shared/PDF-functions.js';
import { parseXML } from '../../shared/XML-parser.js';
export class ConfirmationPdfGenerator {
    async generate(file, additionalData) {
        const xml = await parseXML(file);
        let pdf;
        return new Promise((resolve) => {
            pdf = this.generateConfirmation(xml.Faktura, additionalData);
            pdf.getBlob((blob) => {
                resolve(blob);
            });
        });
    }
    generateConfirmation(invoice, additionalData) {
        const docDefinition = {
            content: [
                ...this.generateNaglowek(invoice.Fa),
                ...generatePodmioty(invoice),
                this.generateWiersze(invoice.Fa),
                this.generateStopka(additionalData, invoice.Naglowek),
            ],
            ...generateStyle(),
        };
        return pdfMake.createPdf(docDefinition);
    }
    generateStopka(additionalData, naglowek) {
        const qrCode = generateQRCodeData(additionalData, false);
        const result = [
            verticalSpacing(1),
            { stack: [...qrCode], unbreakable: false },
            createSection([
                {
                    stack: createLabelText('Wytworzona w: ', naglowek?.SystemInfo),
                    margin: [0, 8, 0, 0],
                },
            ], true, [0, 0, 0, 0]),
        ];
        return createSection(result, false);
    }
    generateWiersze(faVat) {
        const rodzajFaktury = getValue(faVat.RodzajFaktury);
        const p_15 = getValue(faVat.P_15);
        let opis = '';
        if (rodzajFaktury == TRodzajFaktury.ROZ && Number(p_15) !== 0) {
            opis = {
                stack: createLabelTextArray([
                    { value: 'Kwota pozostała do zapłaty: ', formatTyp: FormatTyp.LabelGreater },
                    {
                        value: p_15,
                        formatTyp: FormatTyp.CurrencyGreater,
                        currency: getValue(faVat.KodWaluty)?.toString() ?? '',
                    },
                ]),
                alignment: Position.RIGHT,
                margin: [0, 8, 0, 0],
            };
        }
        else if ((rodzajFaktury == TRodzajFaktury.VAT ||
            rodzajFaktury == TRodzajFaktury.KOR ||
            rodzajFaktury == TRodzajFaktury.KOR_ROZ ||
            rodzajFaktury == TRodzajFaktury.UPR) &&
            Number(p_15) !== 0) {
            opis = {
                stack: createLabelTextArray([
                    { value: 'Kwota należności ogółem: ', formatTyp: FormatTyp.LabelGreater },
                    {
                        value: p_15,
                        formatTyp: [FormatTyp.CurrencyGreater, FormatTyp.HeaderContent, FormatTyp.Value],
                        currency: getValue(faVat.KodWaluty)?.toString() ?? '',
                    },
                ]),
                alignment: Position.RIGHT,
                margin: [0, 8, 0, 0],
            };
        }
        return createSection([opis], true);
    }
    generateNaglowek(fa) {
        const confirmationName = 'Potwierdzenie transakcji';
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
                ...formatText(confirmationName, [FormatTyp.ValueMedium, FormatTyp.Default]),
                alignment: Position.RIGHT,
            },
        ];
    }
}
//# sourceMappingURL=ConfirmationPdfGenerator.js.map