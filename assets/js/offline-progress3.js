(function () {

    "use strict";

    /*
     * Practical 3 Offline Activity Progress
     *
     * Activities:
     * 1 - Introduction
     * 2 - Objectives
     * 3 - Materials and Methods
     * 4 - Precautions
     * 5 - Cold Point Procedure
     * 6 - Heating Curve Procedure
     * 7 - Calculations
     * 8 - Results and Observations
     * 9 - Conclusion
     */

    const STORAGE_KEY =
        "food_process_offline_practical_3_progress";

    const TOTAL_ACTIVITIES = 9;

    const activityCheckboxes =
        document.querySelectorAll(".activity-check");

    const progressText =
        document.getElementById("offlineActivityProgress");

    const progressBar =
        document.getElementById("offlineProgressBar");

    const completedText =
        document.getElementById("offlineCompletedText");

    const completionMessage =
        document.getElementById("offlineCompletionMessage");


    /*
     * Load saved progress
     */

    function loadProgress() {

        let savedProgress = [];

        try {

            const stored =
                localStorage.getItem(STORAGE_KEY);

            if (stored) {
                savedProgress = JSON.parse(stored);
            }

        } catch (error) {

            console.error(
                "Unable to load Practical 3 progress:",
                error
            );

            savedProgress = [];

        }


        if (!Array.isArray(savedProgress)) {
            savedProgress = [];
        }


        activityCheckboxes.forEach(function (checkbox) {

            const activityNumber =
                Number(checkbox.dataset.activity);

            checkbox.checked =
                savedProgress.includes(activityNumber);

        });


        updateProgress();

    }


    /*
     * Save progress
     */

    function saveProgress() {

        const completedActivities = [];

        activityCheckboxes.forEach(function (checkbox) {

            if (checkbox.checked) {

                completedActivities.push(
                    Number(checkbox.dataset.activity)
                );

            }

        });


        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(completedActivities)
            );

        } catch (error) {

            console.error(
                "Unable to save Practical 3 progress:",
                error
            );

        }


        updateProgress();

    }


    /*
     * Update progress display
     */

    function updateProgress() {

        let completed = 0;

        activityCheckboxes.forEach(function (checkbox) {

            if (checkbox.checked) {
                completed++;
            }

        });


        const percentage =
            Math.round(
                (completed / TOTAL_ACTIVITIES) * 100
            );


        if (progressText) {

            progressText.textContent =
                completed +
                " of " +
                TOTAL_ACTIVITIES +
                " activities completed";

        }


        if (progressBar) {

            progressBar.style.width =
                percentage + "%";

        }


        if (completedText) {

            if (completed === 0) {

                completedText.textContent =
                    "Practical 3 is not started.";

            } else if (
                completed < TOTAL_ACTIVITIES
            ) {

                completedText.textContent =
                    "Practical 3 is in progress — " +
                    percentage +
                    "% completed.";

            } else {

                completedText.textContent =
                    "Practical 3 activities are complete.";

            }

        }


        if (completionMessage) {

            if (completed === TOTAL_ACTIVITIES) {

                completionMessage.textContent =
                    "Excellent. You have completed all 9 Practical 3 activities. Your progress is saved on this device and can be synchronized when you are online.";

                completionMessage.style.background =
                    "#eef5f7";

                completionMessage.style.borderColor =
                    "#d3e2e7";

                completionMessage.style.color =
                    "#17495a";

            } else {

                const remaining =
                    TOTAL_ACTIVITIES - completed;

                completionMessage.textContent =
                    remaining +
                    " activity" +
                    (remaining === 1 ? "" : "ies") +
                    " remaining. Continue studying Practical 3.";

            }

        }

    }


    /*
     * Checkbox events
     */

    activityCheckboxes.forEach(function (checkbox) {

        checkbox.addEventListener(
            "change",
            function () {

                saveProgress();

            }
        );

    });


    /*
     * Clear progress
     */

    const clearButton =
        document.getElementById(
            "clearOfflineProgress"
        );


    if (clearButton) {

        clearButton.addEventListener(
            "click",
            function () {

                const confirmed =
                    window.confirm(
                        "Are you sure you want to clear all Practical 3 activity progress?"
                    );


                if (!confirmed) {
                    return;
                }


                activityCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked = false;

                    }
                );


                try {

                    localStorage.removeItem(
                        STORAGE_KEY
                    );

                } catch (error) {

                    console.error(
                        "Unable to clear Practical 3 progress:",
                        error
                    );

                }


                updateProgress();


                if (completionMessage) {

                    completionMessage.textContent =
                        "Practical 3 activity progress has been cleared.";

                }

            }
        );

    }


    /*
     * Expose progress information for
     * the synchronization script.
     */

    window.Practical3OfflineProgress = {

        storageKey: STORAGE_KEY,

        totalActivities: TOTAL_ACTIVITIES,

        getCompletedActivities: function () {

            const completedActivities = [];

            activityCheckboxes.forEach(
                function (checkbox) {

                    if (checkbox.checked) {

                        completedActivities.push(
                            Number(checkbox.dataset.activity)
                        );

                    }

                }
            );

            return completedActivities;

        },

        getCompletedCount: function () {

            let completed = 0;

            activityCheckboxes.forEach(
                function (checkbox) {

                    if (checkbox.checked) {
                        completed++;
                    }

                }
            );

            return completed;

        },

        getProgressPercentage: function () {

            const completed =
                this.getCompletedCount();

            return Math.round(
                (completed / TOTAL_ACTIVITIES) * 100
            );

        },

        reload: function () {

            loadProgress();

        }

    };


    /*
     * Initial load
     */

    loadProgress();

})();