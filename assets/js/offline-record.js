(function () {

    "use strict";

    /*
     * OFFLINE STUDENT PRACTICAL RECORD
     * --------------------------------
     * Saves the student's Results,
     * Observations and Conclusion locally
     * using browser localStorage.
     */

    const PRACTICAL_KEY = getPracticalKey();

    const STORAGE_KEY =
        "food_process_offline_" +
        PRACTICAL_KEY +
        "_student_record";


    /*
     * Detect practical number from the page URL.
     *
     * Example:
     * practical5.html
     *
     * becomes:
     * practical_5
     */
    function getPracticalKey() {

        const path =
            window.location.pathname.toLowerCase();

        const match =
            path.match(/practical([1-5])\.html/);

        if (match) {
            return "practical_" + match[1];
        }

        return "practical_unknown";
    }


    /*
     * Find the record fields.
     */
    function getFields() {

        return {
            results:
                document.getElementById(
                    "offlineRecordResults"
                ),

            observations:
                document.getElementById(
                    "offlineRecordObservations"
                ),

            conclusion:
                document.getElementById(
                    "offlineRecordConclusion"
                )
        };

    }


    /*
     * Save record to localStorage.
     */
    function saveRecord() {

        const fields = getFields();

        const record = {

            results:
                fields.results
                    ? fields.results.value
                    : "",

            observations:
                fields.observations
                    ? fields.observations.value
                    : "",

            conclusion:
                fields.conclusion
                    ? fields.conclusion.value
                    : "",

            savedAt:
                new Date().toISOString()

        };


        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(record)
        );


        showSaveStatus(
            "Record saved on this device."
        );

    }


    /*
     * Load previously saved record.
     */
    function loadRecord() {

        const saved =
            localStorage.getItem(
                STORAGE_KEY
            );

        if (!saved) {
            return;
        }


        try {

            const record =
                JSON.parse(saved);

            const fields =
                getFields();


            if (
                fields.results &&
                typeof record.results === "string"
            ) {

                fields.results.value =
                    record.results;

            }


            if (
                fields.observations &&
                typeof record.observations === "string"
            ) {

                fields.observations.value =
                    record.observations;

            }


            if (
                fields.conclusion &&
                typeof record.conclusion === "string"
            ) {

                fields.conclusion.value =
                    record.conclusion;

            }


            showSaveStatus(
                "Saved offline record restored."
            );


        } catch (error) {

            console.error(
                "Could not load offline practical record:",
                error
            );

        }

    }


    /*
     * Show status message.
     */
    function showSaveStatus(message) {

        const status =
            document.getElementById(
                "offlineRecordStatus"
            );

        if (!status) {
            return;
        }

        status.textContent =
            message;

        status.className =
            "offline-record-status success";

    }


    /*
     * Clear saved record.
     */
    function clearRecord() {

        const confirmed =
            window.confirm(
                "Clear the saved offline practical record?"
            );

        if (!confirmed) {
            return;
        }


        localStorage.removeItem(
            STORAGE_KEY
        );


        const fields =
            getFields();


        if (fields.results) {
            fields.results.value = "";
        }

        if (fields.observations) {
            fields.observations.value = "";
        }

        if (fields.conclusion) {
            fields.conclusion.value = "";
        }


        showSaveStatus(
            "Offline record cleared."
        );

    }


    /*
     * Initialize the offline record.
     */
    function initialize() {

        const fields =
            getFields();


        /*
         * If the page does not contain
         * the offline record fields,
         * do nothing.
         */
        if (
            !fields.results &&
            !fields.observations &&
            !fields.conclusion
        ) {

            return;

        }


        /*
         * Load saved record first.
         */
        loadRecord();


        /*
         * Save whenever the student types.
         */
        Object.keys(fields).forEach(
            function (fieldName) {

                const field =
                    fields[fieldName];

                if (!field) {
                    return;
                }


                field.addEventListener(
                    "input",
                    function () {

                        saveRecord();

                    }
                );

            }
        );


        /*
         * Manual save button.
         */
        const saveButton =
            document.getElementById(
                "saveOfflineRecord"
            );

        if (saveButton) {

            saveButton.addEventListener(
                "click",
                function () {

                    saveRecord();

                }
            );

        }


        /*
         * Clear button.
         */
        const clearButton =
            document.getElementById(
                "clearOfflineRecord"
            );

        if (clearButton) {

            clearButton.addEventListener(
                "click",
                function () {

                    clearRecord();

                }
            );

        }

    }


    /*
     * Start after the page has loaded.
     */
    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize
        );

    } else {

        initialize();

    }

})();