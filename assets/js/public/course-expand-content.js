/**
 * Håndterer ekspanderbart innhold og accordion-funksjonalitet
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
            toggleButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Hindre at accordion-triggeren aktiveres
                toggleExpand(element, toggleButton, maxHeight);
            });
        }
    });
}

// Funksjon for å håndtere expand/collapse
function toggleExpand(element, toggleButton, maxHeight) {
    const isExpanded = element.classList.contains('expanded');
    
    if (isExpanded) {
        // Kollaps innhold
        element.style.maxHeight = `${maxHeight}px`;
        element.classList.remove('expanded');
        element.classList.add('collapsed');
        toggleButton.innerHTML = `Vis mer <i class="ka-icon icon-chevron-down"></i>`;
    } else {
        // Ekspander innhold
        element.style.maxHeight = 'none';
        element.classList.add('expanded');
        element.classList.remove('collapsed');
        toggleButton.innerHTML = `Lukk <i class="ka-icon icon-chevron-up"></i>`;
    }
}

function toggleAccordionHeight(target) {
    const accordionItem = target.closest(".courselist-item");
    if (!accordionItem) {
        //console.error("Feil: Fant ikke .courselist-item for", target);
        return;
    }

    const expandContent = accordionItem.closest(".expand-content");
    if (!expandContent) {
        //console.log("Feil: Fant ikke .expand-content for", accordionItem);
        return;
    }

    const isExpanded = expandContent.classList.contains("expanded");

    // Bare øk høyden hvis den ikke allerede er utvidet
    if (!isExpanded) {
        // Fjern maxHeight for å la innholdet vokse fritt
        expandContent.style.maxHeight = 'none';
        expandContent.classList.add("expanded");
        expandContent.classList.remove("collapsed");
        
        // Oppdater knappetekst
        const toggleButton = expandContent.parentNode.querySelector('.expand-toggle');
        if (toggleButton) {
            toggleButton.innerHTML = `Lukk <i class="ka-icon icon-chevron-up"></i>`;
        }
    }
}

// Funksjon for å håndtere accordion

function toggleAccordion(target) {
    const accordionItem = target.closest(".courselist-item");
    if (!accordionItem) {
        console.error("Feil: Fant ikke .courselist-item for", target);
        return;
    }

    // Støtter både courselist-content og accordion-content
    const content = accordionItem.querySelector(".courselist-content, .accordion-content");
    const icon = accordionItem.querySelector(".accordion-icon");

    if (!content || !icon) {
        console.error("Feil: Fant ikke content eller accordion-icon for", accordionItem);
        return;
    }

    // Oppdatert selektor for å støtte begge typer innhold
    const allContents = document.querySelectorAll(".courselist-content, .accordion-content");
    const allItems = document.querySelectorAll(".courselist-item");
    const allIcons = document.querySelectorAll(".accordion-icon");

    // Lukk alle andre seksjoner
    allContents.forEach((otherContent) => {
        if (otherContent !== content) {
            otherContent.style.height = "0";
            otherContent.classList.remove("open");
        }
    });
    allItems.forEach((otherItem) => {
        if (otherItem !== accordionItem) {
            otherItem.classList.remove("active");
        }
    });
    allIcons.forEach((otherIcon) => {
        if (otherIcon !== icon) {
            otherIcon.textContent = "+";
        }
    });

    if (content.classList.contains("open")) {
        // Lukk denne seksjonen
        content.style.height = "0";
        content.classList.remove("open");
        accordionItem.classList.remove("active");
        icon.textContent = "+";
    } else {
        // Åpne denne seksjonen
        content.style.height = content.scrollHeight + 10 + "px";
        content.classList.add("open");
        accordionItem.classList.add("active");
        icon.textContent = "×";
    }

    // Kall på toggleAccordionHeight for å håndtere expand-content
    toggleAccordionHeight(target);
}

// Funksjoner for å håndtere clickopen elementer
function initAccordion() {
    const elements = document.querySelectorAll(".clickopen");
    
    elements.forEach((element) => {
        element.removeEventListener("click", handleAccordionClick);
        element.addEventListener("click", handleAccordionClick);
    });
}

function handleAccordionClick(event) {
    toggleAccordion(event.target);
}

// Kjør når DOM er lastet
document.addEventListener('DOMContentLoaded', function() {
    initExpandableContent();
    initAccordion();
});
