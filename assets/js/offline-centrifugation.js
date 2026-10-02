(function () {

    "use strict";

    const STORAGE_KEY =
        "food_process_offline_practical_2_centrifugation";

    const RCF_CONSTANT = 1.118e-5;

    function getElement(id) {
        return document.getElementById(id);
    }

    function getInputs() {

        const radiusInput =
            getElement("offlineRadius");

        const rpmInput =
            getElement("offlineRPM");

        return {
            radius: parseFloat(
                radiusInput
                    ? radiusInput.value
                    : 0
            ),

            rpm: parseFloat(
                rpmInput
                    ? rpmInput.value
                    : 0
            )
        };
    }

    function calculateRCF(radius, rpm) {

        return (
            RCF_CONSTANT *
            radius *
            Math.pow(rpm, 2)
        );
    }

    function saveSimulation(data) {

        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(data)
            );

            return true;

        } catch (error) {

            console.error(
                "Practical 2 centrifugation save error:",
                error
            );

            return false;
        }
    }

    function loadSimulation() {

        try {

            const saved =
                localStorage.getItem(
                    STORAGE_KEY
                );

            if (!saved) {
                return null;
            }

            const data =
                JSON.parse(saved);

            if (
                !data ||
                typeof data !== "object"
            ) {

                return null;
            }

            return data;

        } catch (error) {

            console.error(
                "Practical 2 centrifugation load error:",
                error
            );

            return null;
        }
    }

    function updateResults(
        radius,
        rpm,
        rcf
    ) {

        const radiusResult =
            getElement(
                "centrifugationRadiusResult"
            );

        const rpmResult =
            getElement(
                "centrifugationRPMResult"
            );

        const rcfResult =
            getElement(
                "centrifugationRCFResult"
            );

        if (radiusResult) {

            radiusResult.textContent =
                radius.toFixed(2) +
                " cm";
        }

        if (rpmResult) {

            rpmResult.textContent =
                rpm.toFixed(0);
        }

        if (rcfResult) {

            rcfResult.textContent =
                rcf.toFixed(2) +
                " × g";
        }
    }

    function updateStatus(message) {

        const status =
            getElement(
                "centrifugationSimulationStatus"
            );

        if (status) {
            status.textContent =
                message;
        }
    }

    function runSimulation() {

        const inputs =
            getInputs();

        const radius =
            inputs.radius;

        const rpm =
            inputs.rpm;

        if (
            !Number.isFinite(radius) ||
            !Number.isFinite(rpm) ||
            radius <= 0 ||
            rpm <= 0
        ) {

            updateStatus(
                "Please enter a radius and RPM greater than zero."
            );

            return null;
        }

        const rcf =
            calculateRCF(
                radius,
                rpm
            );

        updateResults(
            radius,
            rpm,
            rcf
        );

        const data = {

            practical_number: 2,

            simulation_type:
                "centrifugation",

            radius:
                radius,

            rpm:
                rpm,

            rcf:
                rcf,

            formula:
                "RCF = 1.118 × 10^-5 × r × RPM^2",

            saved_at:
                new Date().toISOString()
        };

        const saved =
            saveSimulation(data);

        if (saved) {

            updateStatus(
                "Centrifugation simulation completed and saved locally."
            );

        } else {

            updateStatus(
                "Simulation completed, but the result could not be saved."
            );
        }

        return data;
    }

    function restoreSimulation() {

        const data =
            loadSimulation();

        if (!data) {
            return null;
        }

        const radiusInput =
            getElement(
                "offlineRadius"
            );

        const rpmInput =
            getElement(
                "offlineRPM"
            );

        if (radiusInput) {

            radiusInput.value =
                data.radius ?? 10;
        }

        if (rpmInput) {

            rpmInput.value =
                data.rpm ?? 3000;
        }

        if (
            Number.isFinite(
                Number(data.radius)
            ) &&
            Number.isFinite(
                Number(data.rpm)
            )
        ) {

            const radius =
                Number(data.radius);

            const rpm =
                Number(data.rpm);

            const rcf =
                Number.isFinite(
                    Number(data.rcf)
                )
                    ? Number(data.rcf)
                    : calculateRCF(
                        radius,
                        rpm
                    );

            updateResults(
                radius,
                rpm,
                rcf
            );

            updateStatus(
                "Previously saved centrifugation simulation loaded."
            );
        }

        return data;
    }

    function clearSimulation() {

        const radiusInput =
            getElement(
                "offlineRadius"
            );

        const rpmInput =
            getElement(
                "offlineRPM"
            );

        if (radiusInput) {
            radiusInput.value = "10";
        }

        if (rpmInput) {
            rpmInput.value = "3000";
        }

        updateResults(
            0,
            0,
            0
        );

        const radiusResult =
            getElement(
                "centrifugationRadiusResult"
            );

        const rpmResult =
            getElement(
                "centrifugationRPMResult"
            );

        const rcfResult =
            getElement(
                "centrifugationRCFResult"
            );

        if (radiusResult) {
            radiusResult.textContent = "—";
        }

        if (rpmResult) {
            rpmResult.textContent = "—";
        }

        if (rcfResult) {
            rcfResult.textContent = "—";
        }

        try {

            localStorage.removeItem(
                STORAGE_KEY
            );

        } catch (error) {

            console.error(
                "Practical 2 centrifugation clear error:",
                error
            );
        }

        updateStatus(
            "Centrifugation simulation cleared."
        );
    }

    function getSimulation() {
        return loadSimulation();
    }

    function hasSimulation() {
        return loadSimulation() !== null;
    }

    function initialize() {

        const runButton =
            getElement(
                "runOfflineCentrifugation"
            );

        const clearButton =
            getElement(
                "clearOfflineCentrifugation"
            );

        if (runButton) {

            runButton.addEventListener(
                "click",
                runSimulation
            );
        }

        if (clearButton) {

            clearButton.addEventListener(
                "click",
                clearSimulation
            );
        }

        restoreSimulation();
    }

    window.Practical2OfflineCentrifugation = {

        calculateRCF:
            calculateRCF,

        run:
            runSimulation,

        load:
            loadSimulation,

        restore:
            restoreSimulation,

        clear:
            clearSimulation,

        get:
            getSimulation,

        hasData:
            hasSimulation
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