// assets/js/offline-sync4.js

(function () {
    "use strict";

    const SYNC_STORAGE_KEY =
        "food_process_offline_practical_4_sync";

    const PROGRESS_STORAGE_KEY =
        "food_process_offline_practical_4_progress";

    const RECORD_STORAGE_KEY =
        "food_process_offline_practical_4_student_record";

    const SPRAY_STORAGE_KEY =
        "food_process_offline_practical_4_spray_simulation";

    const FREEZE_STORAGE_KEY =
        "food_process_offline_practical_4_freeze_simulation";

    const syncButton =
        document.getElementById("syncOfflineP4");

    const syncStatus =
        document.getElementById("offlineSyncStatus");

    /*
     * Return a safe object from localStorage.
     */
    function getLocalObject(key, fallback = null) {
        try {
            const value =
                localStorage.getItem(key);

            if (!value) {
                return fallback;
            }

            return JSON.parse(value);

        } catch (error) {
            console.error(
                "Unable to read local storage:",
                key,
                error
            );

            return fallback;
        }
    }

    /*
     * Show synchronization status.
     */
    function showStatus(message, success = true) {

        if (!syncStatus) {
            return;
        }

        syncStatus.textContent = message;

        if (success) {
            syncStatus.style.color = "#1f5f75";
        } else {
            syncStatus.style.color = "#8a3030";
        }
    }

    /*
     * Get Practical 4 progress.
     */
    function getProgress() {

        const saved =
            getLocalObject(
                PROGRESS_STORAGE_KEY,
                {}
            );

        const progress = {};

        for (let i = 1; i <= 12; i++) {
            progress[i] =
                saved &&
                saved[i] === true;
        }

        return progress;
    }

    /*
     * Get Practical 4 student record.
     */
    function getStudentRecord() {

        const record =
            getLocalObject(
                RECORD_STORAGE_KEY,
                null
            );

        if (!record) {
            return {
                results: "",
                observations: "",
                conclusion: ""
            };
        }

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

    /*
     * Get saved simulation results.
     */
    function getSimulations() {

        const spray =
            getLocalObject(
                SPRAY_STORAGE_KEY,
                null
            );

        const freeze =
            getLocalObject(
                FREEZE_STORAGE_KEY,
                null
            );

        const simulations = [];

        if (spray) {
            simulations.push({
                simulation_type:
                    "Spray Drying",

                input_data:
                    spray.input_data || {},

                result_data:
                    spray.result_data || {},

                saved_at:
                    spray.saved_at ||
                    new Date().toISOString()
            });
        }

        if (freeze) {
            simulations.push({
                simulation_type:
                    "Freeze Drying",

                input_data:
                    freeze.input_data || {},

                result_data:
                    freeze.result_data || {},

                saved_at:
                    freeze.saved_at ||
                    new Date().toISOString()
            });
        }

        return simulations;
    }

    /*
     * Calculate how many activities have been completed.
     */
    function getCompletedCount(progress) {

        let count = 0;

        for (let i = 1; i <= 12; i++) {
            if (progress[i] === true) {
                count++;
            }
        }

        return count;
    }

    /*
     * Prepare the complete offline package.
     */
    function buildSyncPackage() {

        const progress =
            getProgress();

        const record =
            getStudentRecord();

        const simulations =
            getSimulations();

        return {
            practical_number: 4,

            progress: progress,

            completed_activities:
                getCompletedCount(progress),

            total_activities: 12,

            record: record,

            simulations: simulations,

            synced_from:
                "offline_practical4",

            client_time:
                new Date().toISOString()
        };
    }

    /*
     * Store a local copy of the package waiting
     * for synchronization.
     */
    function savePendingSyncPackage(syncPackage) {

        const pending = {
            ...syncPackage,

            pending_sync: true,

            created_at:
                new Date().toISOString()
        };

        try {
            localStorage.setItem(
                SYNC_STORAGE_KEY,
                JSON.stringify(pending)
            );

            return true;

        } catch (error) {

            console.error(
                "Unable to save pending sync package:",
                error
            );

            return false;
        }
    }

    /*
     * Remove pending package after successful
     * synchronization.
     */
    function clearPendingSyncPackage() {

        try {
            localStorage.removeItem(
                SYNC_STORAGE_KEY
            );

            return true;

        } catch (error) {

            console.error(
                "Unable to clear pending sync package:",
                error
            );

            return false;
        }
    }

    /*
     * Send the offline package to the server.
     */
    async function sendToServer(syncPackage) {

        const response =
            await fetch(
                "../sync_offline_practical4.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    credentials: "same-origin",

                    body:
                        JSON.stringify(syncPackage)
                }
            );

        const contentType =
            response.headers.get(
                "content-type"
            ) || "";

        let data;

        if (
            contentType.includes(
                "application/json"
            )
        ) {
            data =
                await response.json();
        } else {
            const text =
                await response.text();

            data = {
                success:
                    response.ok,

                message:
                    text ||
                    "Server returned an unexpected response."
            };
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                "Synchronization failed."
            );
        }

        if (data.success === false) {
            throw new Error(
                data.message ||
                "Synchronization failed."
            );
        }

        return data;
    }

    /*
     * Main synchronization function.
     */
    async function synchronizePractical4() {

        if (!navigator.onLine) {

            const offlinePackage =
                buildSyncPackage();

            const saved =
                savePendingSyncPackage(
                    offlinePackage
                );

            if (saved) {
                showStatus(
                    "You are offline. Your Practical 4 data has been kept on this device and will be ready for synchronization when the server is available.",
                    false
                );
            } else {
                showStatus(
                    "You are offline and the synchronization package could not be saved.",
                    false
                );
            }

            return;
        }

        const syncPackage =
            buildSyncPackage();

        const completed =
            syncPackage.completed_activities;

        const simulations =
            syncPackage.simulations.length;

        showStatus(
            "Synchronizing Practical 4 data..."
        );

        if (syncButton) {
            syncButton.disabled = true;
            syncButton.textContent =
                "Synchronizing...";
        }

        try {

            const result =
                await sendToServer(
                    syncPackage
                );

            clearPendingSyncPackage();

            let message =
                "Practical 4 synchronized successfully.";

            if (
                typeof result.message === "string" &&
                result.message.trim() !== ""
            ) {
                message =
                    result.message;
            }

            message +=
                " Activities: " +
                completed +
                "/12.";

            if (simulations > 0) {
                message +=
                    " Simulations: " +
                    simulations +
                    ".";
            }

            showStatus(
                message
            );

            window.dispatchEvent(
                new CustomEvent(
                    "offlineP4SyncCompleted",
                    {
                        detail: {
                            package:
                                syncPackage,

                            response:
                                result
                        }
                    }
                )
            );

        } catch (error) {

            console.error(
                "Practical 4 synchronization error:",
                error
            );

            /*
             * Keep the package locally so the student's
             * work is not lost when the server is unavailable.
             */
            savePendingSyncPackage(
                syncPackage
            );

            showStatus(
                "Synchronization could not be completed. Your Practical 4 data remains saved on this device.",
                false
            );

            window.dispatchEvent(
                new CustomEvent(
                    "offlineP4SyncFailed",
                    {
                        detail: {
                            error:
                                error.message,

                            package:
                                syncPackage
                        }
                    }
                )
            );

        } finally {

            if (syncButton) {
                syncButton.disabled = false;
                syncButton.textContent =
                    "Synchronize Practical 4";
            }
        }
    }

    /*
     * Automatically try a pending synchronization when
     * the device comes back online.
     */
    async function tryPendingSync() {

        if (!navigator.onLine) {
            return;
        }

        const pending =
            getLocalObject(
                SYNC_STORAGE_KEY,
                null
            );

        if (!pending) {
            return;
        }

        showStatus(
            "Internet connection restored. Synchronizing saved Practical 4 data..."
        );

        if (syncButton) {
            syncButton.disabled = true;
            syncButton.textContent =
                "Synchronizing...";
        }

        try {

            const result =
                await sendToServer(
                    pending
                );

            clearPendingSyncPackage();

            showStatus(
                typeof result.message === "string"
                    ? result.message
                    : "Saved Practical 4 data synchronized successfully."
            );

            window.dispatchEvent(
                new CustomEvent(
                    "offlineP4PendingSyncCompleted",
                    {
                        detail: {
                            response:
                                result,

                            package:
                                pending
                        }
                    }
                )
            );

        } catch (error) {

            console.error(
                "Pending Practical 4 synchronization failed:",
                error
            );

            showStatus(
                "Connection is available, but synchronization could not be completed yet. Your data remains saved locally.",
                false
            );

        } finally {

            if (syncButton) {
                syncButton.disabled = false;
                syncButton.textContent =
                    "Synchronize Practical 4";
            }
        }
    }

    /*
     * Button event.
     */
    if (syncButton) {

        syncButton.addEventListener(
            "click",
            synchronizePractical4
        );
    }

    /*
     * Device/browser comes back online.
     */
    window.addEventListener(
        "online",
        function () {
            setTimeout(
                tryPendingSync,
                1000
            );
        }
    );

    /*
     * Public API.
     */
    window.offlinePractical4Sync = {

        sync: synchronizePractical4,

        tryPendingSync:
            tryPendingSync,

        buildPackage:
            buildSyncPackage,

        getPendingPackage:
            function () {
                return getLocalObject(
                    SYNC_STORAGE_KEY,
                    null
                );
            },

        clearPending:
            clearPendingSyncPackage,

        hasPendingSync:
            function () {
                return localStorage.getItem(
                    SYNC_STORAGE_KEY
                ) !== null;
            }
    };

    /*
     * If a pending package already exists and the
     * page is opened while online, try to synchronize it.
     */
    if (navigator.onLine) {
        setTimeout(
            tryPendingSync,
            1500
        );
    }

})();