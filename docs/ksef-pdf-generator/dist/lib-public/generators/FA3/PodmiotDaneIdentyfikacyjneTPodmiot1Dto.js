import { createLabelText } from '../../../shared/PDF-functions.js';
export function generateDaneIdentyfikacyjneTPodmiot1Dto(daneIdentyfikacyjne) {
    return [
        createLabelText('NIP: ', daneIdentyfikacyjne.NIP),
        createLabelText('Nazwa: ', daneIdentyfikacyjne.Nazwa),
    ];
}
//# sourceMappingURL=PodmiotDaneIdentyfikacyjneTPodmiot1Dto.js.map