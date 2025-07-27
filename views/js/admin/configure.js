
// IIFE for declaration of addEventListener of type 'click' and 'change' for input fields of type file
;(function () {
    'use strict';

    /**
     * Global `click` handler that targets buttons for hidden file inputs.
     * When a button (or any element) with a `data-file-input` attribute is clicked,
     * it resolves that attribute to a selector, finds the corresponding `<input type="file">`,
     * and programmatically triggers its `.click()` to open the native file chooser.
     *
     * Expected markup:
     *   <button type="button" data-file-input="#myFileInput">Choose a file</button> // Functional input
     *   <input id="myFileInput" type="file" class="hidden"> // Dummy input
     */
    document.addEventListener('click', function (e) {
        const btn = e.target.getAttribute('type') && e.target.getAttribute('type') === "button"
            ?? e.target;
        if (!btn) return;

        const fileSelector = btn.getAttribute('data-file-input');
        if (!fileSelector) return;

        const fileInput = document.querySelector(fileSelector.startsWith('#') ? fileSelector : '#' + fileSelector);
        if (fileInput) fileInput.click();
    });

    /**
     * Global `change` handler that reacts only to `<input type="file">` elements.
     * When a file input changes, it reads the selector stored in its
     * `data-text-input` attribute, finds the corresponding read‑only text field,
     * and fills it with the selected file’s name (or clears it if no file is chosen).
     *
     * Expected markup:
     *   <input type="file" data-text-input="#myTextField" /> // Functional input
     *   <input id="myTextField" type="text" readonly> // Dummy input
     */
    document.addEventListener('change', function (e) {
        const fileInput = e.target.getAttribute('type') && e.target.getAttribute('type') === "file"
            ?? e.target;
        if (!fileInput) return;

        const target = fileInput.getAttribute('data-text-input');
        if (!target) return;

        const textInput = document.querySelector(target);
        if (!textInput) return;

        // Affiche le(s) nom(s)
        if (fileInput.files && fileInput.files.length) {
            textInput.value = fileInput.files[0].name;
        } else {
            textInput.value = '';
        }
    });

})();
