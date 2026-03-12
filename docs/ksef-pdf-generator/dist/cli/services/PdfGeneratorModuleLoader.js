export class PdfGeneratorModuleLoader {
    logger;
    constructor(logger) {
        this.logger = logger;
    }
    async loadGenerators() {
        try {
            const { generateInvoice, generatePDFUPO } = await import("../../lib-public/index.js");
            if (!generateInvoice || !generatePDFUPO) {
                throw new Error('Moduł nie eksportuje wymaganych funkcji');
            }
            this.logger.info('✓ Bundel ksef-pdf-generator załadowany pomyślnie');
            return { generateInvoice, generatePDFUPO };
        }
        catch (error) {
            this.logger.error('Błąd podczas ładowania bundla ksef-pdf-generator:', error);
            throw error;
        }
    }
}
//# sourceMappingURL=PdfGeneratorModuleLoader.js.map