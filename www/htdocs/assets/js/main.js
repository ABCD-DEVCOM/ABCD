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