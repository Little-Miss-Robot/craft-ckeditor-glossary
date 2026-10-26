import {
    View,
    ListView,
    ButtonView,
    LabeledFieldView,
    createLabeledInputText,
    FocusCycler,
    FocusTracker,
    KeystrokeHandler,
    ViewCollection,
    submitHandler,
} from 'ckeditor5';

/**
 * The balloon panel for searching glossary terms, creating one on the fly, and unlinking.
 */
export default class GlossaryFormView extends View {
    constructor(locale, { canCreate }) {
        super(locale);

        const t = locale.t;
        this.canCreate = canCreate;

        this.focusTracker = new FocusTracker();
        this.keystrokes = new KeystrokeHandler();
        this._focusables = new ViewCollection();

        this.searchInputView = this._createSearchInput(t('Search glossary terms'));
        this.resultsView = new ListView(locale);
        this.resultsView.extendTemplate({
            attributes: { class: ['ck-glossary__results'] },
        });

        this.createButtonView = this._createButton(t('Create term'), 'ck-button-save');
        this.createButtonView.on('execute', () => this.fire('create'));

        this.unlinkButtonView = this._createButton(t('Remove glossary link'), 'ck-button-cancel');
        this.unlinkButtonView.on('execute', () => this.fire('unlink'));

        this._focusCycler = new FocusCycler({
            focusables: this._focusables,
            focusTracker: this.focusTracker,
            keystrokeHandler: this.keystrokes,
            actions: {
                focusPrevious: 'shift + tab',
                focusNext: 'tab',
            },
        });

        this.setTemplate({
            tag: 'form',
            attributes: {
                class: ['ck', 'ck-glossary-form'],
                tabindex: '-1',
            },
            children: [
                this.searchInputView,
                this.resultsView,
                {
                    tag: 'div',
                    attributes: { class: ['ck', 'ck-glossary-form__actions'] },
                    children: [this.createButtonView, this.unlinkButtonView],
                },
            ],
        });
    }

    render() {
        super.render();

        submitHandler({ view: this });

        const toTrack = [this.searchInputView, this.createButtonView, this.unlinkButtonView];
        toTrack.forEach((view) => {
            this._focusables.add(view);
            this.focusTracker.add(view.element);
        });

        this.keystrokes.listenTo(this.element);
    }

    destroy() {
        super.destroy();
        this.focusTracker.destroy();
        this.keystrokes.destroy();
    }

    focus() {
        this.searchInputView.focus();
    }

    get searchValue() {
        return (this.searchInputView.fieldView.element.value || '').trim();
    }

    set searchValue(value) {
        this.searchInputView.fieldView.value = value || '';
        if (this.searchInputView.fieldView.element) {
            this.searchInputView.fieldView.element.value = value || '';
        }
    }

    /**
     * Replaces the results list with buttons for the given terms.
     *
     * @param {Array<{uid:string,name:string,definition:string,language:string,siteName:string}>} terms
     */
    setResults(terms) {
        this.resultsView.items.clear();

        terms.forEach((term) => {
            const button = new ButtonView(this.locale);
            const badge = term.language ? ` [${term.language.toUpperCase()}]` : '';
            button.set({
                label: `${term.name}${badge}`,
                withText: true,
                tooltip: term.siteName || false,
                class: 'ck-glossary__result',
            });
            button.on('execute', () => this.fire('select', term.uid));
            this.resultsView.items.add(button);
        });
    }

    setStatus(message) {
        this.resultsView.items.clear();
        const label = new ButtonView(this.locale);
        label.set({ label: message, withText: true, isEnabled: false, class: 'ck-glossary__status' });
        this.resultsView.items.add(label);
    }

    showUnlink(show) {
        this.unlinkButtonView.element.style.display = show ? '' : 'none';
    }

    showCreate(show) {
        this.createButtonView.element.style.display = show && this.canCreate ? '' : 'none';
    }

    _createSearchInput(label) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);
        labeledInput.label = label;
        return labeledInput;
    }

    _createButton(label, className) {
        const button = new ButtonView(this.locale);
        button.set({
            label,
            withText: true,
            tooltip: false,
        });
        if (className) {
            button.extendTemplate({ attributes: { class: className } });
        }
        return button;
    }
}
