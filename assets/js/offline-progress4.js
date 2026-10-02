// assets/js/offline-progress4.js

(function () {
    "use strict";

    const STORAGE_KEY = "food_process_offline_practical_4_progress";
    const TOTAL_ACTIVITIES = 12;

    const checkboxes = document.querySelectorAll(
        ".offline-activity-checkbox[data-activity]"
    );

    const completedText = document.getElementById("offlineCompletedText");
    const progressText = document.getElementById("offlineProgressText");
    const progressBar = document.getElementById("offlineActivityProgress");
    const completionMessage = document.getElementById(
        "offlineCompletionMessage"
    );
    const clearButton = document.getElementById("clearOfflineProgress");

    function getDefaultProgress() {
        const progress = {};

        for (let i = 1; i <= TOTAL_ACTIVITIES; i++) {
            progress[i] = false;
        }

        return progress;
    }

    function loadProgress() {
        const defaultProgress = getDefaultProgress();

        try {
            const saved = localStorage.getItem(STORAGE_KEY);

            if (!saved) {
                return defaultProgress;
            }

            const parsed = JSON.parse(saved);

            for (let i = 1; i <= TOTAL_ACTIVITIES; i++) {
                if (
                    Object.prototype.hasOwnProperty.call(parsed, i)
                ) {
                    defaultProgress[i] = parsed[i] === true;
                }
            }

            return defaultProgress;
        } catch (error) {
            console.error(
                "Unable to load Practical 4 progress:",
                error
            );

            return defaultProgress;
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
                "Unable to save Practical 4 progress:",
                error
            );

            return false;
        }
    }

    function updateActivityAppearance(checkbox) {
        const activityNumber = checkbox.dataset.activity;

        if (!activityNumber) {
            return;
        }

        const activityBox = document.getElementById(
            "activityBox" + activityNumber
        );

        if (!activityBox) {
            return;
        }

        if (checkbox.checked) {
            activityBox.classList.add("completed");
        } else {
            activityBox.classList.remove("completed");
        }
    }

    function updateProgressDisplay(progress) {
        let completed = 0;

        for (let i = 1; i <= TOTAL_ACTIVITIES; i++) {
            if (progress[i] === true) {
                completed++;
            }
        }

        const percentage = Math.round(
            (completed / TOTAL_ACTIVITIES) * 100
        );

        if (completedText) {
            completedText.textContent = completed;
        }

        if (progressText) {
            progressText.textContent = percentage + "%";
        }

        if (progressBar) {
            progressBar.style.width = percentage + "%";
            progressBar.setAttribute(
                "aria-valuenow",
                String(percentage)
            );
        }

        if (completionMessage) {
            if (completed === TOTAL_ACTIVITIES) {
                completionMessage.textContent =
                    "All Practical 4 activities have been completed.";

                completionMessage.style.color = "#1f5f75";
                completionMessage.style.fontWeight = "600";
            } else if (completed > 0) {
                completionMessage.textContent =
                    completed +
                    " of " +
                    TOTAL_ACTIVITIES +
                    " activities completed. Keep going.";

                completionMessage.style.color = "";
                completionMessage.style.fontWeight = "";
            } else {
                completionMessage.textContent =
                    "Complete the activities below.";

                completionMessage.style.color = "";
                completionMessage.style.fontWeight = "";
            }
        }
    }

    function applyProgress(progress) {
        checkboxes.forEach(function (checkbox) {
            const activityNumber = checkbox.dataset.activity;

            checkbox.checked =
                progress[activityNumber] === true;

            updateActivityAppearance(checkbox);
        });

        updateProgressDisplay(progress);
    }

    function handleActivityChange(event) {
        const checkbox = event.target;

        if (!checkbox.matches(
            ".offline-activity-checkbox[data-activity]"
        )) {
            return;
        }

        const progress = loadProgress();
        const activityNumber = checkbox.dataset.activity;

        progress[activityNumber] = checkbox.checked;

        saveProgress(progress);

        updateActivityAppearance(checkbox);
        updateProgressDisplay(progress);

        window.dispatchEvent(
            new CustomEvent("offlineP4ProgressChanged", {
                detail: {
                    progress: progress
                }
            })
        );
    }

    function clearProgress() {
        const confirmed = window.confirm(
            "Clear all Practical 4 activity progress?"
        );

        if (!confirmed) {
            return;
        }

        const emptyProgress = getDefaultProgress();

        saveProgress(emptyProgress);

        applyProgress(emptyProgress);

        window.dispatchEvent(
            new CustomEvent("offlineP4ProgressChanged", {
                detail: {
                    progress: emptyProgress
                }
            })
        );

        if (completionMessage) {
            completionMessage.textContent =
                "Practical 4 progress has been cleared.";
        }
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener(
            "change",
            handleActivityChange
        );
    });

    if (clearButton) {
        clearButton.addEventListener(
            "click",
            clearProgress
        );
    }

    applyProgress(loadProgress());

    window.offlinePractical4Progress = {
        storageKey: STORAGE_KEY,

        getProgress: function () {
            return loadProgress();
        },

        saveProgress: function (progress) {
            const normalized = getDefaultProgress();

            for (let i = 1; i <= TOTAL_ACTIVITIES; i++) {
                normalized[i] =
                    progress &&
                    progress[i] === true;
            }

            saveProgress(normalized);
            applyProgress(normalized);

            return normalized;
        },

        clearProgress: function () {
            clearProgress();
        },

        getCompletedCount: function () {
            const progress = loadProgress();

            let count = 0;

            for (let i = 1; i <= TOTAL_ACTIVITIES; i++) {
                if (progress[i] === true) {
                    count++;
                }
            }

            return count;
        },

        getPercentage: function () {
            return Math.round(
                (
                    this.getCompletedCount() /
                    TOTAL_ACTIVITIES
                ) * 100
            );
        }
    };
})();