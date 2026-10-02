// assets/js/offline-record4.js

(function () {
    "use strict";

    const STORAGE_KEY =
        "food_process_offline_practical_4_student_record";

    const resultsField =
        document.getElementById("offlineRecordResults");

    const observationsField =
        document.getElementById("offlineRecordObservations");

    const conclusionField =
        document.getElementById("offlineRecordConclusion");

    const saveButton =
        document.getElementById("saveOfflineRecord");

    const clearButton =
        document.getElementById("clearOfflineRecord");

    const statusElement =
        document.getElementById("offlineRecordStatus");

    function getDefaultRecord() {
        return {
            results: "",
            observations: "",
            conclusion: "",
            updated_at: null
        };
    }

    function loadRecord() {
        const defaultRecord = getDefaultRecord();

        try {
            const saved =
                localStorage.getItem(STORAGE_KEY);

            if (!saved) {
                return defaultRecord;
            }

            const parsed = JSON.parse(saved);

            if (typeof parsed !== "object" || parsed === null) {
                return defaultRecord;
            }

            return {
                results:
                    typeof parsed.results === "string"
                        ? parsed.results
                        : "",

                observations:
                    typeof parsed.observations === "string"
                        ? parsed.observations
                        : "",

                conclusion:
                    typeof parsed.conclusion === "string"
                        ? parsed.conclusion
                        : "",

                updated_at:
                    parsed.updated_at || null
            };
        } catch (error) {
            console.error(
                "Unable to load Practical 4 student record:",
                error
            );

            return defaultRecord;
        }
    }

    function saveRecord(record) {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(record)
            );

            return true;
        } catch (error) {
            console.error(
                "Unable to save Practical 4 student record:",
                error
            );

            return false;
        }
    }

    function showStatus(message, success = true) {
        if (!statusElement) {
            return;
        }

        statusElement.textContent = message;

        if (success) {
            statusElement.style.color = "#1f5f75";
        } else {
            statusElement.style.color = "#8a3030";
        }
    }

    function fillForm(record) {
        if (resultsField) {
            resultsField.value = record.results || "";
        }

        if (observationsField) {
            observationsField.value =
                record.observations || "";
        }

        if (conclusionField) {
            conclusionField.value =
                record.conclusion || "";
        }
    }

    function getFormData() {
        return {
            results: resultsField
                ? resultsField.value.trim()
                : "",

            observations: observationsField
                ? observationsField.value.trim()
                : "",

            conclusion: conclusionField
                ? conclusionField.value.trim()
                : "",

            updated_at: new Date().toISOString()
        };
    }

    function saveStudentRecord() {
        const record = getFormData();

        if (
            record.results === "" &&
            record.observations === "" &&
            record.conclusion === ""
        ) {
            showStatus(
                "Enter at least one record before saving.",
                false
            );

            return;
        }

        const saved = saveRecord(record);

        if (!saved) {
            showStatus(
                "Unable to save the record on this device.",
                false
            );

            return;
        }

        showStatus(
            "Practical 4 record saved offline."
        );

        window.dispatchEvent(
            new CustomEvent("offlineP4RecordSaved", {
                detail: {
                    record: record
                }
            })
        );
    }

    function clearStudentRecord() {
        const confirmed = window.confirm(
            "Clear the saved Practical 4 student record?"
        );

        if (!confirmed) {
            return;
        }

        const emptyRecord = getDefaultRecord();

        saveRecord(emptyRecord);

        fillForm(emptyRecord);

        showStatus(
            "Practical 4 student record cleared."
        );

        window.dispatchEvent(
            new CustomEvent("offlineP4RecordCleared")
        );
    }

    if (saveButton) {
        saveButton.addEventListener(
            "click",
            saveStudentRecord
        );
    }

    if (clearButton) {
        clearButton.addEventListener(
            "click",
            clearStudentRecord
        );
    }

    /*
     * Save automatically whenever the student leaves a field.
     * This prevents loss of work if the page is accidentally
     * closed before the Save button is pressed.
     */
    [
        resultsField,
        observationsField,
        conclusionField
    ].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener("blur", function () {
            const record = getFormData();

            saveRecord(record);
        });
    });

    /*
     * Load the previous record when the page opens.
     */
    const savedRecord = loadRecord();

    fillForm(savedRecord);

    if (
        savedRecord.results ||
        savedRecord.observations ||
        savedRecord.conclusion
    ) {
        showStatus(
            "Saved Practical 4 record restored from this device."
        );
    }

    /*
     * Public API for offline-sync4.js
     */
    window.offlinePractical4Record = {

        storageKey: STORAGE_KEY,

        getRecord: function () {
            return loadRecord();
        },

        saveRecord: function (record) {
            const normalizedRecord = {
                results:
                    record &&
                    typeof record.results === "string"
                        ? record.results
                        : "",

                observations:
                    record &&
                    typeof record.observations === "string"
                        ? record.observations
                        : "",

                conclusion:
                    record &&
                    typeof record.conclusion === "string"
                        ? record.conclusion
                        : "",

                updated_at:
                    new Date().toISOString()
            };

            const saved =
                saveRecord(normalizedRecord);

            if (saved) {
                fillForm(normalizedRecord);

                showStatus(
                    "Practical 4 record saved offline."
                );
            }

            return saved;
        },

        clearRecord: function () {
            clearStudentRecord();
        },

        hasRecord: function () {
            const record = loadRecord();

            return (
                record.results.trim() !== "" ||
                record.observations.trim() !== "" ||
                record.conclusion.trim() !== ""
            );
        }
    };
})();