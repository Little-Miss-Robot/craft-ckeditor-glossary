// src/glossary.js
import { Plugin as Plugin3 } from "ckeditor5";

// src/glossaryediting.js
import { Plugin } from "ckeditor5";

// src/glossarycommand.js
import { Command } from "ckeditor5";
var GlossaryCommand = class extends Command {
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    this.value = selection.getAttribute("glossaryUid") || null;
    this.isEnabled = model.schema.checkAttributeInSelection(selection, "glossaryUid");
  }
  /**
   * @param {string|null} uid The glossary term UID, or null/empty to unlink.
   */
  execute(uid) {
    const model = this.editor.model;
    const selection = model.document.selection;
    model.change((writer) => {
      if (selection.isCollapsed) {
        const position = selection.getFirstPosition();
        const node = position.textNode || position.nodeBefore;
        if (!node) {
          return;
        }
        const range = writer.createRangeOn(node);
        if (uid) {
          writer.setAttribute("glossaryUid", uid, range);
        } else {
          writer.removeAttribute("glossaryUid", range);
        }
        return;
      }
      const ranges = model.schema.getValidRanges(selection.getRanges(), "glossaryUid");
      for (const range of ranges) {
        if (uid) {
          writer.setAttribute("glossaryUid", uid, range);
        } else {
          writer.removeAttribute("glossaryUid", range);
        }
      }
    });
  }
};

// src/glossaryediting.js
var GlossaryEditing = class extends Plugin {
  static get pluginName() {
    return "GlossaryEditing";
  }
  init() {
    const editor = this.editor;
    editor.model.schema.extend("$text", { allowAttributes: "glossaryUid" });
    editor.conversion.for("downcast").attributeToElement({
      model: "glossaryUid",
      view: (uid, { writer }) => {
        if (!uid) {
          return;
        }
        return writer.createAttributeElement(
          "a",
          {
            class: "glossary-link",
            "data-glossary-uid": uid
          },
          { priority: 5 }
        );
      }
    });
    editor.conversion.for("upcast").elementToAttribute({
      view: {
        name: "a",
        attributes: {
          "data-glossary-uid": true
        }
      },
      model: {
        key: "glossaryUid",
        value: (viewElement) => viewElement.getAttribute("data-glossary-uid")
      }
    });
    editor.commands.add("glossary", new GlossaryCommand(editor));
  }
};

// src/glossaryui.js
import { Plugin as Plugin2, ButtonView as ButtonView2, ContextualBalloon, clickOutsideHandler } from "ckeditor5";

// src/ui/glossaryformview.js
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
  submitHandler
} from "ckeditor5";
var GlossaryFormView = class extends View {
  constructor(locale, { canCreate }) {
    super(locale);
    const t = locale.t;
    this.canCreate = canCreate;
    this.focusTracker = new FocusTracker();
    this.keystrokes = new KeystrokeHandler();
    this._focusables = new ViewCollection();
    this.searchInputView = this._createSearchInput(t("Search glossary terms"));
    this.resultsView = new ListView(locale);
    this.resultsView.extendTemplate({
      attributes: { class: ["ck-glossary__results"] }
    });
    this.createButtonView = this._createButton(t("Create term"), "ck-button-save");
    this.createButtonView.on("execute", () => this.fire("create"));
    this.unlinkButtonView = this._createButton(t("Remove glossary link"), "ck-button-cancel");
    this.unlinkButtonView.on("execute", () => this.fire("unlink"));
    this._focusCycler = new FocusCycler({
      focusables: this._focusables,
      focusTracker: this.focusTracker,
      keystrokeHandler: this.keystrokes,
      actions: {
        focusPrevious: "shift + tab",
        focusNext: "tab"
      }
    });
    this.setTemplate({
      tag: "form",
      attributes: {
        class: ["ck", "ck-glossary-form"],
        tabindex: "-1"
      },
      children: [
        this.searchInputView,
        this.resultsView,
        {
          tag: "div",
          attributes: { class: ["ck", "ck-glossary-form__actions"] },
          children: [this.createButtonView, this.unlinkButtonView]
        }
      ]
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
    return (this.searchInputView.fieldView.element.value || "").trim();
  }
  set searchValue(value) {
    this.searchInputView.fieldView.value = value || "";
    if (this.searchInputView.fieldView.element) {
      this.searchInputView.fieldView.element.value = value || "";
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
      const badge = term.language ? ` [${term.language.toUpperCase()}]` : "";
      button.set({
        label: `${term.name}${badge}`,
        withText: true,
        tooltip: term.siteName || false,
        class: "ck-glossary__result"
      });
      button.on("execute", () => this.fire("select", term.uid));
      this.resultsView.items.add(button);
    });
  }
  setStatus(message) {
    this.resultsView.items.clear();
    const label = new ButtonView(this.locale);
    label.set({ label: message, withText: true, isEnabled: false, class: "ck-glossary__status" });
    this.resultsView.items.add(label);
  }
  showUnlink(show) {
    this.unlinkButtonView.element.style.display = show ? "" : "none";
  }
  showCreate(show) {
    this.createButtonView.element.style.display = show && this.canCreate ? "" : "none";
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
      tooltip: false
    });
    if (className) {
      button.extendTemplate({ attributes: { class: className } });
    }
    return button;
  }
};

// src/glossaryui.js
var GLOSSARY_ICON = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M5 2h9a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v12h8V4H5zm1.5 2h5v1.5h-5V6zm0 3h5v1.5h-5V9z"/></svg>';
var GlossaryUI = class extends Plugin2 {
  static get requires() {
    return [ContextualBalloon];
  }
  static get pluginName() {
    return "GlossaryUI";
  }
  init() {
    const editor = this.editor;
    this._balloon = editor.plugins.get(ContextualBalloon);
    this._searchTimer = null;
    editor.ui.componentFactory.add("glossary", (locale) => {
      const button = new ButtonView2(locale);
      const command = editor.commands.get("glossary");
      button.set({
        label: locale.t("Glossary link"),
        icon: GLOSSARY_ICON,
        tooltip: true,
        isToggleable: true
      });
      button.bind("isEnabled").to(command);
      button.bind("isOn").to(command, "value", (value) => !!value);
      this.listenTo(button, "execute", () => this._showUI());
      return button;
    });
  }
  _createFormView() {
    const editor = this.editor;
    const canCreate = !!(window.Craft && Craft.glossary && Craft.glossary.canCreate);
    const form = new GlossaryFormView(editor.locale, { canCreate });
    form.keystrokes.set("Esc", (data, cancel) => {
      this._hideUI();
      cancel();
    });
    form.searchInputView.fieldView.on("input", () => {
      clearTimeout(this._searchTimer);
      this._searchTimer = setTimeout(() => this._runSearch(form), 200);
    });
    form.on("select", (evt, uid) => {
      editor.execute("glossary", uid);
      this._hideUI();
      editor.editing.view.focus();
    });
    form.on("unlink", () => {
      editor.execute("glossary", null);
      this._hideUI();
      editor.editing.view.focus();
    });
    form.on("create", () => this._createTerm(form));
    return form;
  }
  _showUI() {
    const editor = this.editor;
    if (!this._form) {
      this._form = this._createFormView();
    }
    if (!this._balloon.hasView(this._form)) {
      this._balloon.add({
        view: this._form,
        position: this._getBalloonPosition()
      });
    } else {
      this._balloon.updatePosition(this._getBalloonPosition());
    }
    const command = editor.commands.get("glossary");
    this._form.showUnlink(!!command.value);
    this._form.showCreate(false);
    this._form.searchValue = "";
    this._form.setStatus(editor.locale.t("Type to search\u2026"));
    this._form.focus();
    clickOutsideHandler({
      emitter: this._form,
      activator: () => this._balloon.visibleView === this._form,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideUI()
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
      form.setStatus(this.editor.locale.t("Type to search\u2026"));
      return;
    }
    form.setStatus(this.editor.locale.t("Searching\u2026"));
    try {
      const data = await this._request("ckeditor-glossary/terms/search", { q: query, siteId: this._siteId() }, "GET");
      const terms = data && data.terms || [];
      if (terms.length === 0) {
        form.setStatus(this.editor.locale.t("No terms found."));
      } else {
        form.setResults(terms);
      }
    } catch (e) {
      form.setStatus(this.editor.locale.t("Search failed."));
    }
  }
  async _createTerm(form) {
    const name = form.searchValue;
    if (!name) {
      return;
    }
    form.setStatus(this.editor.locale.t("Creating\u2026"));
    try {
      const data = await this._request(
        "ckeditor-glossary/terms/create-inline",
        { name, siteId: this._siteId() },
        "POST"
      );
      if (data && data.success && data.term) {
        this.editor.execute("glossary", data.term.uid);
        this._hideUI();
        this.editor.editing.view.focus();
      } else {
        form.setStatus(data && data.message || this.editor.locale.t("Could not create term."));
      }
    } catch (e) {
      form.setStatus(this.editor.locale.t("Could not create term."));
    }
  }
  _siteId() {
    const input = document.querySelector('input[name="siteId"]');
    if (input && input.value) {
      return input.value;
    }
    return window.Craft && Craft.siteId || "";
  }
  _request(action, params, method) {
    if (!window.Craft) {
      return Promise.reject(new Error("Craft is unavailable."));
    }
    const headers = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      [Craft.csrfTokenName]: Craft.csrfTokenValue
    };
    if (method === "GET") {
      const url = Craft.getActionUrl(action, params);
      return fetch(url, { method: "GET", headers, credentials: "same-origin" }).then((r) => r.json());
    }
    const body = new FormData();
    body.append(Craft.csrfTokenName, Craft.csrfTokenValue);
    Object.keys(params).forEach((key) => body.append(key, params[key]));
    return fetch(Craft.getActionUrl(action), {
      method: "POST",
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
      body
    }).then((r) => r.json());
  }
};

// src/glossary.js
var Glossary = class extends Plugin3 {
  static get requires() {
    return [GlossaryEditing, GlossaryUI];
  }
  static get pluginName() {
    return "Glossary";
  }
};
export {
  Glossary
};
