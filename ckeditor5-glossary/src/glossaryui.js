import { Plugin, ButtonView, ContextualBalloon, clickOutsideHandler } from 'ckeditor5';
import GlossaryFormView from './ui/glossaryformview.js';

const GLOSSARY_ICON =
    '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M5 2h9a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v12h8V4H5zm1.5 2h5v1.5h-5V6zm0 3h5v1.5h-5V9z"/></svg>';

/**
 * The glossary toolbar button + balloon UI (search, create-on-the-fly, unlink).
 */
export default class GlossaryUI extends Plugin {
    static get requires() {
        return [ContextualBalloon];
    }

    static get pluginName() {
        return 'GlossaryUI';
    }

    init() {
        const editor = this.editor;

        this._balloon = editor.plugins.get(ContextualBalloon);
        this._searchTimer = null;

        editor.ui.componentFactory.add('glossary', (locale) => {
            const button = new ButtonView(locale);
            const command = editor.commands.get('glossary');

            button.set({
                label: locale.t('Glossary link'),
                icon: GLOSSARY_ICON,
                tooltip: true,
                isToggleable: true,
            });

            button.bind('isEnabled').to(command);
            button.bind('isOn').to(command, 'value', (value) => !!value);

            this.listenTo(button, 'execute', () => this._showUI());

            return button;
        });
    }

    _createFormView() {
        const editor = this.editor;
        const canCreate = !!(window.Craft && Craft.glossary && Craft.glossary.canCreate);
        const form = new GlossaryFormView(editor.locale, { canCreate });

        form.keystrokes.set('Esc', (data, cancel) => {
            this._hideUI();
            cancel();
        });

        // Debounced search as the editor types.
        form.searchInputView.fieldView.on('input', () => {
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(() => this._runSearch(form), 200);
        });

        form.on('select', (evt, uid) => {
            editor.execute('glossary', uid);
            this._hideUI();
            editor.editing.view.focus();
        });

        form.on('unlink', () => {
            editor.execute('glossary', null);
            this._hideUI();
            editor.editing.view.focus();
        });

        form.on('create', () => this._createTerm(form));

        return form;
    }

    _showUI() {
        const editor = this.editor;

        if (!this._form) {
            this._form = this._createFormView();
        }

        // Add to the balloon first so the form (and its buttons) are rendered before we touch
        // their DOM elements.
        if (!this._balloon.hasView(this._form)) {
            this._balloon.add({
                view: this._form,
                position: this._getBalloonPosition(),
            });
        } else {
            this._balloon.updatePosition(this._getBalloonPosition());
        }

        const command = editor.commands.get('glossary');
        this._form.showUnlink(!!command.value);
        this._form.showCreate(false);
        this._form.searchValue = '';
        this._form.setStatus(editor.locale.t('Type to search…'));
        this._form.focus();

        clickOutsideHandler({
            emitter: this._form,
            activator: () => this._balloon.visibleView === this._form,
            contextElements: [this._balloon.view.element],
            callback: () => this._hideUI(),
        });
    }

    _hideUI() {
        if (this._form && this._balloon.hasView(this._form)) {
            this._balloon.remove(this._form);
        }
    }

    _getBalloonPosition() {
        const view = this.editor.editing.view;
        const target = view.domConverter.viewRangeToDom(view.document.selection.getFirstRange());
        return { target };
    }

    async _runSearch(form) {
        const query = form.searchValue;
        form.showCreate(query.length > 0);

        if (query.length === 0) {
            form.setStatus(this.editor.locale.t('Type to search…'));
            return;
        }

        form.setStatus(this.editor.locale.t('Searching…'));

        try {
            const data = await this._request('ckeditor-glossary/terms/search', { q: query, siteId: this._siteId() }, 'GET');
            const terms = (data && data.terms) || [];

            if (terms.length === 0) {
                form.setStatus(this.editor.locale.t('No terms found.'));
            } else {
                form.setResults(terms);
            }
        } catch (e) {
            form.setStatus(this.editor.locale.t('Search failed.'));
        }
    }

    async _createTerm(form) {
        const name = form.searchValue;
        if (!name) {
            return;
        }

        form.setStatus(this.editor.locale.t('Creating…'));

        try {
            const data = await this._request(
                'ckeditor-glossary/terms/create-inline',
                { name, siteId: this._siteId() },
                'POST',
            );

            if (data && data.success && data.term) {
                this.editor.execute('glossary', data.term.uid);
                this._hideUI();
                this.editor.editing.view.focus();
            } else {
                form.setStatus((data && data.message) || this.editor.locale.t('Could not create term.'));
            }
        } catch (e) {
            form.setStatus(this.editor.locale.t('Could not create term.'));
        }
    }

    _siteId() {
        const input = document.querySelector('input[name="siteId"]');
        if (input && input.value) {
            return input.value;
        }
        return (window.Craft && Craft.siteId) || '';
    }

    _request(action, params, method) {
        if (!window.Craft) {
            return Promise.reject(new Error('Craft is unavailable.'));
        }

        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            [Craft.csrfTokenName]: Craft.csrfTokenValue,
        };

        if (method === 'GET') {
            const url = Craft.getActionUrl(action, params);
            return fetch(url, { method: 'GET', headers, credentials: 'same-origin' }).then((r) => r.json());
        }

        const body = new FormData();
        body.append(Craft.csrfTokenName, Craft.csrfTokenValue);
        Object.keys(params).forEach((key) => body.append(key, params[key]));

        return fetch(Craft.getActionUrl(action), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body,
        }).then((r) => r.json());
    }
}
