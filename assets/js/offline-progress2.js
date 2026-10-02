(function () {

    "use strict";

    const STORAGE_KEY =
        "food_process_offline_practical_2_progress";

    const TOTAL_ACTIVITIES = 9;

    function getProgress() {

        try {

            const saved =
                localStorage.getItem(STORAGE_KEY);

            if (!saved) {
                return {};
            }

            const parsed =
                JSON.parse(saved);

            return parsed &&
                   typeof parsed === "object"
                ? parsed
                : {};

        } catch (error) {

            console.error(
                "Practical 2 progress loading error:",
                error
            );

            return {};
        }
    }

    function saveProgress(progress) {

        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(progress)
            );

            return true;

        } catch (error) {

            console.error(
                "Practical 2 progress saving error:",
                error
            );

            return false;
        }
    }

    function getCompletedCount(progress) {

        let completed = 0;

        for (
            let i = 1;
            i <= TOTAL_ACTIVITIES;
            i++
        ) {

            if (
                progress[i] === true
            ) {
                completed++;
            }
        }

        return completed;
    }

    function updateUI() {

        const progress =
            getProgress();

        let completed =
            getCompletedCount(progress);

        const percentage =
            Math.round(
                (completed /
                    TOTAL_ACTIVITIES) *
                    100
            );

        for (
            let i = 1;
            i <= TOTAL_ACTIVITIES;
            i++
        ) {

            const checkbox =
                document.getElementById(
                    "activity" + i
                );

            if (!checkbox) {
                continue;
            }

            checkbox.checked =
                progress[i] === true;

            const activity =
                checkbox.closest(
                    ".activity"
                );

            if (!activity) {
                continue;
            }

            if (checkbox.checked) {

                activity.classList.add(
                    "completed"
                );

            } else {

                activity.classList.remove(
                    "completed"
                );
            }
        }

        const progressText =
            document.getElementById(
                "offlineActivityProgress"
            );

        if (progressText) {

            progressText.textContent =
                "Progress: " +
                percentage +
                "%";
        }

        const progressBar =
            document.getElementById(
                "offlineProgressBar"
            );

        if (progressBar) {

            progressBar.style.width =
                percentage + "%";
        }

        const completedText =
            document.getElementById(
                "offlineCompletedText"
            );

        if (completedText) {

            completedText.textContent =
                completed +
                " of " +
                TOTAL_ACTIVITIES +
                " activities completed";
        }

        const completionMessage =
            document.getElementById(
                "offlineCompletionMessage"
            );

        if (completionMessage) {

            if (
                completed ===
                TOTAL_ACTIVITIES
            ) {

                completionMessage.textContent =
                    "Excellent. All Practical 2 activities have been completed.";

            } else {

                completionMessage.textContent =
                    "Complete all activities to finish Practical 2.";
            }
        }

        return {
            completed: completed,
            total: TOTAL_ACTIVITIES,
            percentage: percentage,
            completedAll:
                completed === TOTAL_ACTIVITIES
        };
    }

    function setActivity(
        activityNumber,
        completed
    ) {

        activityNumber =
            parseInt(
                activityNumber,
                10
            );

        if (
            activityNumber < 1 ||
            activityNumber > TOTAL_ACTIVITIES
        ) {

            return false;
        }

        const progress =
            getProgress();

        progress[activityNumber] =
            completed === true;

        const saved =
            saveProgress(progress);

        if (saved) {
            updateUI();
        }

        return saved;
    }

    function getActivity(
        activityNumber
    ) {

        const progress =
            getProgress();

        return progress[
            parseInt(
                activityNumber,
                10
            )
        ] === true;
    }

    function getAllProgress() {

        return getProgress();
    }

    function clearProgress() {

        try {

            localStorage.removeItem(
                STORAGE_KEY
            );

            updateUI();

            return true;

        } catch (error) {

            console.error(
                "Practical 2 progress clearing error:",
                error
            );

            return false;
        }
    }

    function initializeCheckboxes() {

        for (
            let i = 1;
            i <= TOTAL_ACTIVITIES;
            i++
        ) {

            const checkbox =
                document.getElementById(
                    "activity" + i
                );

            if (!checkbox) {
                continue;
            }

            checkbox.addEventListener(
                "change",
                function () {

                    setActivity(
                        i,
                        checkbox.checked
                    );

                }
            );
        }

        updateUI();
    }

    window.Practical2OfflineProgress = {

        totalActivities:
            TOTAL_ACTIVITIES,

        getProgress:
            getAllProgress,

        getActivity:
            getActivity,

        setActivity:
            setActivity,

        clearProgress:
            clearProgress,

        updateUI:
            updateUI,

        getCompletedCount:
            function () {

                return getCompletedCount(
                    getProgress()
                );
            },

        getPercentage:
            function () {

                const completed =
                    getCompletedCount(
                        getProgress()
                    );

                return Math.round(
                    (completed /
                        TOTAL_ACTIVITIES) *
                        100
                );
            },

        isComplete:
            function () {

                return (
                    getCompletedCount(
                        getProgress()
                    ) ===
                    TOTAL_ACTIVITIES
                );
            }
    };

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initializeCheckboxes
        );

    } else {

        initializeCheckboxes();
    }

})();