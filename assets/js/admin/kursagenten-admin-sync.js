jQuery(document).ready(function ($) {
    var courseData = []; // Lagre kursdata globalt
    var batchSize = 10; // Antall kurs å prosessere samtidig
    var offset = 0; // Start offset

    $("#sync-all-courses").on("click", function (e) {
        e.preventDefault();

        var $button = $(this);
        $button.addClass("processing");
        $("#sync-status-message").text("Synkronisering pågår...");

        // Første AJAX-kall for å hente kursdata
        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            data: {
                action: "get_course_ids",
                nonce: sync_kurs.nonce,
            },
            success: function (response) {
                if (response.success) {
                    courseData = response.data.courses;
                    processBatch($button); // Start batch-prosessering
                } else {
                    alert("Kunne ikke hente kursdata.");
                    resetSyncButton($button);
                }
            },
            error: function () {
                alert("Kunne ikke hente kursdata.");
                resetSyncButton($button);
            },
        });
    });

    function processBatch($button) {
        var batch = courseData.slice(offset, offset + batchSize);

        if (batch.length === 0) {
            $button.removeClass("processing");
            $("#sync-status-message").text("Alle kurs er synkronisert.");
            return;
        }

        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            data: {
                action: "run_sync_kurs",
                nonce: sync_kurs.nonce,
                courses: batch,
            },
            success: function (response) {
                if (response.success) {
                    offset += batchSize; // Gå til neste batch
                    processBatch($button); // Rekursiv kall for neste batch
                } else {
                    alert("Kunne ikke synkronisere en batch.");
                    resetSyncButton($button);
                }
            },
            error: function () {
                alert("Kunne ikke synkronisere en batch.");
                resetSyncButton($button);
            },
        });
    }

    function resetSyncButton($button) {
        $button.removeClass("processing");
        $("#sync-status-message").text("En feil oppstod under synkronisering.");
    }
});


/* jQuery(document).ready(function($) {
    var courseIds = []; // Store course IDs globally
    var batchSize = 10; // Number of courses to process at once
    var offset = 0; // Starting offset
    console.log("Js fungerer 2");

    $("#sync-all-courses").on("click", function(e) {
        e.preventDefault();

        var $link = $(this);

        // Add the processing class when clicked
        $link.addClass("processing");
        $("#sync-status-message").text("Synkronisering pågår...");

        // First AJAX call to get the list of course IDs
        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            timeout: 300000,
            data: {
                action: "get_course_ids",
                nonce: sync_kurs.nonce
            },
            success: function(response) {
                if (response.success) {
                    courseIds = response.data.courseIds;
                    processBatch($link);
                } else {
                    alert("Kunne ikke hente kurs-IDer.");
                    $link.removeClass("processing");
                    $("#sync-status-message").text("En feil oppstod under synkronisering.");
                }
            },
            error: function() {
                alert("Kunne ikke hente kurs-IDer.");
                $link.removeClass("processing");
                $("#sync-status-message").text("En feil oppstod under synkronisering.");
            }
        });
    });

    function processBatch($link) {
        var batch = courseIds.slice(offset, offset + batchSize);

        // If there are no more courses to process
        if (batch.length === 0) {
            $link.removeClass("processing");
            $("#sync-status-message").text("Alle kurs er nå hentet fra Kursagenten.");
            return;
        }

        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            data: {
                action: "run_sync_kurs",
                nonce: sync_kurs.nonce,
                courseIds: batch // Send only a batch of course IDs
            },
            success: function(response) {
                if (response.success) {
                    offset += batchSize; // Move to the next batch
                    processBatch($link); // Recursive call to process the next batch
                } else {
                    alert("Kunne ikke prosessere kursbatch.");
                    $link.removeClass("processing");
                    $("#sync-status-message").text("En feil oppstod under synkronisering.");
                }
            },
            error: function() {
                alert("Kunne ikke prosessere kursbatch.");
                $link.removeClass("processing");
                $("#sync-status-message").text("En feil oppstod under synkronisering.");
            }
        });
    }
});
 */