/**
 * CourseMonitor Shared Frontend Utilities
 */

/**
 * Escapes unsafe HTML characters to prevent Stored and Reflected XSS vulnerabilities.
 * @param {*} str Input value
 * @return {string} Sanitized string safe for innerHTML interpolation
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
    });
}

/**
 * Safely sets an element's text content.
 * @param {string|HTMLElement} el 
 * @param {*} text 
 */
function safeText(el, text) {
    const node = typeof el === 'string' ? document.getElementById(el) : el;
    if (node) {
        node.textContent = text !== null && text !== undefined ? String(text) : '';
    }
}
