jQuery(document).ready(function ($) {
    var courseData = []; // Lagre kursdata globalt
    var batchSize = 20; // Antall kurs å prosessere samtidig (økt fra 10 til 20)
    var offset = 0; // Start offset
    var totalCourses = 0; // Total antall kurs

    $("#sync-all-courses").on("click", function (e) {
        e.preventDefault();

        var $button = $(this);
        $button.addClass("processing");
        $("#sync-status-message").html("Henter kursdata fra Kursagenten API...");

        // Første AJAX-kall for å hente kursdata
        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            timeout: 300000, // 5 minutter timeout
            data: {
                action: "get_course_ids",
                nonce: sync_kurs.nonce,
            },
            success: function (response) {
                if (response.success) {
                    courseData = response.data.courses;
                    totalCourses = courseData.length;
                    $("#sync-status-message").html("Fant " + totalCourses + " kurs. Starter synkronisering...");
                    processBatch($button); // Start batch-prosessering
                } else {
                    alert("Kunne ikke hente kursdata.");
                    resetSyncButton($button);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("Kunne ikke hente kursdata. Timeout eller nettverksfeil.");
                resetSyncButton($button);
            },
        });
    });

    function processBatch($button) {
        var batch = courseData.slice(offset, offset + batchSize);

        if (batch.length === 0) {
            $button.removeClass("processing");
            $("#sync-status-message").html('<strong style="color: green;">✓ Alle kurs er synkronisert!</strong>');
            offset = 0; // Reset offset for neste gang
            return;
        }

        var currentBatch = Math.floor(offset / batchSize) + 1;
        var totalBatches = Math.ceil(totalCourses / batchSize);
        var processed = offset;
        var percentage = Math.round((processed / totalCourses) * 100);
        
        $("#sync-status-message").html(
            "Synkroniserer batch " + currentBatch + " av " + totalBatches + 
            " (" + processed + " av " + totalCourses + " kurs - " + percentage + "%)"
        );

        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            timeout: 180000, // 3 minutter timeout per batch
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
            error: function (xhr, status, error) {
                console.error("Batch sync error:", status, error);
                alert("Kunne ikke synkronisere en batch. Feil: " + status);
                resetSyncButton($button);
            },
        });
    }

    function resetSyncButton($button) {
        $button.removeClass("processing");
        $("#sync-status-message").html('<strong style="color: red;">✗ En feil oppstod under synkronisering.</strong>');
        offset = 0; // Reset offset slik at bruker kan prøve igjen fra starten
    }

    // Oppdatert kode for opprydding
    $('#cleanup-courses').on('click', function(e) {
        console.log("Opprydding kurs");
        e.preventDefault();
        const button = $(this);
        const statusDiv = $('#cleanup-status-message');
        
        // Deaktiver knappen og vis status
        button.prop('disabled', true).addClass('processing');
        statusDiv.html('<div class="notice notice-info"><p>Starter opprydding av kurs...</p></div>');
        
        $.ajax({
            url: sync_kurs.ajax_url,
            type: 'POST',
            data: {
                action: 'cleanup_courses',
                nonce: sync_kurs.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    statusDiv.html('<div class="notice notice-error"><p>Feil: ' + response.data.message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                statusDiv.html('<div class="notice notice-error"><p>En feil oppstod under opprydding. Vennligst prøv igjen.</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).removeClass('processing');
            }
        });
    });
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