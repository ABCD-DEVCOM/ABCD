/**
 * main.js
 * ABCD System Global Scripts
 * Data: 2026-04-21
 */

// ==========================================================================
// 1. MANAGEMENT OF THE GLOBAL PRELOADER
// ==========================================================================
// We use “load” to ensure that images, iframes and all sub-resources
// have finished loading before hiding the cogwheel.
window.addEventListener('load', function() {
    var telaCarregamento = document.getElementById('preloader');
    
    // Fail-safe: Only attempts to hide if the element exists on the current page
    if (telaCarregamento) {
        telaCarregamento.style.display = 'none';
    }
});

// ==========================================================================
// 2. CHANGE LANGUAGE (CambiarLenguaje)
// ==========================================================================
// The function is always available. It will only be triggered if the selector exists in the HTML.
function CambiarLenguaje() {
    // Search for the language selector on the page (by name or ID)
    var selectLang = document.querySelector('select[name="lenguaje"]') || document.getElementById('lenguaje');

    // Fail-safe: If the selector is not found (page without language switch), silently abort
    if (!selectLang) return;

    var lang = selectLang.value;
    var currentUrl = new URL(window.location.href);

    // Updates or adds the "lang" parameter to the URL in a clean and native way
    currentUrl.searchParams.set('lang', lang);

    // Redirects the page
    window.location.href = currentUrl.toString();
}


// ==========================================================================
// 3. ABCD GRID TABLES (Move, Delete, Duplicate, Add Row)
// ==========================================================================

// Moves the line up (-1) or down (1)
function moveRow(btn, direction) {
    var row = btn.closest("tr");
    var tbody = row.parentNode;
    if (direction === -1 && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
    } else if (direction === 1 && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
    }
}

// Delete the line (prompt for an optional confirmation message)
function deleteRow(btn, confirmMsg) {
    if (confirmMsg) {
        if (!confirm(confirmMsg)) return;
    }
    var row = btn.closest("tr");
    row.remove();
}

// Duplicates the row and preserves ALL values, regardless of the field name
function duplicateRow(btn) {
    var row = btn.closest("tr");
    var clone = row.cloneNode(true);

    // Copies the values from input fields, select elements and text areas dynamically
    var origElements = row.querySelectorAll("input, select, textarea");
    var cloneElements = clone.querySelectorAll("input, select, textarea");

    for (var i = 0; i < origElements.length; i++) {
        cloneElements[i].value = origElements[i].value;
    }

    row.parentNode.insertBefore(clone, row.nextSibling);
}

// Adds a new line based on a hidden ‘Template’ in the HTML
function addEmptyRow(tbodyId, templateId, position) {
    var tbody = document.getElementById(tbodyId);
    var template = document.getElementById(templateId);

    // Clone the template’s content
    var clone = template.content.cloneNode(true);

    if (position === 'BEFORE' && tbody.firstChild) {
        tbody.insertBefore(clone, tbody.firstChild);
    } else {
        tbody.appendChild(clone);
    }
}