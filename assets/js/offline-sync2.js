(function () {

    "use strict";

    const SYNC_STORAGE_KEY =
        "food_process_offline_practical_2_sync";

    const SYNC_ENDPOINT =
        "../sync_offline_practical2.php";

    const PRACTICAL_NUMBER = 2;

    const TOTAL_ACTIVITIES = 9;

    function getElement(id) {
        return document.getElementById(id);
    }

    function safeParse(
        key,
        fallback
    ) {

        try {

            const saved =
                localStorage.getItem(key);

            if (!saved) {
                return fallback;
            }

            const parsed =
                JSON.parse(saved);

            return parsed ?? fallback;

        } catch (error) {

            console.error(
                "Practical 2 storage parsing error:",
                error
            );

            return fallback;
        }
    }

    function getActivities() {

        const saved =
            safeParse(
                "food_process_offline_practical_2_progress",
                {}
            );

        const activities = [];

        for (
            let i = 1;
            i <= TOTAL_ACTIVITIES;
            i++
        ) {

            activities.push({

                activity_number:
                    i,

                completed:
                    saved[i] === true

            });
        }

        return activities;
    }

    function getRecord() {

        return safeParse(
            "food_process_offline_practical_2_student_record",
            {}
        );
    }

    function getCentrifugation() {

        return safeParse(
            "food_process_offline_practical_2_centrifugation",
            {}
        );
    }

    function getSieveAnalysis() {

        return safeParse(
            "food_process_offline_practical_2_sieve",
            {}
        );
    }

    function getSyncState() {

        return safeParse(
            SYNC_STORAGE_KEY,
            {}
        );
    }

    function saveSyncState(state) {

        try {

            localStorage.setItem(
                SYNC_STORAGE_KEY,
                JSON.stringify(state)
            );

            return true;

        } catch (error) {

            console.error(
                "Practical 2 sync state saving error:",
                error
            );

            return false;
        }
    }

    function updateStatus(message) {

        const status =
            getElement(
                "offlineSyncStatus"
            );

        if (status) {
            status.textContent =
                message;
        }
    }

    function countCompletedActivities(
        activities
    ) {

        return activities.filter(
            function (activity) {

                return activity.completed === true;

            }
        ).length;
    }

    function buildPayload() {

        const activities =
            getActivities();

        const record =
            getRecord();

        const centrifugation =
            getCentrifugation();

        const sieve =
            getSieveAnalysis();

        const completed =
            countCompletedActivities(
                activities
            );

        return {

            practical_number:
                PRACTICAL_NUMBER,

            activities:
                activities,

            record:
                record,

            simulation: {

                centrifugation:
                    centrifugation,

                sieve_analysis:
                    sieve
            },

            summary: {

                total_activities:
                    TOTAL_ACTIVITIES,

                completed_activities:
                    completed,

                completion_percentage:
                    Math.round(
                        (
                            completed /
                            TOTAL_ACTIVITIES
                        ) * 100
                    )
            },

            sync_time:
                new Date().toISOString()
        };
    }

    async function syncPractical2() {

        const button =
            getElement(
                "syncOfflineP2"
            );

        if (button) {

            button.disabled = true;

            button.textContent =
                "Synchronizing...";
        }

        updateStatus(
            "Preparing Practical 2 data for synchronization..."
        );

        const payload =
            buildPayload();

        try {

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

                        credentials:
                            "same-origin",

                        body:
                            JSON.stringify(
                                payload
                            )
                    }
                );

            let data;

            try {

                data =
                    await response.json();

            } catch (jsonError) {

                throw new Error(
                    "The server returned an invalid response."
                );
            }

            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "Practical 2 synchronization failed."
                );
            }

            const syncState = {

                practical_number:
                    PRACTICAL_NUMBER,

                last_sync:
                    new Date().toISOString(),

                success:
                    true,

                server_response:
                    data

            };

            saveSyncState(
                syncState
            );

            let message =
                "Practical 2 synchronized successfully.";

            if (
                typeof data.activities_saved !==
                "undefined"
            ) {

                message +=
                    " Activities saved: " +
                    data.activities_saved + ".";
            }

            if (
                typeof data.activities_completed !==
                "undefined" &&
                typeof data.total_activities !==
                "undefined"
            ) {

                message +=
                    " Completed: " +
                    data.activities_completed +
                    "/" +
                    data.total_activities +
                    ".";
            }

            updateStatus(
                message
            );

            return data;

        } catch (error) {

            console.error(
                "Practical 2 synchronization error:",
                error
            );

            const syncState = {

                practical_number:
                    PRACTICAL_NUMBER,

                last_attempt:
                    new Date().toISOString(),

                success:
                    false,

                error:
                    error.message

            };

            saveSyncState(
                syncState
            );

            updateStatus(
                "Synchronization failed: " +
                error.message
            );

            return null;

        } finally {

            if (button) {

                button.disabled = false;

                button.textContent =
                    "Sync Practical 2";
            }
        }
    }

    function getLastSync() {

        return getSyncState();
    }

    function isOnline() {

        return navigator.onLine;
    }

    function initialize() {

        const button =
            getElement(
                "syncOfflineP2"
            );

        if (button) {

            button.addEventListener(
                "click",
                syncPractical2
            );
        }

        const previousSync =
            getSyncState();

        if (
            previousSync &&
            previousSync.last_sync
        ) {

            updateStatus(
                "Last synchronization: " +
                new Date(
                    previousSync.last_sync
                ).toLocaleString()
            );
        }
    }

    window.Practical2OfflineSync = {

        sync:
            syncPractical2,

        buildPayload:
            buildPayload,

        getActivities:
            getActivities,

        getRecord:
            getRecord,

        getCentrifugation:
            getCentrifugation,

        getSieveAnalysis:
            getSieveAnalysis,

        getLastSync:
            getLastSync,

        isOnline:
            isOnline
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