import { Plugin } from 'ckeditor5';
import GlossaryCommand from './glossarycommand.js';

/**
 * Defines the glossary schema and the two-way conversion between the model attribute
 * `glossaryUid` and the `<a class="glossary-link" data-glossary-uid="…">` view element.
 */
export default class GlossaryEditing extends Plugin {
    static get pluginName() {
        return 'GlossaryEditing';
    }

    init() {
        const editor = this.editor;

        editor.model.schema.extend('$text', { allowAttributes: 'glossaryUid' });

        // Model attribute -> editing & data view (<a class="glossary-link" data-glossary-uid>).
        editor.conversion.for('downcast').attributeToElement({
            model: 'glossaryUid',
            view: (uid, { writer }) => {
                if (!uid) {
                    return;
                }

                return writer.createAttributeElement(
                    'a',
                    {
                        class: 'glossary-link',
                        'data-glossary-uid': uid,
                    },
                    { priority: 5 },
                );
            },
        });

        // Data view -> model attribute.
        editor.conversion.for('upcast').elementToAttribute({
            view: {
                name: 'a',
                attributes: {
                    'data-glossary-uid': true,
                },
            },
            model: {
                key: 'glossaryUid',
                value: (viewElement) => viewElement.getAttribute('data-glossary-uid'),
            },
        });

        editor.commands.add('glossary', new GlossaryCommand(editor));
    }
}
