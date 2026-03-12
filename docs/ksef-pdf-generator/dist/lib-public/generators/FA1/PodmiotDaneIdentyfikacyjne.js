import { createLabelText, getValue, hasValue } from '../../../shared/PDF-functions.js';
export function generateDaneIdentyfikacyjne(daneIdentyfikacyjne) {
    const result = [];
    result.push(createLabelText('NIP: ', daneIdentyfikacyjne.NIP));
    if (hasValue(daneIdentyfikacyjne.ImiePierwsze) || hasValue(daneIdentyfikacyjne.Nazwisko)) {
        result.push(createLabelText('', `${getValue(daneIdentyfikacyjne.ImiePierwsze)} ${getValue(daneIdentyfikacyjne.Nazwisko)}`));
    }
    if (daneIdentyfikacyjne.PelnaNazwa) {
        result.push(createLabelText('Pełna nazwa: ', daneIdentyfikacyjne.PelnaNazwa));
    }
    if (daneIdentyfikacyjne.Nazwisko) {
        result.push(createLabelText('Nazwa handlowa: ', daneIdentyfikacyjne.NazwaHandlowa));
    }
    return result;
}
//# sourceMappingURL=PodmiotDaneIdentyfikacyjne.js.map