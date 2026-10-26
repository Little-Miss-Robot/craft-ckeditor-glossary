import { Command } from 'ckeditor5';

/**
 * Applies or removes the `glossaryUid` attribute on the current selection.
 *
 * Executing with a `uid` wraps the selected text in a glossary link; executing with a falsy
 * `uid` removes the glossary link from the selection.
 */
export default class GlossaryCommand extends Command {
    refresh() {
        const model = this.editor.model;
        const selection = model.document.selection;

        this.value = selection.getAttribute('glossaryUid') || null;
        this.isEnabled = model.schema.checkAttributeInSelection(selection, 'glossaryUid');
    }

    /**
     * @param {string|null} uid The glossary term UID, or null/empty to unlink.
     */
    execute(uid) {
        const model = this.editor.model;
        const selection = model.document.selection;

        model.change((writer) => {
            if (selection.isCollapsed) {
                // Collapsed selection: toggle on the text node under the caret, if any.
                const position = selection.getFirstPosition();
                const node = position.textNode || position.nodeBefore;

                if (!node) {
                    return;
                }

                const range = writer.createRangeOn(node);
                if (uid) {
                    writer.setAttribute('glossaryUid', uid, range);
                } else {
                    writer.removeAttribute('glossaryUid', range);
                }
                return;
            }

            const ranges = model.schema.getValidRanges(selection.getRanges(), 'glossaryUid');

            for (const range of ranges) {
                if (uid) {
                    writer.setAttribute('glossaryUid', uid, range);
                } else {
                    writer.removeAttribute('glossaryUid', range);
                }
            }
        });
    }
}
