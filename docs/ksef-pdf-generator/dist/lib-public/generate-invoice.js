import { generateFA1 } from './FA1-generator.js';
import { generateFA2 } from './FA2-generator.js';
import { generateFA3 } from './FA3-generator.js';
import { parseXML } from '../shared/XML-parser.js';
export async function generateInvoice(file, additionalData, formatType = 'blob') {
    const xml = await parseXML(file);
    const wersja = xml?.Faktura?.Naglowek?.KodFormularza?._attributes?.kodSystemowy;
    let pdf;
    return new Promise((resolve) => {
        switch (wersja) {
            case 'FA (1)':
                pdf = generateFA1(xml.Faktura, additionalData);
                break;
            case 'FA (2)':
                pdf = generateFA2(xml.Faktura, additionalData);
                break;
            case 'FA (3)':
                pdf = generateFA3(xml.Faktura, additionalData);
                break;
        }
        switch (formatType) {
            case 'blob':
                pdf.getBlob((blob) => {
                    resolve(blob);
                });
                break;
            case 'base64':
            default:
                pdf.getBase64((base64) => {
                    resolve(base64);
                });
        }
    });
}
//# sourceMappingURL=generate-invoice.js.map