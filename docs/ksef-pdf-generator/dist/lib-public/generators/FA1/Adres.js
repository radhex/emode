import { createLabelText } from '../../../shared/PDF-functions.js';
export function generateAdres(adres) {
    const result = [];
    if (adres.AdresZagr) {
        const adresZagr = adres.AdresZagr;
        if (adresZagr.KodKraju) {
            result.push(createLabelText('Kraj: ', adresZagr.KodKraju));
        }
        if (adresZagr.Ulica) {
            result.push(createLabelText('Ulica: ', adresZagr.Ulica));
        }
        if (adresZagr.NrDomu) {
            result.push(createLabelText('Numer domu: ', adresZagr.NrDomu));
        }
        if (adresZagr.NrLokalu) {
            result.push(createLabelText('Numer lokalu: ', adresZagr.NrLokalu));
        }
        if (adresZagr.KodPocztowy) {
            result.push(createLabelText('Kod pocztowy: ', adresZagr.KodPocztowy));
        }
        if (adresZagr.Miejscowosc) {
            result.push(createLabelText('Miejscowość: ', adresZagr.Miejscowosc));
        }
        if (adresZagr.GLN) {
            result.push(createLabelText('GLN: ', adresZagr.GLN));
        }
    }
    if (adres.AdresPol) {
        const adresPol = adres.AdresPol;
        if (adresPol.Wojewodztwo) {
            result.push(createLabelText('Województwo: ', adresPol.Wojewodztwo));
        }
        if (adresPol.Powiat) {
            result.push(createLabelText('Powiat: ', adresPol.Powiat));
        }
        if (adresPol.Gmina) {
            result.push(createLabelText('Gmina: ', adresPol.Gmina));
        }
        if (adresPol.Ulica) {
            result.push(createLabelText('Ulica: ', adresPol.Ulica));
        }
        if (adresPol.NrDomu) {
            result.push(createLabelText('Numer domu: ', adresPol.NrDomu));
        }
        if (adresPol.NrLokalu) {
            result.push(createLabelText('Numer lokalu: ', adresPol.NrLokalu));
        }
        if (adresPol.KodPocztowy) {
            result.push(createLabelText('Kod pocztowy: ', adresPol.KodPocztowy));
        }
        if (adresPol.Miejscowosc) {
            result.push(createLabelText('Miejscowość: ', adresPol.Miejscowosc));
        }
        if (adresPol.Poczta) {
            result.push(createLabelText('Poczta: ', adresPol.Poczta));
        }
        if (adresPol.GLN) {
            result.push(createLabelText('GLN: ', adresPol.GLN));
        }
    }
    return result;
}
//# sourceMappingURL=Adres.js.map