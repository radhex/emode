import path from 'path';
export class GenerateConfirmationCommand {
    generator;
    fileService;
    logger;
    inputPath;
    outputPath;
    additionalData;
    constructor(generator, fileService, logger, inputPath, outputPath, additionalData) {
        this.generator = generator;
        this.fileService = fileService;
        this.logger = logger;
        this.inputPath = inputPath;
        this.outputPath = outputPath;
        this.additionalData = additionalData;
    }
    async execute() {
        try {
            this.logger.info(`Generowanie potwierdzenia transakcji PDF z: ${this.inputPath}`);
            const xmlContent = await this.fileService.readXmlFile(this.inputPath);
            const file = this.fileService.createFileFromContent(xmlContent, path.basename(this.inputPath));
            const pdfBlob = await this.generator.generate(file, this.additionalData);
            await this.fileService.writePdfFile(pdfBlob, this.outputPath);
            this.logger.success(`Potwierdzenie transakcji PDF wygenerowane pomyślnie: ${this.outputPath}`);
        }
        catch (error) {
            this.logger.error('Błąd podczas generowania potwierdzenia transakcji PDF:', error);
            throw error;
        }
    }
}
//# sourceMappingURL=GenerateConfirmationCommand.js.map