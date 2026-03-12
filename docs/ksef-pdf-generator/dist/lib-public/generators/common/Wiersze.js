import { TRodzajFaktury } from '../../../shared/consts/const.js';
import { getValue } from '../../../shared/PDF-functions.js';
export function shouldAddMarza(rodzajFaktury, isP_PMarzy, wiersz) {
    if (typeof rodzajFaktury === 'string') {
        const isVATType = [
            TRodzajFaktury.VAT,
            TRodzajFaktury.KOR,
            TRodzajFaktury.ROZ,
            TRodzajFaktury.KOR_ROZ,
        ].includes(rodzajFaktury);
        const isZALType = [TRodzajFaktury.ZAL, TRodzajFaktury.KOR_ZAL].includes(rodzajFaktury);
        if (isP_PMarzy) {
            if (isVATType && !getValue(wiersz.P_12) && !getValue(wiersz.P_12_XII)) {
                return { P_12: { _text: 'marża' } };
            }
            else if (isZALType && !getValue(wiersz.P_12Z) && !getValue(wiersz.P_12Z_XII)) {
                return { P_12Z: { _text: 'marża' } };
            }
            else {
                return null;
            }
        }
    }
    return null;
}
//# sourceMappingURL=Wiersze.js.map