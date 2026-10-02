(function () {

    "use strict";

    const STORAGE_KEY =
        "food_process_offline_practical_2_student_record";

    function getElement(id) {

        return document.getElementById(id);
    }

    function getRecordFromForm() {

        const studentName =
            getElement("offlineStudentName");

        const registrationNumber =
            getElement("offlineRegistrationNumber");

        const results =
            getElement("offlineResults");

        const observations =
            getElement("offlineObservations");

        const conclusion =
            getElement("offlineConclusion");

        return {

            student_name:
                studentName
                    ? studentName.value.trim()
                    : "",

            registration_number:
                registrationNumber
                    ? registrationNumber.value.trim()
                    : "",

            results:
                results
                    ? results.value.trim()
                    : "",

            observations:
                observations
                    ? observations.value.trim()
                    : "",

            conclusion:
                conclusion
                    ? conclusion.value.trim()
                    : "",

            practical_number: 2,

            saved_at:
                new Date().toISOString()
        };
    }

    function saveRecord() {

        const record =
            getRecordFromForm();

        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(record)
            );

            updateStatus(
                "Practical record saved successfully on this device."
            );

            return true;

        } catch (error) {

            console.error(
                "Practical 2 record saving error:",
                error
            );

            updateStatus(
                "Unable to save the practical record."
            );

            return false;
        }
    }

    function loadRecord() {

        try {

            const saved =
                localStorage.getItem(
                    STORAGE_KEY
                );

            if (!saved) {
                return null;
            }

            const record =
                JSON.parse(saved);

            if (
                !record ||
                typeof record !== "object"
            ) {

                return null;
            }

            const studentName =
                getElement(
                    "offlineStudentName"
                );

            const registrationNumber =
                getElement(
                    "offlineRegistrationNumber"
                );

            const results =
                getElement(
                    "offlineResults"
                );

            const observations =
                getElement(
                    "offlineObservations"
                );

            const conclusion =
                getElement(
                    "offlineConclusion"
                );

            if (studentName) {

                studentName.value =
                    record.student_name || "";
            }

            if (registrationNumber) {

                registrationNumber.value =
                    record.registration_number || "";
            }

            if (results) {

                results.value =
                    record.results || "";
            }

            if (observations) {

                observations.value =
                    record.observations || "";
            }

            if (conclusion) {

                conclusion.value =
                    record.conclusion || "";
            }

            updateStatus(
                "Previously saved practical record loaded."
            );

            return record;

        } catch (error) {

            console.error(
                "Practical 2 record loading error:",
                error
            );

            return null;
        }
    }

    function clearRecord() {

        try {

            localStorage.removeItem(
                STORAGE_KEY
            );

            const studentName =
                getElement(
                    "offlineStudentName"
                );

            const registrationNumber =
                getElement(
                    "offlineRegistrationNumber"
                );

            const results =
                getElement(
                    "offlineResults"
                );

            const observations =
                getElement(
                    "offlineObservations"
                );

            const conclusion =
                getElement(
                    "offlineConclusion"
                );

            if (studentName) {
                studentName.value = "";
            }

            if (registrationNumber) {
                registrationNumber.value = "";
            }

            if (results) {
                results.value = "";
            }

            if (observations) {
                observations.value = "";
            }

            if (conclusion) {
                conclusion.value = "";
            }

            updateStatus(
                "Practical record cleared."
            );

            return true;

        } catch (error) {

            console.error(
                "Practical 2 record clearing error:",
                error
            );

            updateStatus(
                "Unable to clear the practical record."
            );

            return false;
        }
    }

    function getRecord() {

        try {

            const saved =
                localStorage.getItem(
                    STORAGE_KEY
                );

            if (!saved) {
                return null;
            }

            const record =
                JSON.parse(saved);

            if (
                !record ||
                typeof record !== "object"
            ) {

                return null;
            }

            return record;

        } catch (error) {

            console.error(
                "Practical 2 record retrieval error:",
                error
            );

            return null;
        }
    }

    function hasRecordData() {

        const record =
            getRecord();

        if (!record) {
            return false;
        }

        return Boolean(
            record.student_name ||
            record.registration_number ||
            record.results ||
            record.observations ||
            record.conclusion
        );
    }

    function updateStatus(message) {

        const status =
            getElement(
                "offlineRecordStatus"
            );

        if (status) {
            status.textContent =
                message;
        }
    }

    function initialize() {

        const saveButton =
            getElement(
                "saveOfflineRecord"
            );

        const clearButton =
            getElement(
                "clearOfflineRecord"
            );

        if (saveButton) {

            saveButton.addEventListener(
                "click",
                saveRecord
            );
        }

        if (clearButton) {

            clearButton.addEventListener(
                "click",
                clearRecord
            );
        }

        loadRecord();
    }

    window.Practical2OfflineRecord = {

        save:
            saveRecord,

        load:
            loadRecord,

        clear:
            clearRecord,

        get:
            getRecord,

        hasData:
            hasRecordData
    };

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize
        );

    } else {

        initialize();
    }

})();