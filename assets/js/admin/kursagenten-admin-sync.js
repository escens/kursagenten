jQuery(document).ready(function ($) {
    var courseData = []; // Lagre kursdata globalt
    var batchSize = 20; // Antall kurs å prosessere samtidig
    var offset = 0; // Start offset
    var totalCourses = 0; // Total antall kurs
    var batchStartTimes = []; // Lagre starttider for hver batch for å estimere tid
    var syncStartTime = 0; // Starttid for hele synkroniseringen
    var syncStats = null; // Statistikk fra pre-check
    var syncInProgress = false; // Flag for å hindre multiple clicks
    var failedCourses = []; // Samle alle feilede kurs gjennom synkroniseringen
    var runCleanup = false; // Om opprydding skal kjøres

    $("#sync-all-courses").on("click", function (e) {
        e.preventDefault();

        if (syncInProgress) {
            alert("Synkronisering pågår allerede. Vennligst vent...");
            return;
        }

        var $button = $(this);
        
        // Check if we're resuming or starting fresh
        var isResume = offset > 0 && courseData.length > 0;
        
        if (!isResume) {
            // Read checkbox value for cleanup
            runCleanup = $("#run-cleanup-checkbox").is(":checked");
            
            syncInProgress = true;
            $button.addClass("processing");
            $("#sync-status-message").html('<span class="spinner is-active" style="float:none;"></span> Analyserer kurs fra Kursagenten API...');

            // First AJAX call to get course data with sync status
            $.ajax({
                url: sync_kurs.ajax_url,
                type: "POST",
                timeout: 300000, // 5 minutes timeout
                data: {
                    action: "get_course_ids",
                    nonce: sync_kurs.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        courseData = response.data.courses;
                        syncStats = response.data.stats;
                        totalCourses = syncStats.total;
                        syncStartTime = Date.now(); // Start time tracking
                        
                        // Show statistics
                        var statsHtml = "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #2271b1; border-radius: 3px;'>";
                        statsHtml += "<strong style='font-size: 14px;'>📊 " + syncStats.total + " kurs funnet.</strong><br>";
                        statsHtml += "<span style='color: #666; font-size: 13px;'>Oppretter/oppdaterer alle kurs.</span>";
                        statsHtml += "</div>";
                        statsHtml += '<span class="spinner is-active" style="float:none;"></span> Starter synkronisering...';
                        
                        $("#sync-status-message").html(statsHtml);
                        
                        // Update button text
                        $button.find(".dashicons").removeClass("dashicons-update").addClass("dashicons-cloud-download");
                        
                        // Start processing after showing stats (15 seconds to read)
                        setTimeout(function() {
                            processBatch($button);
                        }, 15000);
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
        } else {
            // Resume sync
            syncInProgress = true;
            $button.addClass("processing");
            $("#sync-status-message").html('<span class="spinner is-active" style="float:none;"></span> Fortsetter synkronisering fra batch ' + (Math.floor(offset / batchSize) + 1) + '...');
            processBatch($button);
        }
    });

    function processBatch($button) {
        var batch = courseData.slice(offset, offset + batchSize);

        if (batch.length === 0) {
            // All batches completed - check if we should run cleanup
            if (runCleanup) {
                $("#sync-status-message").html('<span class="spinner is-active" style="float:none;"></span> <strong style="color: blue;">Kjører opprydding...</strong>');
                runFinalCleanup($button);
            } else {
                // Skip cleanup - show success message immediately
                $button.removeClass("processing");
                syncInProgress = false;
                
                var totalTime = Math.round((Date.now() - syncStartTime) / 60000);
                var successHtml = "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 3px;'>";
                successHtml += "<strong style='font-size: 14px; color: #155724;'>✓ Synkronisering fullført!</strong><br><br>";
                successHtml += "<div style='margin-left: 10px; color: #155724;'>";
                successHtml += "• Totalt antall kurs: <strong>" + totalCourses + "</strong><br>";
                successHtml += "• Totaltid: <strong>" + totalTime + " minutter</strong><br>";
                
                if (failedCourses.length > 0) {
                    successHtml += "• Vellykket synkronisert: <strong>" + (totalCourses - failedCourses.length) + "</strong><br>";
                    successHtml += "• <strong style='color: #d63638;'>Feilede: " + failedCourses.length + "</strong>";
                } else {
                    successHtml += "• Status: <strong>Alle kurs er synkronisert ✓</strong>";
                }
                successHtml += "</div></div>";
                
                // Show failed courses if any
                if (failedCourses.length > 0) {
                    successHtml += "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #d63638; border-radius: 3px;'>";
                    successHtml += "<strong style='font-size: 14px; color: #721c24;'>⚠️ Feilede kurs (" + failedCourses.length + "):</strong><br><br>";
                    successHtml += "<div style='max-height: 300px; overflow-y: auto; margin-left: 10px;'>";
                    
                    // Group by error type
                    var errorGroups = {};
                    failedCourses.forEach(function(course) {
                        var errorType = course.error_type || 'unknown';
                        if (!errorGroups[errorType]) {
                            errorGroups[errorType] = [];
                        }
                        errorGroups[errorType].push(course);
                    });
                    
                    // Display grouped errors
                    Object.keys(errorGroups).forEach(function(errorType) {
                        var courses = errorGroups[errorType];
                        var errorLabel = errorType === 'api_fetch_failed' ? 'API-feil' : 
                                       errorType === 'sync_failed' ? 'Synkroniseringsfeil' :
                                       errorType === 'image_timeout' ? 'Bilde timeout' :
                                       errorType === 'image_too_large' ? 'For stort bilde' : 'Ukjent feil';
                        
                        successHtml += "<strong>" + errorLabel + " (" + courses.length + "):</strong><br>";
                        courses.forEach(function(course) {
                            successHtml += "• <strong>" + course.course_name + "</strong> (kursID: " + course.location_id + ")<br>";
                            successHtml += "  <em style='color: #666; font-size: 12px;'>" + course.error_message + "</em><br>";
                        });
                        successHtml += "<br>";
                    });
                    
                    successHtml += "</div></div>";
                }
                
                $("#sync-status-message").html(successHtml);
                
                // Reset variables
                offset = 0;
                batchStartTimes = [];
                syncStartTime = 0;
                courseData = [];
                syncStats = null;
                failedCourses = [];
                
                // Reset button text
                $button.text("Hent alle kurs fra Kursagenten");
            }
            return;
        }

        var currentBatch = Math.floor(offset / batchSize) + 1;
        var totalBatches = Math.ceil(totalCourses / batchSize);
        var processed = offset;
        var percentage = Math.round((processed / totalCourses) * 100);
        
        // Beregn estimert gjenværende tid
        var timeEstimate = "";
        if (currentBatch > 1 && batchStartTimes.length > 0) {
            var elapsedTime = Date.now() - syncStartTime;
            var avgTimePerBatch = elapsedTime / (currentBatch - 1);
            var remainingBatches = totalBatches - currentBatch + 1;
            var estimatedRemainingMs = avgTimePerBatch * remainingBatches;
            
            var minutes = Math.ceil(estimatedRemainingMs / 60000);
            if (minutes < 1) {
                timeEstimate = " - Estimert gjenværende tid: < 1 min";
            } else if (minutes === 1) {
                timeEstimate = " - Estimert gjenværende tid: 1 min";
            } else {
                timeEstimate = " - Estimert gjenværende tid: " + minutes + " min";
            }
        }
        
        $("#sync-status-message").html(
            '<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>' +
            "Synkroniserer batch " + currentBatch + " av " + totalBatches + 
            " (" + processed + " av " + totalCourses + " kurs - " + percentage + "%)" + timeEstimate
        );

        var batchStartTime = Date.now();
        batchStartTimes.push(batchStartTime);

        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            timeout: 600000, // 10 minutter timeout per batch (pga bildeopplasting)
            data: {
                action: "run_sync_kurs",
                nonce: sync_kurs.nonce,
                courses: batch,
            },
            success: function (response) {
                if (response.success) {
                    // Track any failed courses in this batch
                    if (response.data.failed_courses && response.data.failed_courses.length > 0) {
                        console.warn("Batch completed with " + response.data.failed_courses.length + " failures:", response.data.failed_courses);
                        failedCourses = failedCourses.concat(response.data.failed_courses);
                    }
                    
                    offset += batchSize; // Gå til neste batch
                    processBatch($button); // Rekursiv kall for neste batch
                } else {
                    alert("Kunne ikke synkronisere en batch.");
                    resetSyncButton($button);
                }
            },
            error: function (xhr, status, error) {
                console.error("Batch sync error:", status, error, xhr);
                var errorMsg = "Kunne ikke synkronisere batch. Feil: " + status;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg += " - " + xhr.responseJSON.data.message;
                }
                alert(errorMsg);
                resetSyncButton($button);
            },
        });
    }

    function runFinalCleanup($button) {
        $.ajax({
            url: sync_kurs.ajax_url,
            type: "POST",
            timeout: 300000, // 5 minutter for cleanup (nok tid for store datasett)
            data: {
                action: "cleanup_courses",
                nonce: sync_kurs.nonce,
            },
            success: function (response) {
                $button.removeClass("processing");
                syncInProgress = false;
                
                if (response.success) {
                    var totalTime = Math.round((Date.now() - syncStartTime) / 60000);
                    var successHtml = "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 3px;'>";
                    successHtml += "<strong style='font-size: 14px; color: #155724;'>✓ Synkronisering fullført!</strong><br><br>";
                    successHtml += "<div style='margin-left: 10px; color: #155724;'>";
                    successHtml += "• Totalt antall kurs: <strong>" + totalCourses + "</strong><br>";
                    successHtml += "• Totaltid: <strong>" + totalTime + " minutter</strong><br>";
                    
                    if (failedCourses.length > 0) {
                        successHtml += "• Vellykket synkronisert: <strong>" + (totalCourses - failedCourses.length) + "</strong><br>";
                        successHtml += "• <strong style='color: #d63638;'>Feilede: " + failedCourses.length + "</strong>";
                    } else {
                        successHtml += "• Status: <strong>Alle kurs er synkronisert og oppryddet ✓</strong>";
                    }
                    successHtml += "</div></div>";
                    
                    // Show failed courses if any
                    if (failedCourses.length > 0) {
                        successHtml += "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #d63638; border-radius: 3px;'>";
                        successHtml += "<strong style='font-size: 14px; color: #721c24;'>⚠️ Feilede kurs (" + failedCourses.length + "):</strong><br><br>";
                        successHtml += "<div style='max-height: 300px; overflow-y: auto; margin-left: 10px;'>";
                        
                        // Group by error type
                        var errorGroups = {};
                        failedCourses.forEach(function(course) {
                            var errorType = course.error_type || 'unknown';
                            if (!errorGroups[errorType]) {
                                errorGroups[errorType] = [];
                            }
                            errorGroups[errorType].push(course);
                        });
                        
                        // Display grouped errors
                        Object.keys(errorGroups).forEach(function(errorType) {
                            var courses = errorGroups[errorType];
                            var errorLabel = errorType === 'api_fetch_failed' ? 'API-feil' : 
                                           errorType === 'sync_failed' ? 'Synkroniseringsfeil' :
                                           errorType === 'image_timeout' ? 'Bilde timeout' :
                                           errorType === 'image_too_large' ? 'For stort bilde' : 'Ukjent feil';
                            
                            successHtml += "<strong>" + errorLabel + " (" + courses.length + "):</strong><br>";
                            courses.forEach(function(course) {
                                successHtml += "• <strong>" + course.course_name + "</strong> (kursID: " + course.location_id + ")<br>";
                                successHtml += "  <em style='color: #666; font-size: 12px;'>" + course.error_message + "</em><br>";
                            });
                            successHtml += "<br>";
                        });
                        
                        successHtml += "</div></div>";
                    }
                    
                    $("#sync-status-message").html(successHtml);
                } else {
                    $("#sync-status-message").html('<strong style="color: orange;">✓ Kurs synkronisert, men opprydding feilet.</strong>');
                }
                // Reset alle variabler
                offset = 0;
                batchStartTimes = [];
                syncStartTime = 0;
                courseData = [];
                syncStats = null;
                failedCourses = [];
                
                // Reset button text
                $button.text("Hent alle kurs fra Kursagenten");
            },
            error: function (xhr, status, error) {
                console.error("Cleanup error:", status, error);
                $button.removeClass("processing");
                syncInProgress = false;
                $("#sync-status-message").html('<strong style="color: orange;">✓ Kurs synkronisert, men opprydding feilet.</strong>');
                // Reset alle variabler
                offset = 0;
                batchStartTimes = [];
                syncStartTime = 0;
                courseData = [];
                syncStats = null;
                
                // Reset button text
                $button.text("Hent alle kurs fra Kursagenten");
            },
        });
    }

    function resetSyncButton($button) {
        $button.removeClass("processing");
        syncInProgress = false;
        
        // Check if we can resume
        if (offset > 0 && courseData.length > 0) {
            var resumeBatch = Math.floor(offset / batchSize) + 1;
            var totalBatches = Math.ceil(totalCourses / batchSize);
            var processed = offset;
            var percentage = Math.round((processed / totalCourses) * 100);
            
            $("#sync-status-message").html(
                '<strong style="color: #d63638;">✗ Synkronisering stoppet ved batch ' + resumeBatch + ' av ' + totalBatches + 
                ' (' + processed + ' av ' + totalCourses + ' kurs synkronisert - ' + percentage + '%)</strong><br><br>' +
                '<div style="background: #fff3cd; padding: 10px; margin-top: 10px; border-left: 4px solid #ffc107; border-radius: 3px;">' +
                '<strong>💡 Tips:</strong> Klikk på knappen igjen for å fortsette synkroniseringen fra der den stoppet.' +
                '</div>'
            );
            
            // Update button text to indicate resume
            $button.text("Fortsett synkronisering");
        } else {
            $("#sync-status-message").html('<strong style="color: red;">✗ En feil oppstod under synkronisering.</strong>');
            
            // Reset all variables
            offset = 0;
            batchStartTimes = [];
            syncStartTime = 0;
            courseData = [];
            syncStats = null;
        }
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