/**
 * Main JavaScript
 * 
 * @package Template Academyk
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons once
    initializeLucideIcons();
});

// Initialize Lucide icons function with debounce
let lucideTimeout;
function initializeLucideIcons() {
    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        clearTimeout(lucideTimeout);
        lucideTimeout = setTimeout(function() {
            try {
                lucide.createIcons();
            } catch (e) {
                console.error('Lucide icons initialization error:', e);
            }
        }, 50);
    }
}

// Make it globally available
window.initializeLucideIcons = initializeLucideIcons;
