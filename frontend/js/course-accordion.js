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

    console.log("Åpner accordion for:", accordionItem);

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
        content.style.height = content.scrollHeight + "px";
        content.classList.add("open");
        accordionItem.classList.add("active");
        icon.textContent = "×";
    }
}



function initAccordion() {
    const elements = document.querySelectorAll(".clickopen");
    //console.log("initAccordion kjører... Antall elementer funnet:", elements.length);

    elements.forEach((element) => {
        element.removeEventListener("click", handleAccordionClick);
        element.addEventListener("click", handleAccordionClick);
    });
}




function handleAccordionClick(event) {
    toggleAccordion(event.target);
}

// Initialiser ved DOMContentLoaded
document.addEventListener("DOMContentLoaded", initAccordion);



/* HTML code to use in templates
Bruk alternativt denne for å åpne  onclick="toggleAccordion(this), ellers kan klassen "clickopen" kan brukes
<div class="accordion">
    <div class="courselist-item">
        <div class="courselist-header"">
            <div class="courselist-text-area">
                <span class="accordion-icon clickopen">+</span>
                <span class="courselist-title"><h3 class="course-title">TITLE</h3></span>
            </div>
            <button class="courselist-button" data-url="" onclick="buttonAction(event, 'row<?php echo $index + 1; ?>')">BUTTONTEXT</button>
        </div>
        <div class="accordion-content">
            <p>CONTENT</p>
        </div>
 </div>
*/