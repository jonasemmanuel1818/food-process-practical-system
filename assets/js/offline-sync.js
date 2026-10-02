/* =========================================================
   FOOD PROCESS SYSTEM
   PRACTICAL 5 OFFLINE SYNCHRONIZATION
========================================================= */

"use strict";


/* =========================================================
   STORAGE KEYS
========================================================= */

const SYNC_STORAGE_KEY =
    "food_process_offline_practical_5_sync";

const SIMULATION_KEY =
    "food_process_offline_practical_5_filtration";

const MEASUREMENT_KEY =
    "food_process_offline_practical_5_measurements";

const RECORD_KEY =
    "food_process_offline_practical_5_student_record";


/* =========================================================
   SYNCHRONIZATION ENDPOINT
========================================================= */

const SYNC_ENDPOINT =
    "../sync_offline_practical5.php";


/* =========================================================
   STATE
========================================================= */

let synchronizationRunning = false;


/* =========================================================
   DOM ELEMENTS
========================================================= */

const prepareSyncButton =
    document.getElementById(
        "prepareOfflineSync"
    );

const viewSyncButton =
    document.getElementById(
        "viewOfflineSync"
    );

const clearSyncButton =
    document.getElementById(
        "clearOfflineSync"
    );

const syncStatusElement =
    document.getElementById(
        "offlineSyncStatus"
    );

const syncMessageElement =
    document.getElementById(
        "offlineSyncMessage"
    );


/* =========================================================
   HELPER — UPDATE STATUS
========================================================= */

function updateSyncStatus(
    status,
    message
) {

    if (syncStatusElement) {

        syncStatusElement.textContent =
            status;

        syncStatusElement.className =
            "offline-sync-status";

        if (status === "Synced") {

            syncStatusElement.classList.add(
                "success"
            );

        }
        else if (status === "Syncing") {

            syncStatusElement.classList.add(
                "syncing"
            );

        }
        else if (status === "Failed") {

            syncStatusElement.classList.add(
                "failed"
            );

        }
        else if (status === "Pending") {

            syncStatusElement.classList.add(
                "pending"
            );

        }
    }


    if (syncMessageElement) {

        syncMessageElement.textContent =
            message;
    }
}


/* =========================================================
   COLLECT ACTIVITY PROGRESS
========================================================= */

function collectActivityProgress() {

    const activityProgress = {};


    const activityCheckboxes =
        document.querySelectorAll(
            ".offline-activity-checkbox"
        );


    activityCheckboxes.forEach(
        function (checkbox) {

            const activityNumber =
                checkbox.dataset.activity;


            if (!activityNumber) {
                return;
            }


            activityProgress[
                activityNumber
            ] =
                checkbox.checked
                    ? 1
                    : 0;
        }
    );


    /*
     * If the page is not currently displaying
     * the checkboxes, recover the values directly
     * from localStorage.
     */

    if (
        Object.keys(activityProgress).length === 0
    ) {

        for (
            let activity = 1;
            activity <= 8;
            activity++
        ) {

            const key =
                "food_process_offline_practical_5_activity_" +
                activity;


            const saved =
                localStorage.getItem(
                    key
                );


            activityProgress[
                activity
            ] =
                saved === "1"
                    ? 1
                    : 0;
        }
    }


    return activityProgress;
}


/* =========================================================
   COLLECT STUDENT RECORD
========================================================= */

function collectStudentRecord() {

    const savedRecord =
        localStorage.getItem(
            RECORD_KEY
        );


    if (!savedRecord) {

        return {
            results: "",
            observations: "",
            conclusion: ""
        };
    }


    try {

        const record =
            JSON.parse(
                savedRecord
            );


        return {

            results:
                typeof record.results === "string"
                    ? record.results
                    : "",

            observations:
                typeof record.observations === "string"
                    ? record.observations
                    : "",

            conclusion:
                typeof record.conclusion === "string"
                    ? record.conclusion
                    : ""
        };

    }
    catch (error) {

        console.error(
            "Could not read offline student record:",
            error
        );


        return {
            results: "",
            observations: "",
            conclusion: ""
        };
    }
}


/* =========================================================
   COLLECT SIMULATION
========================================================= */

function collectSimulation() {

    const savedSimulation =
        localStorage.getItem(
            SIMULATION_KEY
        );


    if (!savedSimulation) {

        return {};
    }


    try {

        return JSON.parse(
            savedSimulation
        );

    }
    catch (error) {

        console.error(
            "Could not read offline simulation:",
            error
        );


        return {};
    }
}


/* =========================================================
   COLLECT MEASUREMENTS
========================================================= */

function collectMeasurements() {

    const savedMeasurements =
        localStorage.getItem(
            MEASUREMENT_KEY
        );


    if (!savedMeasurements) {

        return {};
    }


    try {

        return JSON.parse(
            savedMeasurements
        );

    }
    catch (error) {

        console.error(
            "Could not read offline measurements:",
            error
        );


        return {};
    }
}


/* =========================================================
   CREATE SYNCHRONIZATION PACKAGE
========================================================= */

function createSyncPackage() {

    const syncPackage = {

        practical_number: 5,

        practical_name:
            "Filtration and Separation",

        activity_progress:
            collectActivityProgress(),

        student_record:
            collectStudentRecord(),

        simulation:
            collectSimulation(),

        measurements:
            collectMeasurements(),

        created_at:
            new Date().toISOString(),

        sync_status:
            "pending"
    };


    return syncPackage;
}


/* =========================================================
   SAVE SYNCHRONIZATION PACKAGE
========================================================= */

function saveSyncPackage(
    syncPackage
) {

    localStorage.setItem(

        SYNC_STORAGE_KEY,

        JSON.stringify(
            syncPackage
        )
    );
}


/* =========================================================
   LOAD SYNCHRONIZATION PACKAGE
========================================================= */

function loadSyncPackage() {

    const savedPackage =
        localStorage.getItem(
            SYNC_STORAGE_KEY
        );


    if (!savedPackage) {

        return null;
    }


    try {

        return JSON.parse(
            savedPackage
        );

    }
    catch (error) {

        console.error(
            "Could not read synchronization package:",
            error
        );


        return null;
    }
}


/* =========================================================
   PREPARE FOR SYNCHRONIZATION
========================================================= */

function prepareForSync() {

    const syncPackage =
        createSyncPackage();


    saveSyncPackage(
        syncPackage
    );


    updateSyncStatus(

        "Pending",

        "Practical 5 data has been prepared for synchronization."
    );


    console.log(
        "Offline synchronization package:",
        syncPackage
    );


    return syncPackage;
}


/* =========================================================
   UPDATE STORED SYNC STATUS
========================================================= */

function updateStoredSyncStatus(
    status
) {

    const syncPackage =
        loadSyncPackage();


    if (!syncPackage) {
        return;
    }


    syncPackage.sync_status =
        status;


    syncPackage.last_updated =
        new Date().toISOString();


    saveSyncPackage(
        syncPackage
    );
}


/* =========================================================
   SYNCHRONIZE WITH SERVER
========================================================= */

async function synchronizeOfflineData() {

    /*
     * Prevent two synchronization requests
     * from running at the same time.
     */

    if (synchronizationRunning) {

        return;
    }


    /*
     * There must be an internet connection.
     */

    if (!navigator.onLine) {

        updateSyncStatus(

            "Pending",

            "You are offline. The data will remain on this device until a connection is available."
        );

        return;
    }


    let syncPackage =
        loadSyncPackage();


    /*
     * If no package exists, create one from
     * the current offline data.
     */

    if (!syncPackage) {

        syncPackage =
            createSyncPackage();


        /*
         * Do not create an empty package when
         * absolutely nothing has been stored.
         */

        const hasActivities =
            Object.keys(
                syncPackage.activity_progress
            ).length > 0;


        const hasRecord =
            syncPackage.student_record.results.trim() !== ""
            ||
            syncPackage.student_record.observations.trim() !== ""
            ||
            syncPackage.student_record.conclusion.trim() !== "";


        const hasSimulation =
            Object.keys(
                syncPackage.simulation
            ).length > 0;


        const hasMeasurements =
            Object.keys(
                syncPackage.measurements
            ).length > 0;


        if (
            !hasActivities
            &&
            !hasRecord
            &&
            !hasSimulation
            &&
            !hasMeasurements
        ) {

            updateSyncStatus(

                "Pending",

                "There is no offline Practical 5 data to synchronize."
            );

            return;
        }


        saveSyncPackage(
            syncPackage
        );
    }


    /*
     * Do not synchronize an already confirmed package.
     */

    if (
        syncPackage.sync_status ===
        "synced"
    ) {

        updateSyncStatus(

            "Synced",

            "Practical 5 data has already been synchronized with the server."
        );

        return;
    }


    synchronizationRunning =
        true;


    updateStoredSyncStatus(
        "syncing"
    );


    updateSyncStatus(

        "Syncing",

        "Synchronizing Practical 5 data with the server..."
    );


    try {

        /*
         * Send the complete package as JSON.
         */

        const response =
            await fetch(
                SYNC_ENDPOINT,
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json"
                    },

                    body:
                        JSON.stringify(
                            syncPackage
                        )
                }
            );


        /*
         * Try to read the server response
         * as JSON.
         */

        let data;


        try {

            data =
                await response.json();

        }
        catch (jsonError) {

            throw new Error(
                "The synchronization server returned an invalid response."
            );
        }


        /*
         * Check HTTP and application-level
         * success.
         */

        if (
            !response.ok
            ||
            !data.success
        ) {

            throw new Error(

                data.message
                    ||
                "The server could not synchronize the offline data."
            );
        }


        /*
         * Server confirmed successful
         * synchronization.
         */

        updateStoredSyncStatus(
            "synced"
        );


        updateSyncStatus(

            "Synced",

            data.message
                ||
            "Practical 5 data synchronized successfully."
        );


        console.log(
            "Practical 5 synchronization successful:",
            data
        );


    }
    catch (error) {

        console.error(
            "Offline synchronization failed:",
            error
        );


        /*
         * IMPORTANT:
         * Keep the package locally so it can
         * be synchronized again later.
         */

        updateStoredSyncStatus(
            "pending"
        );


        updateSyncStatus(

            "Failed",

            error.message
                ||
            "Synchronization failed. Your offline data is still محفوظ on this device and will be retried."
        );


    }
    finally {

        synchronizationRunning =
            false;
    }
}


/* =========================================================
   VIEW SYNCHRONIZATION DATA
========================================================= */

function viewSyncData() {

    const syncPackage =
        loadSyncPackage();


    if (!syncPackage) {

        updateSyncStatus(

            "Pending",

            "No synchronization package is currently stored."
        );


        console.log(
            "No Practical 5 offline synchronization package found."
        );


        return;
    }


    console.log(
        "========================================"
    );

    console.log(
        "PRACTICAL 5 OFFLINE SYNCHRONIZATION DATA"
    );

    console.log(
        "========================================"
    );

    console.log(
        syncPackage
    );


    updateSyncStatus(

        syncPackage.sync_status === "synced"
            ? "Synced"
            : "Pending",

        "Synchronization data has been displayed in the browser console."
    );
}


/* =========================================================
   CLEAR SYNCHRONIZATION PACKAGE
========================================================= */

function clearSyncPackage() {

    const syncPackage =
        loadSyncPackage();


    if (!syncPackage) {

        updateSyncStatus(

            "Pending",

            "There is no synchronization package to clear."
        );


        return;
    }


    /*
     * Do not allow accidental deletion of
     * unsynchronized data without confirmation.
     */

    if (
        syncPackage.sync_status !==
        "synced"
    ) {

        const confirmed =
            window.confirm(

                "This data has not been confirmed as synchronized with the server.\n\n" +
                "Clearing it may permanently remove the offline copy.\n\n" +
                "Do you want to continue?"
            );


        if (!confirmed) {

            return;
        }
    }


    localStorage.removeItem(
        SYNC_STORAGE_KEY
    );


    updateSyncStatus(

        "Pending",

        "The offline synchronization package has been cleared."
    );


    console.log(
        "Practical 5 synchronization package cleared."
    );
}


/* =========================================================
   MANUAL SYNCHRONIZATION
========================================================= */

async function manualSync() {

    if (!navigator.onLine) {

        updateSyncStatus(

            "Pending",

            "You are currently offline. Connect to the internet and try again."
        );


        return;
    }


    /*
     * If there is no saved package, prepare
     * one first.
     */

    if (
        !loadSyncPackage()
    ) {

        prepareForSync();
    }


    await synchronizeOfflineData();
}


/* =========================================================
   PREPARE BUTTON
========================================================= */

if (prepareSyncButton) {

    prepareSyncButton.addEventListener(

        "click",

        function () {

            prepareForSync();

        }
    );
}


/* =========================================================
   VIEW BUTTON
========================================================= */

if (viewSyncButton) {

    viewSyncButton.addEventListener(

        "click",

        function () {

            viewSyncData();

        }
    );
}


/* =========================================================
   CLEAR BUTTON
========================================================= */

if (clearSyncButton) {

    clearSyncButton.addEventListener(

        "click",

        function () {

            clearSyncPackage();

        }
    );
}


/* =========================================================
   OPTIONAL SYNC NOW BUTTON
========================================================= */

const syncNowButton =
    document.getElementById(
        "syncOfflineNow"
    );


if (syncNowButton) {

    syncNowButton.addEventListener(

        "click",

        function () {

            manualSync();

        }
    );
}


/* =========================================================
   INTERNET CONNECTION RETURNED
========================================================= */

window.addEventListener(

    "online",

    function () {

        console.log(
            "Internet connection detected."
        );


        updateSyncStatus(

            "Pending",

            "Internet connection restored. Preparing to synchronize your Practical 5 data..."
        );


        /*
         * Give the browser a short moment to
         * stabilize the connection.
         */

        setTimeout(

            function () {

                synchronizeOfflineData();

            },

            1500
        );
    }
);


/* =========================================================
   OFFLINE EVENT
========================================================= */

window.addEventListener(

    "offline",

    function () {

        console.log(
            "Device is offline."
        );


        const syncPackage =
            loadSyncPackage();


        if (syncPackage) {

            updateSyncStatus(

                "Pending",

                "You are offline. Your Practical 5 data remains safely stored on this device."
            );

        }
        else {

            updateSyncStatus(

                "Pending",

                "You are offline. Practical 5 data will be stored on this device."
            );
        }
    }
);


/* =========================================================
   INITIAL STATUS
========================================================= */

function initializeSynchronization() {

    const syncPackage =
        loadSyncPackage();


    if (syncPackage) {

        if (
            syncPackage.sync_status ===
            "synced"
        ) {

            updateSyncStatus(

                "Synced",

                "Practical 5 data has already been synchronized with the server."
            );

        }
        else if (
            syncPackage.sync_status ===
            "syncing"
        ) {

            /*
             * A previous browser session may have
             * ended during synchronization.
             *
             * Reset it to pending so it can safely
             * be attempted again.
             */

            updateStoredSyncStatus(
                "pending"
            );


            updateSyncStatus(

                "Pending",

                "Practical 5 data is waiting to be synchronized."
            );

        }
        else {

            updateSyncStatus(

                "Pending",

                "Practical 5 data is waiting to be synchronized."
            );
        }

    }
    else {

        updateSyncStatus(

            "Pending",

            "No synchronization package has been prepared yet."
        );
    }


    /*
     * If the browser is already online when
     * the page opens, attempt synchronization
     * of an existing package.
     */

    if (
        navigator.onLine
        &&
        syncPackage
        &&
        syncPackage.sync_status !==
        "synced"
    ) {

        setTimeout(

            function () {

                synchronizeOfflineData();

            },

            1000
        );
    }
}


/* =========================================================
   START
========================================================= */

if (
    document.readyState ===
    "loading"
) {

    document.addEventListener(

        "DOMContentLoaded",

        initializeSynchronization

    );

}
else {

    initializeSynchronization();

}