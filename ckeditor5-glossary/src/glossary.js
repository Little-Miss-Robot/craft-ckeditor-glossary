import { Plugin } from 'ckeditor5';
import GlossaryEditing from './glossaryediting.js';
import GlossaryUI from './glossaryui.js';

/**
 * The glossary plugin: links selected text to a glossary term, stored as
 * `<a class="glossary-link" data-glossary-uid="…">`.
 */
export default class Glossary extends Plugin {
    static get requires() {
        return [GlossaryEditing, GlossaryUI];
    }

    static get pluginName() {
        return 'Glossary';
    }
}
