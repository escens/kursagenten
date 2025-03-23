/**
 * Håndterer ekspanderbart innhold
 * Bruk klassen 'expand-content' og data-size="200" (eller ønsket høyde i px)
 * Eksempel: <div class="expand-content" data-size="200">Innhold...</div>
 */
function initExpandableContent() {
    const expandableElements = document.querySelectorAll('.expand-content');

    expandableElements.forEach(element => {
        const maxHeight = parseInt(element.dataset.size) || 200;
        
        // Sjekk om innholdet er høyere enn maksimal høyde
        if (element.scrollHeight > maxHeight) {
            // Legg til collapsed klasse og sett maks høyde
            element.classList.add('collapsed');
            element.style.maxHeight = `${maxHeight}px`;
            
            // Opprett toggle-knapp
            const toggleButton = document.createElement('div');
            toggleButton.className = 'expand-toggle';
            toggleButton.innerHTML = `Vis mer <i class="ka-icon icon-chevron-down"></i>`;
            
            // Legg til toggle-knapp etter innholdselementet
            element.parentNode.insertBefore(toggleButton, element.nextSibling);
            toggleButton.style.display = 'block';
            
            // Håndter klikk på toggle-knapp
            toggleButton.addEventListener('click', () => {
                const isExpanded = element.classList.contains('expanded');
                
                if (isExpanded) {
                    // Kollaps innhold
                    element.style.maxHeight = `${maxHeight}px`;
                    element.classList.remove('expanded');
                    element.classList.add('collapsed');
                    toggleButton.innerHTML = `Vis mer <i class="ka-icon icon-chevron-down"></i>`;
                } else {
                    // Ekspander innhold
                    element.style.maxHeight = `${element.scrollHeight}px`;
                    element.classList.add('expanded');
                    element.classList.remove('collapsed');
                    toggleButton.innerHTML = `Lukk <i class="ka-icon icon-chevron-up"></i>`;
                }
            });
        }
    });
}

// Kjør når DOM er lastet
document.addEventListener('DOMContentLoaded', initExpandableContent);
