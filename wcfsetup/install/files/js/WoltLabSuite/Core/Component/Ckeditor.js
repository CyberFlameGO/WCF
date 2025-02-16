/**
 * The userland API for interactions with a CKEditor instance.
 *
 * The purpose of this implementation is to provide a stable and strongly typed
 * API that can be reused in components. Access to the raw API of CKEditor is
 * not exposed, if you feel that you need additional helper methods then please
 * submit an issue.
 *
 * @author Alexander Ebert
 * @copyright 2001-2023 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */
define(["require", "exports", "tslib", "./Ckeditor/Attachment", "./Ckeditor/Media", "./Ckeditor/Mention", "./Ckeditor/Quote", "./Ckeditor/Autosave", "./Ckeditor/Configuration", "./Ckeditor/Event", "./Ckeditor/SubmitOnEnter", "../Devtools", "ckeditor5-bundle"], function (require, exports, tslib_1, Attachment_1, Media_1, Mention_1, Quote_1, Autosave_1, Configuration_1, Event_1, SubmitOnEnter_1, Devtools_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.getCkeditorById = exports.getCkeditor = exports.setupCkeditor = void 0;
    Devtools_1 = tslib_1.__importDefault(Devtools_1);
    const instances = new WeakMap();
    class Ckeditor {
        #editor;
        #features;
        constructor(editor, features) {
            this.#editor = editor;
            this.#features = features;
        }
        async destroy() {
            (0, Event_1.dispatchToCkeditor)(this.sourceElement).destroy();
            await this.#editor.destroy();
        }
        discardDraft() {
            if (this.#features.autosave) {
                (0, Autosave_1.deleteDraft)(this.#features.autosave);
            }
        }
        focus() {
            this.#editor.editing.view.focus();
        }
        getHtml() {
            return this.#editor.data.get();
        }
        insertHtml(html) {
            const viewFragment = this.#editor.data.processor.toView(html);
            const modelFragment = this.#editor.data.toModel(viewFragment);
            this.#editor.model.insertContent(modelFragment);
        }
        insertText(text) {
            const div = document.createElement("div");
            div.textContent = text;
            this.insertHtml(div.innerHTML);
        }
        isVisible() {
            return this.#editor.ui.element.clientWidth !== 0;
        }
        setHtml(html) {
            this.#editor.data.set(html);
        }
        removeAll(model, attributes) {
            this.#editor.model.change((writer) => {
                const elements = findModelForRemoval(this.#editor.model.document.getRoot(), model, attributes);
                for (const element of elements) {
                    writer.remove(element);
                }
            });
        }
        reset() {
            this.setHtml("");
            (0, Event_1.dispatchToCkeditor)(this.sourceElement).reset({
                ckeditor: this,
            });
        }
        get element() {
            return this.#editor.ui.element;
        }
        get features() {
            return this.#features;
        }
        get sourceElement() {
            return this.#editor.sourceElement;
        }
    }
    function* findModelForRemoval(element, model, attributes) {
        if (element.is("element", model)) {
            let isMatch = true;
            Object.entries(attributes).forEach(([key, value]) => {
                if (!element.hasAttribute(key)) {
                    isMatch = false;
                }
                else if (element.getAttribute(key) !== value)
                    isMatch = false;
            });
            if (isMatch) {
                yield element;
                return;
            }
        }
        for (const child of element.getChildren()) {
            if (child.is("element")) {
                yield* findModelForRemoval(child, model, attributes);
            }
        }
    }
    function initializeFeatures(element, features) {
        (0, Event_1.dispatchToCkeditor)(element).setupFeatures({
            features,
        });
        if (features.autosave && Devtools_1.default._internal_.editorAutosave() === false) {
            features.autosave = "";
        }
        Object.freeze(features);
    }
    function initializeConfiguration(element, features, bbcodes) {
        const configuration = (0, Configuration_1.createConfigurationFor)(features);
        configuration.woltlabBbcode = bbcodes;
        if (features.autosave !== "") {
            (0, Autosave_1.initializeAutosave)(features.autosave, configuration);
        }
        (0, Event_1.dispatchToCkeditor)(element).setupConfiguration({
            configuration,
            features,
        });
        for (const { name } of bbcodes) {
            configuration.toolbar.push(`woltlabBbcode_${name}`);
        }
        return configuration;
    }
    async function setupCkeditor(element, features, bbcodes) {
        if (instances.has(element)) {
            throw new TypeError(`Cannot initialize the editor for '${element.id}' twice.`);
        }
        initializeFeatures(element, features);
        if (features.attachment) {
            (0, Attachment_1.setup)(element);
        }
        if (features.media) {
            (0, Media_1.setup)(element);
        }
        (0, Mention_1.setup)(element);
        if (features.quoteBlock) {
            (0, Quote_1.setup)(element);
        }
        const configuration = initializeConfiguration(element, features, bbcodes);
        const cke = await window.CKEditor5.create(element, configuration);
        const ckeditor = new Ckeditor(cke, features);
        if (features.autosave) {
            (0, Autosave_1.setupRestoreDraft)(cke, features.autosave);
        }
        instances.set(element, ckeditor);
        (0, Event_1.dispatchToCkeditor)(element).ready({
            ckeditor,
        });
        if (features.submitOnEnter) {
            (0, SubmitOnEnter_1.setup)(cke, ckeditor);
        }
        if (ckeditor.getHtml() === "") {
            (0, Event_1.dispatchToCkeditor)(element).discardRecoveredData();
        }
        const enableDebug = window.ENABLE_DEBUG_MODE && window.ENABLE_DEVELOPER_TOOLS;
        if (enableDebug && Devtools_1.default._internal_.editorInspector()) {
            void new Promise((resolve_1, reject_1) => { require(["@ckeditor/ckeditor5-inspector"], resolve_1, reject_1); }).then(tslib_1.__importStar).then((inspector) => {
                inspector.default.attach(cke);
            });
        }
        return ckeditor;
    }
    exports.setupCkeditor = setupCkeditor;
    function getCkeditor(element) {
        return instances.get(element);
    }
    exports.getCkeditor = getCkeditor;
    function getCkeditorById(id) {
        const element = document.getElementById(id);
        if (element === null) {
            throw new Error(`Unable to find an element with the id '${id}'.`);
        }
        return getCkeditor(element);
    }
    exports.getCkeditorById = getCkeditorById;
});
