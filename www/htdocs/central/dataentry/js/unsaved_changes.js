/*
 * unsaved_changes.js
 *
 * Detects unsaved changes in the record edit form (document.forma1) and, when the
 * user cancels the edit, asks whether to save, discard or keep editing.
 *
 * How it works
 *   1. When the page has fully loaded, a snapshot of the record fields is taken.
 *   2. The toolbar "cancel edit" button calls top.Menu("cancelar") (inicio_main.php),
 *      which first calls ABCD_promptUnsavedChanges() defined here.
 *   3. The current state of the form is compared with the snapshot. If it differs,
 *      a dialog is shown and the cancel is postponed (the function returns true).
 *      Otherwise it returns false and Menu() cancels the edit as before.
 *
 * Comparing snapshots (instead of listening to input/change events) also detects
 * the changes that are made by script: the "clear field" button, calendar, file
 * upload, index and subfield popups, drag & drop of table rows, add/remove of
 * inline subfield rows, etc.
 *
 * Settings come from scripts_dataentry.php in window.ABCD_unsavedConfig:
 *   forceDirty   : true when the form is shown again after a validation error. The
 *                  values on screen were never saved, so there is no clean baseline.
 *   saveFunction : name of the function that saves the form
 *   msg          : dialog texts (title, text, save, discard, keep)
 */
(function () {
    "use strict";

    var cfg = window.ABCD_unsavedConfig || {};
    var msg = cfg.msg || {};
    var DIALOG_ID = "abcd-unsaved-dialog";

    var baseline = null;   // snapshot of the form, taken after the page has loaded
    var ckBaseline = {};   // CKEditor: instance name -> data when the editor became ready

    /* ---------------------------------------------------------------- snapshot */

    // CKEditor instance that replaces this textarea (null if there is none)
    function ckInstance(el) {
        if (typeof CKEDITOR === "undefined" || !CKEDITOR.instances) return null;
        return (el.id && CKEDITOR.instances[el.id]) || (el.name && CKEDITOR.instances[el.name]) || null;
    }

    // Serialize the record data of the form into a single string.
    // Record data lives in the fields named "tag..." (the rule used by
    // FormarValorCapturado() when saving). Inline subfield inputs have no name,
    // they are read directly through their css class.
    function snapshot() {
        var form = document.forma1;
        if (!form) return null;

        var parts = [];
        for (var i = 0; i < form.elements.length; i++) {
            var el = form.elements[i];
            var type = (el.type || "").toLowerCase();
            var name = el.name || "";
            var isInline = !!(el.classList && el.classList.contains("inline-sub-input"));

            if (type === "button" || type === "submit" || type === "reset" || type === "image" || type === "file") continue;
            if (!isInline && name.substr(0, 3) !== "tag") continue;

            // The hidden tagNNN of an inline subfield editor is rebuilt from the
            // visible inputs; use the visible inputs to avoid false positives.
            if (!isInline && type === "hidden" && document.getElementById("tbody_" + name.substr(3))) continue;

            // Rich text: compared through the CKEditor API (see ckChanged)
            if (type === "textarea" && ckInstance(el)) continue;

            var value;
            if (type === "radio" || type === "checkbox") {
                if (!el.checked) continue;
                value = el.value;
            } else if (type === "select-multiple") {
                var selected = [];
                for (var j = 0; j < el.options.length; j++) {
                    if (el.options[j].selected) selected.push(el.options[j].value);
                }
                value = selected.join("\u0001");
            } else {
                value = el.value;
            }

            value = String(value).replace(/\r\n?/g, "\n");
            if (value === "") continue;   // an empty field (or an empty new row) is not data

            var key = isInline ? "inline:" + el.getAttribute("data-subcode") : name;
            parts.push(key + "=" + value);
        }
        return parts.join("\u0000");
    }

    /* ---------------------------------------------------------------- CKEditor */

    if (typeof CKEDITOR !== "undefined" && CKEDITOR.on) {
        // getData() is normalized by the editor, so take the baseline when it is ready
        CKEDITOR.on("instanceReady", function (ev) {
            ckBaseline[ev.editor.name] = ev.editor.getData();
        });
    }

    function ckChanged() {
        if (typeof CKEDITOR === "undefined" || !CKEDITOR.instances) return false;
        for (var name in CKEDITOR.instances) {
            if (!Object.prototype.hasOwnProperty.call(CKEDITOR.instances, name)) continue;
            var editor = CKEDITOR.instances[name];
            if (editor.status !== "ready" || !(name in ckBaseline)) continue;   // still loading
            if (editor.getData() !== ckBaseline[name]) return true;
        }
        return false;
    }

    window.addEventListener("load", function () {
        baseline = snapshot();
    });

    function hasChanges() {
        if (cfg.forceDirty) return true;
        if (baseline === null) return false;   // page not loaded yet: nothing was edited
        return snapshot() !== baseline || ckChanged();
    }

    /* ------------------------------------------------------------------ dialog */

    function closeDialog() {
        var dialog = document.getElementById(DIALOG_ID);
        if (dialog) dialog.parentNode.removeChild(dialog);
        document.removeEventListener("keydown", onKeyDown, true);
    }

    function saveChanges() {
        closeDialog();
        var save = window[cfg.saveFunction || "EnviarForma"];
        if (typeof save === "function") save();
    }

    function discardChanges() {
        closeDialog();
        top.Menu("cancelar", true);   // true: skip the unsaved-changes check
    }

    function onKeyDown(ev) {
        var dialog = document.getElementById(DIALOG_ID);
        if (!dialog) return;
        if (ev.key === "Escape" || ev.keyCode === 27) {
            ev.preventDefault();
            ev.stopPropagation();
            closeDialog();
        } else if (ev.key === "Tab" || ev.keyCode === 9) {
            // keep the keyboard focus inside the dialog
            var buttons = dialog.getElementsByTagName("button");
            var first = buttons[0];
            var last = buttons[buttons.length - 1];
            if (ev.shiftKey && document.activeElement === first) {
                ev.preventDefault();
                last.focus();
            } else if (!ev.shiftKey && document.activeElement === last) {
                ev.preventDefault();
                first.focus();
            }
        }
    }

    function makeButton(label, cssClass, onClick) {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "bt " + cssClass;
        button.textContent = label;
        button.addEventListener("click", onClick);
        return button;
    }

    function showDialog() {
        if (document.getElementById(DIALOG_ID)) return;

        var overlay = document.createElement("div");
        overlay.id = DIALOG_ID;
        overlay.setAttribute("role", "alertdialog");
        overlay.setAttribute("aria-modal", "true");
        overlay.setAttribute("aria-labelledby", DIALOG_ID + "-title");
        overlay.setAttribute("aria-describedby", DIALOG_ID + "-text");
        overlay.style.cssText = "position:fixed;top:0;left:0;right:0;bottom:0;z-index:2147483647;" +
            "background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;";

        var box = document.createElement("div");
        box.style.cssText = "background:#fff;color:#222;border-radius:6px;padding:22px 26px;" +
            "max-width:460px;width:90%;box-shadow:0 8px 28px rgba(0,0,0,.35);font-family:inherit;";

        var title = document.createElement("h3");
        title.id = DIALOG_ID + "-title";
        title.style.cssText = "margin:0 0 10px 0;font-size:18px;";
        title.textContent = msg.title || "Unsaved changes";

        var text = document.createElement("p");
        text.id = DIALOG_ID + "-text";
        text.style.cssText = "margin:0 0 20px 0;line-height:1.4;";
        text.textContent = msg.text || "This record has unsaved changes. What do you want to do?";

        var actions = document.createElement("div");
        actions.style.cssText = "display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;";

        var keepButton = makeButton(msg.keep || "Keep editing", "bt-gray", closeDialog);
        var discardButton = makeButton(msg.discard || "Discard changes", "bt-red", discardChanges);
        var saveButton = makeButton(msg.save || "Save changes", "bt-green", saveChanges);
        actions.appendChild(keepButton);
        actions.appendChild(discardButton);
        actions.appendChild(saveButton);

        box.appendChild(title);
        box.appendChild(text);
        box.appendChild(actions);
        overlay.appendChild(box);
        document.body.appendChild(overlay);

        document.addEventListener("keydown", onKeyDown, true);
        saveButton.focus();
    }

    /* ---------------------------------------------------------------- public API */

    // Called by top.Menu("cancelar"). Returns true when the form has unsaved changes:
    // the dialog is shown and the caller must NOT cancel the edit now.
    window.ABCD_promptUnsavedChanges = function () {
        if (!hasChanges()) return false;
        showDialog();
        return true;
    };
})();
