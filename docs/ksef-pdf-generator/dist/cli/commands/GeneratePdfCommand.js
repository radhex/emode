import path from 'path';
export class GeneratePdfCommand {
    generator;
    fileService;
    logger;
    inputPath;
    outputPath;
    commandName;
    constructor(generator, fileService, logger, inputPath, outputPath, commandName) {
        this.generator = generator;
        this.fileService = fileService;
        this.logger = logger;
        this.inputPath = inputPath;
        this.outputPath = outputPath;
        this.commandName = commandName;
    }
    async execute() {
        try {
            this.logger.info(`Generowanie ${this.commandName} PDF z: ${this.inputPath}`);
            const xmlContent = await this.fileService.readXmlFile(this.inputPath);
            const file = this.fileService.createFileFromContent(xmlContent, path.basename(this.inputPath));
            const pdfBlob = await this.generator.generate(file);
            await this.fileService.writePdfFile(pdfBlob, this.outputPath);
            this.logger.success(`${this.commandName} PDF wygenerowane pomyślnie: ${this.outputPath}`);
        }
        catch (error) {
            this.logger.error(`Błąd podczas generowania ${this.commandName} PDF:`, error);
            throw error;
        }
    }
}
//# sourceMappingURL=GeneratePdfCommand.js.map