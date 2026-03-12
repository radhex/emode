import { DEFAULT_TABLE_LAYOUT, Kraj } from './consts/const.js';
import { formatDateTime, getFormaPlatnosciString } from './generators/common/functions.js';
import FormatTyp, { Answer, Position } from './enums/common.enum.js';
export function formatText(value, format = null, options = {}, currency = '') {
    if (!value) {
        return '';
    }
    const result = { text: value.toString() };
    Object.assign(result, options);
    if (format) {
        result.style = format;
        if (!Array.isArray(format)) {
            formatValue(format, result, value, currency);
        }
        else {
            format.forEach((item) => {
                formatValue(item, result, value, currency);
            });
        }
    }
    return result;
}
export function generateTable(array, keys) {
    const faRows = getTable(array);
    const headers = Object.entries(keys).map(([key, value]) => {
        return {
            name: key,
            title: value,
            format: FormatTyp.Default,
        };
    });
    const table = getContentTable(headers, faRows, '*');
    return table.content ?? [];
}
function formatValue(item, result, value, currency = '') {
    switch (item) {
        case FormatTyp.Currency:
            result.text = isNaN(Number(value))
                ? value
                : `${dotToComma(Number(value).toFixed(2))} ${currency}`;
            result.alignment = Position.RIGHT;
            break;
        case FormatTyp.CurrencyAbs:
            result.text = isNaN(Number(value))
                ? value
                : `${dotToComma(Math.abs(Number(value)).toFixed(2))} ${currency}`;
            result.alignment = Position.RIGHT;
            break;
        case FormatTyp.CurrencyGreater:
            result.text = isNaN(Number(value))
                ? value
                : `${dotToComma(Number(value).toFixed(2))} ${currency}`;
            result.fontSize = 10;
            break;
        case FormatTyp.Currency6:
            result.text = isNaN(Number(value))
                ? value
                : `${dotToComma(Number(value).toFixed(6))} ${currency}`;
            result.alignment = Position.RIGHT;
            break;
        case FormatTyp.DateTime:
            result.text = formatDateTime(value);
            break;
        case FormatTyp.Date:
            result.text = formatDateTime(value, false, true);
            break;
        case FormatTyp.FormOfPayment:
            result.text = getFormaPlatnosciString({ _text: value });
            break;
        case FormatTyp.Boolean:
            result.text = value === '1' ? Answer.TRUE : Answer.FALSE;
            break;
        case FormatTyp.Percentage:
            result.text = `${value}%`;
            break;
    }
}
function dotToComma(value) {
    return value.replace('.', ',');
}
export function hasValue(value) {
    return (!!((typeof value !== 'object' && value) || (typeof value === 'object' && value._text)) || value === 0);
}
export function getValue(value) {
    if (typeof value === 'object') {
        return value._text;
    }
    return value;
}
export function getNumber(value) {
    const text = getValue(value);
    if (!text) {
        return 0;
    }
    if (typeof text === 'number') {
        return text;
    }
    return parseFloat(text.toString());
}
export function getNumberRounded(value) {
    const number = getNumber(value);
    return Math.round(number * 100) / 100;
}
export function createLabelTextArray(data) {
    return [
        {
            text: data.map((textEl) => formatText(getValue(textEl.value) ?? '', textEl.formatTyp ?? FormatTyp.Label, {}, textEl.currency)),
        },
    ];
}
export function createLabelText(label, value, formatTyp = FormatTyp.Value, style = {}) {
    if (!value || (typeof value === 'object' && !value._text)) {
        return [];
    }
    if (typeof value === 'object') {
        return [
            {
                text: [formatText(label, FormatTyp.Label), formatText(value._text, formatTyp)],
                ...style,
            },
        ];
    }
    return [
        {
            text: [formatText(label, FormatTyp.Label), formatText(value, formatTyp)],
            ...style,
        },
    ];
}
export function createSection(content, isLineOnTop, margin) {
    return [
        {
            stack: [
                ...(content.length
                    ? [...(isLineOnTop ? [{ stack: [generateLine()], margin: [0, 8, 0, 0] }] : []), content]
                    : []),
            ],
            margin: margin ?? [0, 0, 0, 8],
        },
    ];
}
export function createHeader(text, margin) {
    return [
        {
            stack: [formatText(text, FormatTyp.HeaderContent)],
            margin: margin ?? [0, 8, 0, 8],
        },
    ];
}
export function createSubHeader(text, margin) {
    return [
        {
            stack: [formatText(text, FormatTyp.SubHeaderContent)],
            margin: margin ?? [0, 4, 0, 4],
        },
    ];
}
export function generateStyle() {
    return {
        styles: {
            columnMarginLeft: {
                margin: [4, 0, 0, 0],
            },
            columnMarginRight: {
                margin: [0, 0, 4, 0],
            },
            GrayBoldTitle: {
                fillColor: '#F6F7FA',
                bold: true,
            },
            GrayTitle: {
                fillColor: '#F6F7FA',
            },
            Label: {
                color: '#343A40',
                bold: true,
            },
            LabelMargin: {
                margin: [0, 12, 0, 1.3],
            },
            LabelSmallMargin: {
                margin: [0, 6, 0, 1.3],
            },
            LabelMedium: {
                color: '#343A40',
                bold: true,
                fontSize: 9,
            },
            LabelGreater: {
                color: '#343A40',
                bold: true,
                fontSize: 10,
            },
            Value: {
                color: '#343A40',
            },
            ValueMedium: {
                color: '#343A40',
                fontSize: 9,
            },
            Bold: {
                fontSize: 9,
                bold: true,
            },
            Description: {
                color: 'blue',
                bold: false,
            },
            HeaderPosition: {
                fontSize: 16,
                bold: true,
            },
            Right: {
                alignment: Position.RIGHT,
            },
            header: {
                fontSize: 12,
                bold: true,
                margin: [0, 12, 0, 5],
            },
            HeaderContent: {
                fontSize: 10,
                bold: true,
            },
            SubHeaderContent: {
                fontSize: 7,
                bold: true,
            },
            TitleContent: {
                fontSize: 10,
                bold: true,
            },
            Link: {
                color: 'blue',
            },
            MarginBottom4: {
                marginBottom: 4,
            },
            MarginBottom8: {
                marginBottom: 8,
            },
            MarginTop4: {
                marginTop: 4,
            },
        },
        defaultStyle: {
            font: 'Roboto',
            fontSize: 7,
            lineHeight: 1.2,
        },
    };
}
export function getTable(data) {
    if (!data) {
        return [];
    }
    if (Array.isArray(data)) {
        return data;
    }
    return [data];
}
export function getRowTable(data, formatColumn) {
    return data.map((el, index) => {
        if (Array.isArray(formatColumn)) {
            return formatText(el, formatColumn[index] ?? FormatTyp.Default);
        }
        return formatText(el, formatColumn ?? FormatTyp.Default);
    });
}
export function hasColumnsValue(name, data) {
    return data.some((el) => {
        return hasValue(el[name]);
    });
}
export function getDifferentColumnsValue(name, data) {
    const result = [];
    data.forEach((el) => {
        const val = getValue(el[name]);
        if (val) {
            const index = result.findIndex((el) => el.value === val);
            if (index < 0) {
                result.push({ value: val, count: 1 });
            }
            else {
                result[index].count++;
            }
        }
    });
    return result;
}
export function getContentTable(headers, data, defaultWidths, margin, wordBreak) {
    const fieldsWithValue = headers.filter((header) => {
        return data.some((d) => {
            const name = header.name;
            if (name === '' && d?._text) {
                return true;
            }
            if (name === '') {
                return false;
            }
            if (typeof d[name] === 'object' && d[name]?._text) {
                return true;
            }
            return !!(typeof d[name] !== 'object' && d[name]);
        });
    });
    if (fieldsWithValue.length < 1) {
        return { content: null, fieldsWithValue: [] };
    }
    const headerRow = getRowTable(fieldsWithValue.map((header) => header.title), FormatTyp.GrayBoldTitle);
    const tableBody = data.map((row) => {
        return fieldsWithValue.map((header) => {
            const fp = (header.name ? row[header.name] : row);
            const value = typeof fp === 'object' ? fp?._text : fp;
            return formatText(makeBreakable(header.mappingData && value ? header.mappingData[value] : (value ?? ''), wordBreak ?? 40), header.format ?? FormatTyp.Default, { rowSpan: fp?._rowSpan ?? 1 });
        });
    });
    return {
        fieldsWithValue: fieldsWithValue.map((el) => el.name),
        content: {
            table: {
                headerRows: 1,
                keepWithHeaderRows: 1,
                widths: fieldsWithValue.map((header) => header.width ?? defaultWidths),
                body: [headerRow, ...tableBody],
            },
            margin: margin ?? [0, 0, 0, 8],
            layout: DEFAULT_TABLE_LAYOUT,
        },
    };
}
export function generateTwoColumns(kol1, kol2, margin) {
    return {
        columns: [
            { stack: [kol1], width: '50%' },
            { stack: [kol2], width: '50%' },
        ],
        margin: margin ?? [0, 0, 0, 0],
        columnGap: 20,
    };
}
export function generateColumns(contents, style = undefined) {
    const width = (100 / contents.length).toFixed(0) + '%';
    const columns = contents.map((content) => ({ stack: content, width }));
    const columnStyle = style ? { ...style } : { columnGap: 20 };
    return {
        columns,
        ...columnStyle,
    };
}
export function generateQRCode(qrCode) {
    return qrCode
        ? {
            qr: qrCode,
            fit: 150,
            foreground: 'black',
            background: 'white',
            eccLevel: 'M',
        }
        : undefined;
}
export function verticalSpacing(height) {
    return { text: '\n', fontSize: height };
}
export function getKraj(kod) {
    if (Kraj[kod]) {
        return Kraj[kod];
    }
    return kod;
}
export function generateLine() {
    return {
        table: {
            widths: ['*'],
            body: [[{ text: ' ', fontSize: 1 }]],
        },
        layout: {
            hLineWidth: (i) => (i === 0 ? 1 : 0),
            vLineWidth: () => 0,
            hLineColor: function () {
                return '#c0bfc1';
            },
            paddingTop: () => 0,
            paddingBottom: () => 0,
        },
    };
}
export function makeBreakable(value, wordBreak = 40) {
    if (typeof value === 'string') {
        return value.replace(new RegExp(`(.{${wordBreak}})`, 'g'), '$1\u200B');
    }
    return value;
}
//# sourceMappingURL=PDF-functions.js.map