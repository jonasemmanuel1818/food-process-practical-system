(function () {

    "use strict";


    /*
     * =====================================================
     * FOOD PROCESS SYSTEM
     * OFFLINE ACTIVITY PROGRESS
     * =====================================================
     *
     * This script stores activity completion in the
     * browser's localStorage.
     *
     * It works without an internet connection.
     */


    const PRACTICAL_KEY =
        getPracticalKey();


    const activities =
        document.querySelectorAll(".offline-activity");


    if (!activities.length) {

        return;

    }


    /*
     * -----------------------------------------------------
     * GET PRACTICAL NUMBER
     * -----------------------------------------------------
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
     * -----------------------------------------------------
     * STORAGE KEY
     * -----------------------------------------------------
     */

    function storageKey(activityNumber) {

        return (
            "food_process_offline_" +
            PRACTICAL_KEY +
            "_activity_" +
            activityNumber
        );

    }


    /*
     * -----------------------------------------------------
     * LOAD SAVED PROGRESS
     * -----------------------------------------------------
     */

    function loadProgress() {

        activities.forEach(function (activity) {

            const checkbox =
                activity.querySelector(
                    ".offline-activity-checkbox"
                );


            if (!checkbox) {

                return;

            }


            const number =
                checkbox.dataset.activity;


            const saved =
                localStorage.getItem(
                    storageKey(number)
                );


            if (saved === "1") {

                checkbox.checked = true;

                activity.classList.add(
                    "offline-activity-completed"
                );

            }

        });


        updateProgress();

    }


    /*
     * -----------------------------------------------------
     * SAVE ACTIVITY
     * -----------------------------------------------------
     */

    function saveActivity(
        checkbox
    ) {

        const number =
            checkbox.dataset.activity;


        if (checkbox.checked) {

            localStorage.setItem(
                storageKey(number),
                "1"
            );

        } else {

            localStorage.removeItem(
                storageKey(number)
            );

        }


        const activity =
            checkbox.closest(
                ".offline-activity"
            );


        if (activity) {

            activity.classList.toggle(
                "offline-activity-completed",
                checkbox.checked
            );

        }


        updateProgress();

    }


    /*
     * -----------------------------------------------------
     * PROGRESS DISPLAY
     * -----------------------------------------------------
     */

    function updateProgress() {

        const total =
            activities.length;


        let completed = 0;


        activities.forEach(function (activity) {

            const checkbox =
                activity.querySelector(
                    ".offline-activity-checkbox"
                );


            if (
                checkbox &&
                checkbox.checked
            ) {

                completed++;

            }

        });


        const percentage =
            total > 0
                ? Math.round(
                    (completed / total) * 100
                )
                : 0;


        const progressBar =
            document.getElementById(
                "offlineActivityProgress"
            );


        const progressText =
            document.getElementById(
                "offlineProgressText"
            );


        const completedText =
            document.getElementById(
                "offlineCompletedText"
            );


        if (progressBar) {

            progressBar.style.width =
                percentage + "%";

            progressBar.setAttribute(
                "aria-valuenow",
                percentage
            );

        }


        if (progressText) {

            progressText.textContent =
                percentage + "%";

        }


        if (completedText) {

            completedText.textContent =
                completed +
                " of " +
                total +
                " activities completed.";

        }


        updateCompletionMessage(
            completed,
            total
        );

    }


    /*
     * -----------------------------------------------------
     * COMPLETION MESSAGE
     * -----------------------------------------------------
     */

    function updateCompletionMessage(
        completed,
        total
    ) {

        const message =
            document.getElementById(
                "offlineCompletionMessage"
            );


        if (!message) {

            return;

        }


        if (
            total > 0 &&
            completed === total
        ) {

            message.innerHTML =
                "<strong>All activities completed.</strong> " +
                "Your progress has been saved on this device.";

            message.className =
                "offline-completion success";

        } else {

            message.innerHTML =
                "<strong>Offline progress:</strong> " +
                "Your completed activities are saved on this device.";

            message.className =
                "offline-completion";

        }

    }


    /*
     * -----------------------------------------------------
     * CHECKBOX EVENTS
     * -----------------------------------------------------
     */

    activities.forEach(function (activity) {

        const checkbox =
            activity.querySelector(
                ".offline-activity-checkbox"
            );


        if (!checkbox) {

            return;

        }


        checkbox.addEventListener(
            "change",
            function () {

                saveActivity(
                    checkbox
                );

            }
        );

    });


    /*
     * -----------------------------------------------------
     * CLEAR CURRENT PRACTICAL PROGRESS
     * -----------------------------------------------------
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
                        "Clear all offline activity progress for this practical?"
                    );


                if (!confirmed) {

                    return;

                }


                activities.forEach(
                    function (activity) {

                        const checkbox =
                            activity.querySelector(
                                ".offline-activity-checkbox"
                            );


                        if (!checkbox) {

                            return;

                        }


                        const number =
                            checkbox.dataset.activity;


                        localStorage.removeItem(
                            storageKey(number)
                        );


                        checkbox.checked =
                            false;


                        activity.classList.remove(
                            "offline-activity-completed"
                        );

                    }
                );


                updateProgress();

            }
        );

    }


    /*
     * -----------------------------------------------------
     * INITIALIZE
     * -----------------------------------------------------
     */

    loadProgress();

})();