export class UpoPdfGenerator {
    generateFn;
    constructor(generateFn) {
        this.generateFn = generateFn;
    }
    async generate(file) {
        return await this.generateFn(file);
    }
}
//# sourceMappingURL=UpoPdfGenerator.js.map