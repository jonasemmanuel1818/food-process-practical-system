(function () {

    "use strict";

    /*
     * Practical 3 Offline Student Record
     *
     * Stores:
     * - Results
     * - Observations
     * - Conclusion
     *
     * Data remains available after refreshing or
     * reopening the offline Practical 3 page.
     */

    const STORAGE_KEY =
        "food_process_offline_practical_3_student_record";


    const resultsField =
        document.getElementById(
            "offlineRecordResults"
        );

    const observationsField =
        document.getElementById(
            "offlineRecordObservations"
        );

    const conclusionField =
        document.getElementById(
            "offlineRecordConclusion"
        );

    const saveButton =
        document.getElementById(
            "saveOfflineRecord"
        );

    const clearButton =
        document.getElementById(
            "clearOfflineRecord"
        );

    const statusBox =
        document.getElementById(
            "offlineRecordStatus"
        );


    /*
     * Display status message
     */

    function showStatus(message, type) {

        if (!statusBox) {
            return;
        }


        statusBox.textContent = message;


        if (type === "success") {

            statusBox.style.background =
                "#eef5f7";

            statusBox.style.borderColor =
                "#d3e2e7";

            statusBox.style.color =
                "#17495a";

        } else if (type === "error") {

            statusBox.style.background =
                "#f8eeee";

            statusBox.style.borderColor =
                "#e2caca";

            statusBox.style.color =
                "#7a3030";

        } else {

            statusBox.style.background =
                "#f8f5ea";

            statusBox.style.borderColor =
                "#e4dcc2";

            statusBox.style.color =
                "#6b5a20";

        }

    }


    /*
     * Read the current record
     */

    function getRecord() {

        return {

            results:
                resultsField
                    ? resultsField.value.trim()
                    : "",

            observations:
                observationsField
                    ? observationsField.value.trim()
                    : "",

            conclusion:
                conclusionField
                    ? conclusionField.value.trim()
                    : "",

            saved_at:
                new Date().toISOString()

        };

    }


    /*
     * Save record to localStorage
     */

    function saveRecord(showMessage = true) {

        const record = getRecord();


        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(record)
            );


            if (showMessage) {

                showStatus(
                    "Practical 3 student record has been saved on this device.",
                    "success"
                );

            }


            return true;

        } catch (error) {

            console.error(
                "Unable to save Practical 3 student record:",
                error
            );


            showStatus(
                "The student record could not be saved on this device.",
                "error"
            );


            return false;

        }

    }


    /*
     * Load saved record
     */

    function loadRecord() {

        let savedRecord = null;


        try {

            const stored =
                localStorage.getItem(STORAGE_KEY);


            if (stored) {

                savedRecord =
                    JSON.parse(stored);

            }

        } catch (error) {

            console.error(
                "Unable to load Practical 3 student record:",
                error
            );

        }


        if (
            !savedRecord ||
            typeof savedRecord !== "object"
        ) {

            return;

        }


        if (resultsField) {

            resultsField.value =
                savedRecord.results || "";

        }


        if (observationsField) {

            observationsField.value =
                savedRecord.observations || "";

        }


        if (conclusionField) {

            conclusionField.value =
                savedRecord.conclusion || "";

        }


        showStatus(
            "Previously saved Practical 3 record loaded from this device.",
            "success"
        );

    }


    /*
     * Clear saved record
     */

    function clearRecord() {

        const confirmed =
            window.confirm(
                "Are you sure you want to clear the Practical 3 student record?"
            );


        if (!confirmed) {
            return;
        }


        if (resultsField) {
            resultsField.value = "";
        }

        if (observationsField) {
            observationsField.value = "";
        }

        if (conclusionField) {
            conclusionField.value = "";
        }


        try {

            localStorage.removeItem(
                STORAGE_KEY
            );

        } catch (error) {

            console.error(
                "Unable to clear Practical 3 student record:",
                error
            );

        }


        showStatus(
            "Practical 3 student record has been cleared.",
            "success"
        );

    }


    /*
     * Save button
     */

    if (saveButton) {

        saveButton.addEventListener(
            "click",
            function () {

                saveRecord(true);

            }
        );

    }


    /*
     * Clear button
     */

    if (clearButton) {

        clearButton.addEventListener(
            "click",
            function () {

                clearRecord();

            }
        );

    }


    /*
     * Make the functions available to
     * offline-sync3.js
     */

    window.Practical3OfflineRecord = {

        storageKey: STORAGE_KEY,

        getRecord: function () {

            return getRecord();

        },

        save: function () {

            return saveRecord(false);

        },

        load: function () {

            loadRecord();

        },

        clear: function () {

            clearRecord();

        }

    };


    /*
     * Load record when the page opens.
     */

    loadRecord();

})();