function toggleAccordion(header) {
    const allItems = document.querySelectorAll(".accordion-content");
    const allIcons = document.querySelectorAll(".accordion-icon");
    const content = header.nextElementSibling;
    const icon = header.querySelector(".accordion-icon");

    // Lukk alle andre seksjoner
    allItems.forEach((item) => {
        if (item !== content) {
            item.style.height = "0";
            item.classList.remove("open");
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
        icon.textContent = "+";
    } else {
        // Åpne denne seksjonen
        content.classList.add("open");
        content.style.height = content.scrollHeight + "px";
        icon.textContent = "×";
    }
}
function buttonAction(event, message) {
    // Stopp event bubbling slik at raden ikke åpnes/lukkes
    event.stopPropagation();

    // Legg inn logikk for knappen
    alert(`Du klikket på knappen for ${message}`);
}