import { createLabelText } from '../../../shared/PDF-functions.js';
export function generateDaneKontaktowe(email, telefon) {
    const result = [];
    if (email) {
        result.push(createLabelText('Email: ', email));
    }
    if (telefon) {
        telefon.forEach((item) => {
            result.push(createLabelText('Tel.: ', `${item}\n`));
        });
    }
    return result;
}
//# sourceMappingURL=PodmiotDaneKontaktowe.js.map