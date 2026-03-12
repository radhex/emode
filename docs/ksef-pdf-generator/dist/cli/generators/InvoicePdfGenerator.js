export class InvoicePdfGenerator {
    generateFn;
    constructor(generateFn) {
        this.generateFn = generateFn;
    }
    async generate(file, additionalData) {
        return await this.generateFn(file, additionalData, 'blob');
    }
}
//# sourceMappingURL=InvoicePdfGenerator.js.map