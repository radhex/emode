export class ConsoleLogger {
    info(message) {
        console.log(message);
    }
    success(message) {
        console.log(`✅ ${message}`);
    }
    error(message, error) {
        console.error(`❌ ${message}`, error || '');
    }
}
//# sourceMappingURL=ConsoleLogger.js.map