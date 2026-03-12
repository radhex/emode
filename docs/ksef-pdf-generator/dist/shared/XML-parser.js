import { xml2js } from 'xml-js';
export function stripPrefixes(obj) {
    if (Array.isArray(obj)) {
        return obj.map(stripPrefixes);
    }
    else if (typeof obj === 'object' && obj !== null) {
        return Object.fromEntries(Object.entries(obj).map(([key, value]) => [
            key.includes(':') ? key.split(':')[1] : key,
            stripPrefixes(value),
        ]));
    }
    return obj;
}
export function parseXML(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                const xmlStr = e.target?.result;
                const jsonDoc = stripPrefixes(xml2js(xmlStr, { compact: true }));
                resolve(jsonDoc);
            }
            catch (error) {
                reject(error);
            }
        };
        reader.readAsText(file);
    });
}
//# sourceMappingURL=XML-parser.js.map