import FormatTyp from '../../../shared/enums/common.enum.js';
import { createHeader, createLabelText, createSection, createSubHeader, formatText, generateLine, generateQRCode, generateTwoColumns, getContentTable, getTable, verticalSpacing, } from '../../../shared/PDF-functions.js';
import { generateZalaczniki } from './Zalaczniki.js';
export function generateStopka(additionalData, stopka, naglowek, wz, zalacznik) {
    const wzty = generateWZ(wz);
    const rejestry = generateRejestry(stopka);
    const informacje = generateInformacje(stopka);
    const qrCode = generateQRCodeData(additionalData);
    const zalaczniki = !additionalData?.isMobile ? generateZalaczniki(zalacznik) : [];
    const result = [
        verticalSpacing(1),
        ...(wzty.length ? [generateLine()] : []),
        ...(wzty.length ? [generateTwoColumns(wzty, [])] : []),
        ...(rejestry.length || informacje.length ? [generateLine()] : []),
        ...rejestry,
        ...informacje,
        ...(zalaczniki.length ? zalaczniki : []),
        { stack: [...qrCode], unbreakable: true },
        createSection([
            {
                stack: createLabelText('Wytworzona w: ', naglowek?.SystemInfo),
                margin: [0, 8, 0, 0],
            },
        ], true, [0, 0, 0, 0]),
    ];
    return createSection(result, false);
}
function generateWZ(wz) {
    const result = [];
    const definedHeader = [{ name: '', title: 'Numer WZ', format: FormatTyp.Default }];
    const faWiersze = getTable(wz ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*');
    if (content.fieldsWithValue.length && content.content) {
        result.push(createSubHeader('Numery dokumentów magazynowych WZ', [0, 8, 0, 4]));
        result.push(content.content);
    }
    return result;
}
function generateRejestry(stopka) {
    const result = [];
    const definedHeader = [
        { name: 'PelnaNazwa', title: 'Pełna nazwa', format: FormatTyp.Default },
        { name: 'KRS', title: 'KRS', format: FormatTyp.Default },
        { name: 'REGON', title: 'REGON', format: FormatTyp.Default },
        { name: 'BDO', title: 'BDO', format: FormatTyp.Default },
    ];
    const faWiersze = getTable(stopka?.Rejestry ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*');
    if (content.fieldsWithValue.length && content.content) {
        result.push(createHeader('Rejestry'));
        result.push(content.content);
    }
    return result;
}
function generateInformacje(stopka) {
    const result = [];
    const definedHeader = [
        { name: 'StopkaFaktury', title: 'Stopka faktury', format: FormatTyp.Default },
    ];
    const faWiersze = getTable(stopka?.Informacje ?? []);
    const content = getContentTable([...definedHeader], faWiersze, '*');
    if (content.fieldsWithValue.length && content.content) {
        result.push(createHeader('Pozostałe informacje'));
        result.push(content.content);
    }
    return result;
}
export function generateQRCodeData(additionalData, captions = true) {
    const result = [];
    if (additionalData?.qrCode) {
        const qrCode = generateQRCode(additionalData.qrCode);
        result.push(createHeader('Sprawdź, czy Twoja faktura znajduje się w KSeF!'));
        if (qrCode) {
            result.push({
                columns: [
                    {
                        stack: [
                            qrCode,
                            ...(captions
                                ? [
                                    {
                                        stack: [formatText(additionalData.nrKSeF ?? 'OFFLINE', FormatTyp.Default)],
                                        width: 'auto',
                                        alignment: 'center',
                                        marginLeft: 0,
                                        marginRight: 65,
                                        marginTop: 10,
                                    },
                                ]
                                : []),
                        ],
                        width: 200,
                    },
                    {
                        stack: [
                            formatText('Nie możesz zeskanować kodu z obrazka? Kliknij w link weryfikacyjny i przejdź do weryfikacji faktury!', FormatTyp.Value),
                            { stack: [formatText(additionalData.qrCode, FormatTyp.Link)], marginTop: 5 },
                        ],
                        link: additionalData.qrCode,
                        margin: [10, (qrCode.fit ?? 120) / 2 - 30, 0, 0],
                        width: 'auto',
                    },
                ],
            });
        }
    }
    if (additionalData?.qrCode2 && !additionalData.nrKSeF) {
        const qrCode = generateQRCode(additionalData.qrCode2);
        result.push(createHeader('Zweryfikuj wystawcę faktury!'));
        if (qrCode) {
            qrCode.fit = 200;
            result.push({
                columns: [
                    {
                        stack: [
                            qrCode,
                            ...(captions
                                ? [
                                    {
                                        stack: [formatText('CERTYFIKAT', FormatTyp.Default)],
                                        width: 'auto',
                                        alignment: 'center',
                                        marginLeft: 0,
                                        // ECDSA certificate QR Code fit almost full width so we need to increase margin
                                        marginRight: additionalData.qrCode2.length > 300 ? 28 : 18,
                                        marginTop: 10,
                                    },
                                ]
                                : []),
                        ],
                        width: 200,
                    },
                    {
                        stack: [
                            formatText('Nie możesz zeskanować kodu z obrazka? Kliknij w link weryfikacyjny i przejdź do weryfikacji wystawcy!', FormatTyp.Value),
                            {
                                stack: [formatText(additionalData.qrCode2.substring(0, 150) + '...', FormatTyp.Link)],
                                marginTop: 5,
                            },
                        ],
                        link: additionalData.qrCode2,
                        noWrap: false,
                        margin: [10, (qrCode.fit ?? 120) / 2 - 30, 0, 0],
                        width: 'auto',
                    },
                ],
            });
        }
    }
    return createSection(result, true);
}
//# sourceMappingURL=Stopka.js.map