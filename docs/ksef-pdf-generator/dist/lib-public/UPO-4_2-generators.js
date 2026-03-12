import pdfMake from 'pdfmake/build/pdfmake.js';
import { generateStyle } from '../shared/PDF-functions.js';
import { generateNaglowekUPO } from './generators/UPO4_2/Naglowek.js';
import { generateDokumnetUPO } from './generators/UPO4_2/Dokumenty.js';
import { parseXML } from '../shared/XML-parser.js';
import { Position } from '../shared/enums/common.enum.js';
export async function generatePDFUPO(file) {
    const upo = (await parseXML(file));
    const docDefinition = {
        content: [generateNaglowekUPO(upo.Potwierdzenie), generateDokumnetUPO(upo.Potwierdzenie)],
        ...generateStyle(),
        pageSize: 'A4',
        pageOrientation: 'landscape',
        footer: function (currentPage, pageCount) {
            return {
                text: currentPage.toString() + ' z ' + pageCount,
                alignment: Position.RIGHT,
                margin: [0, 0, 20, 0],
            };
        },
    };
    return new Promise((resolve, reject) => {
        pdfMake.createPdf(docDefinition).getBlob((blob) => {
            if (blob) {
                resolve(blob);
            }
            else {
                reject('Error');
            }
        });
    });
}
//# sourceMappingURL=UPO-4_2-generators.js.map