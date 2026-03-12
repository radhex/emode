import { promises as fs } from 'fs';
import path from 'path';
export class FileService {
    logger;
    constructor(logger) {
        this.logger = logger;
    }
    async readXmlFile(filePath) {
        const resolvedPath = path.resolve(filePath);
        await this.ensureFileExists(resolvedPath);
        return await fs.readFile(resolvedPath, 'utf-8');
    }
    async writePdfFile(blob, outputPath) {
        const resolvedPath = path.resolve(outputPath);
        const buffer = await this.convertBlobToBuffer(blob);
        const outputDir = path.dirname(resolvedPath);
        await fs.mkdir(outputDir, { recursive: true });
        await fs.writeFile(resolvedPath, buffer);
    }
    async ensureFileExists(filePath) {
        try {
            await fs.access(filePath);
        }
        catch {
            throw new Error(`Plik wejściowy nie istnieje: ${filePath}`);
        }
    }
    createFileFromContent(content, filename) {
        const FileConstructor = global.File;
        if (!FileConstructor) {
            throw new Error('Środowisko File nie zostało zainicjalizowane');
        }
        return new FileConstructor([content], filename, { type: 'application/xml' });
    }
    async convertBlobToBuffer(blob) {
        return new Promise((resolve, reject) => {
            const reader = new global.FileReader();
            reader.onload = () => {
                const arrayBuffer = reader.result;
                resolve(Buffer.from(arrayBuffer));
            };
            reader.onerror = () => reject(new Error('Błąd podczas odczytu Blob'));
            reader.readAsArrayBuffer(blob);
        });
    }
}
//# sourceMappingURL=FileService.js.map