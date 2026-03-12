import { Window } from 'happy-dom';
export class BrowserEnvironmentInitializer {
    dom;
    vfsStorage = {};
    initialize() {
        this.setupPdfMake();
        this.setupHappyDom();
        this.setupCanvasMock();
    }
    setupPdfMake() {
        global.pdfMake = {
            vfs: this.vfsStorage,
            fonts: {
                Roboto: {
                    normal: 'Roboto-Regular.ttf',
                    bold: 'Roboto-Medium.ttf',
                    italics: 'Roboto-Italic.ttf',
                    bolditalics: 'Roboto-MediumItalic.ttf'
                }
            },
            addVirtualFileSystem: (vfs) => {
                Object.assign(this.vfsStorage, vfs);
                console.log('✓ Fonty VFS załadowane:', Object.keys(vfs).length, 'plików');
            }
        };
    }
    setupHappyDom() {
        this.dom = new Window({
            url: 'http://localhost',
            width: 1024,
            height: 768
        });
        global.window = this.dom;
        global.document = this.dom.document;
        global.FileReader = this.dom.FileReader;
        global.Blob = this.dom.Blob;
        global.File = this.dom.File;
        global.DOMParser = this.dom.DOMParser;
        global.XMLSerializer = this.dom.XMLSerializer;
        this.dom.pdfMake = global.pdfMake;
    }
    setupCanvasMock() {
        global.HTMLCanvasElement = class HTMLCanvasElement {
            getContext() {
                return {
                    measureText: (text) => ({ width: text.length * 10 }),
                    fillText: () => { },
                    fillRect: () => { },
                };
            }
        };
    }
}
//# sourceMappingURL=BrowserEnvironmentInitializer.js.map