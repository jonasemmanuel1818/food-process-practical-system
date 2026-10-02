(function () {

    "use strict";

    /*
     * PRACTICAL 3 OFFLINE SYNCHRONIZATION
     *
     * Synchronizes:
     * 1. Activity progress
     * 2. Student practical record
     * 3. Thermal processing simulation
     *
     * Endpoint:
     * ../sync_offline_practical3.php
     */

    const PRACTICAL_NUMBER = 3;

    const SYNC_ENDPOINT =
        "../sync_offline_practical3.php";

    const PROGRESS_STORAGE_KEY =
        "food_process_offline_practical_3_progress";

    const RECORD_STORAGE_KEY =
        "food_process_offline_practical_3_student_record";

    const SIMULATION_STORAGE_KEY =
        "food_process_offline_practical_3_thermal_simulation";

    const SYNC_STORAGE_KEY =
        "food_process_offline_practical_3_sync";


    const syncButton =
        document.getElementById(
            "syncOfflineP3"
        );

    const syncStatus =
        document.getElementById(
            "offlineSyncStatus"
        );


    /*
     * Display synchronization status
     */

    function showStatus(message, type) {

        if (!syncStatus) {
            return;
        }


        syncStatus.textContent =
            message;


        if (type === "success") {

            syncStatus.style.background =
                "#eef5f7";

            syncStatus.style.borderColor =
                "#d3e2e7";

            syncStatus.style.color =
                "#17495a";

        } else if (type === "error") {

            syncStatus.style.background =
                "#f8eeee";

            syncStatus.style.borderColor =
                "#e2caca";

            syncStatus.style.color =
                "#7a3030";

        } else {

            syncStatus.style.background =
                "#f8f5ea";

            syncStatus.style.borderColor =
                "#e4dcc2";

            syncStatus.style.color =
                "#6b5a20";

        }

    }


    /*
     * Read JSON safely from localStorage
     */

    function readStorage(key, fallback) {

        try {

            const stored =
                localStorage.getItem(key);


            if (!stored) {
                return fallback;
            }


            const parsed =
                JSON.parse(stored);


            return parsed;

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
     * Get activity progress
     */

    function getActivityProgress() {

        const saved =
            readStorage(
                PROGRESS_STORAGE_KEY,
                []
            );


        if (!Array.isArray(saved)) {
            return [];
        }


        return saved
            .map(function (number) {

                return Number(number);

            })
            .filter(function (number) {

                return (
                    Number.isInteger(number) &&
                    number >= 1 &&
                    number <= 9
                );

            });

    }


    /*
     * Convert activity progress into
     * synchronization records.
     */

    function buildActivityData() {

        const completedActivities =
            getActivityProgress();


        const activities = [];


        for (
            let activity = 1;
            activity <= 9;
            activity++
        ) {

            activities.push({

                activity_number:
                    activity,

                completed:
                    completedActivities.includes(
                        activity
                    )
                        ? 1
                        : 0

            });

        }


        return activities;

    }


    /*
     * Get student practical record
     */

    function getStudentRecord() {

        const record =
            readStorage(
                RECORD_STORAGE_KEY,
                {}
            );


        if (
            !record ||
            typeof record !== "object"
        ) {

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
     * Get thermal simulation
     */

    function getSimulation() {

        const simulation =
            readStorage(
                SIMULATION_STORAGE_KEY,
                null
            );


        if (
            !simulation ||
            typeof simulation !== "object"
        ) {

            return null;

        }


        return simulation;

    }


    /*
     * Get last synchronization information
     */

    function getSyncInformation() {

        return readStorage(
            SYNC_STORAGE_KEY,
            {}
        );

    }


    /*
     * Save synchronization information
     */

    function saveSyncInformation(data) {

        try {

            localStorage.setItem(
                SYNC_STORAGE_KEY,
                JSON.stringify(data)
            );


            return true;

        } catch (error) {

            console.error(
                "Unable to save synchronization information:",
                error
            );


            return false;

        }

    }


    /*
     * Check whether the browser is online
     */

    function isBrowserOnline() {

        return navigator.onLine !== false;

    }


    /*
     * Build complete synchronization payload
     */

    function buildPayload() {

        const activities =
            buildActivityData();


        const record =
            getStudentRecord();


        const simulation =
            getSimulation();


        return {

            practical_number:
                PRACTICAL_NUMBER,

            activities:
                activities,

            record:
                record,

            simulation:
                simulation,

            sync_time:
                new Date().toISOString()

        };

    }


    /*
     * Synchronize Practical 3
     */

    async function synchronize() {

        if (!isBrowserOnline()) {

            showStatus(
                "You are currently offline. Your Practical 3 data remains saved on this device.",
                "warning"
            );

            return;

        }


        if (syncButton) {

            syncButton.disabled = true;

            syncButton.textContent =
                "Synchronizing...";

        }


        showStatus(
            "Synchronizing Practical 3 data with the server...",
            "warning"
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


            /*
             * Read response as text first.
             *
             * This prevents JSON.parse errors from
             * hiding the actual PHP response.
             */

            const responseText =
                await response.text();


            let result;


            try {

                result =
                    JSON.parse(
                        responseText
                    );

            } catch (jsonError) {

                console.error(
                    "Invalid synchronization response:",
                    responseText
                );


                throw new Error(
                    "The server returned an invalid response."
                );

            }


            if (!response.ok) {

                throw new Error(
                    result.message ||
                    result.error ||
                    "Synchronization failed."
                );

            }


            if (
                result.success !== true
            ) {

                throw new Error(
                    result.message ||
                    result.error ||
                    "Synchronization could not be completed."
                );

            }


            /*
             * Save successful synchronization
             * information locally.
             */

            saveSyncInformation({

                synchronized:
                    true,

                practical_number:
                    PRACTICAL_NUMBER,

                synchronized_at:
                    new Date().toISOString(),

                server_response:
                    result

            });


            /*
             * Display successful result.
             */

            let message =
                "Practical 3 synchronized successfully.";


            if (
                result.activities_saved !== undefined
            ) {

                message +=
                    " Activities saved: " +
                    result.activities_saved +
                    ".";

            }


            if (
                result.record_saved !== undefined &&
                result.record_saved
            ) {

                message +=
                    " Student record saved.";

            }


            if (
                result.simulation_saved !== undefined &&
                result.simulation_saved
            ) {

                message +=
                    " Simulation saved.";

            }


            showStatus(
                message,
                "success"
            );


        } catch (error) {

            console.error(
                "Practical 3 synchronization error:",
                error
            );


            showStatus(
                "Synchronization could not be completed. Your Practical 3 data remains saved on this device.",
                "error"
            );

        } finally {

            if (syncButton) {

                syncButton.disabled = false;

                syncButton.textContent =
                    "Sync Practical 3";

            }

        }

    }


    /*
     * Synchronization button
     */

    if (syncButton) {

        syncButton.addEventListener(
            "click",
            function () {

                synchronize();

            }
        );

    }


    /*
     * Automatically update the message
     * when internet connectivity changes.
     */

    window.addEventListener(
        "online",
        function () {

            showStatus(
                "Internet connection detected. You can now synchronize Practical 3.",
                "success"
            );

        }
    );


    window.addEventListener(
        "offline",
        function () {

            showStatus(
                "You are offline. Practical 3 data remains saved on this device.",
                "warning"
            );

        }
    );


    /*
     * Expose synchronization functions
     * for future use.
     */

    window.Practical3OfflineSync = {

        endpoint:
            SYNC_ENDPOINT,

        synchronize:
            synchronize,

        getPayload:
            buildPayload,

        getActivityProgress:
            getActivityProgress,

        getStudentRecord:
            getStudentRecord,

        getSimulation:
            getSimulation,

        getLastSync:
            getSyncInformation

    };


    /*
     * Initial synchronization status
     */

    const lastSync =
        getSyncInformation();


    if (
        lastSync &&
        lastSync.synchronized_at
    ) {

        showStatus(
            "Last synchronization: " +
            new Date(
                lastSync.synchronized_at
            ).toLocaleString(),
            "success"
        );

    }

})();