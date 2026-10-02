(function () {

    "use strict";

    const STORAGE_KEY =
        "food_process_offline_practical_5_filtration";

    const TABLE_STORAGE_KEY =
        "food_process_offline_practical_5_measurements";


    function getElement(id) {

        return document.getElementById(id);

    }


    function getNumber(id) {

        const element = getElement(id);

        if (!element) {
            return NaN;
        }

        return parseFloat(element.value);

    }


    function formatNumber(value, decimals) {

        if (!Number.isFinite(value)) {
            return "0";
        }

        return value.toFixed(decimals);

    }


    /*
    =====================================================
    CALCULATE FILTRATION
    =====================================================
    */

    function calculateFiltration() {

        const initialMass =
            getNumber("offlineInitialMass");

        const retainedMass =
            getNumber("offlineRetainedMass");

        const filtrationTime =
            getNumber("offlineFiltrationTime");


        if (
            !Number.isFinite(initialMass) ||
            initialMass <= 0
        ) {

            showMessage(
                "Please enter an initial sample mass greater than 0 g.",
                "error"
            );

            return;

        }


        if (
            !Number.isFinite(retainedMass) ||
            retainedMass < 0
        ) {

            showMessage(
                "Please enter a valid retained solid mass.",
                "error"
            );

            return;

        }


        if (retainedMass > initialMass) {

            showMessage(
                "Retained solid mass cannot be greater than the initial sample mass.",
                "error"
            );

            return;

        }


        if (
            !Number.isFinite(filtrationTime) ||
            filtrationTime < 0
        ) {

            showMessage(
                "Please enter a valid filtration time.",
                "error"
            );

            return;

        }


        /*
        CALCULATIONS
        */

        const filtrateMass =
            initialMass - retainedMass;


        const retentionPercentage =
            (retainedMass / initialMass) * 100;


        const separationPercentage =
            (filtrateMass / initialMass) * 100;


        /*
        DISPLAY SIMULATION RESULTS
        */

        setResult(
            "offlineFiltrateMass",
            formatNumber(filtrateMass, 2) + " g"
        );


        setResult(
            "offlineRetentionPercentage",
            formatNumber(retentionPercentage, 2) + " %"
        );


        setResult(
            "offlineSeparationPercentage",
            formatNumber(separationPercentage, 2) + " %"
        );


        setResult(
            "offlineResultFiltrationTime",
            formatNumber(filtrationTime, 2) + " seconds"
        );


        /*
        FILL EDITABLE MEASUREMENT TABLE
        */

        setTableValue(
            "tableInitialMass",
            initialMass
        );


        setTableValue(
            "tableRetainedMass",
            retainedMass
        );


        setTableValue(
            "tableFiltrateMass",
            filtrateMass
        );


        setTableValue(
            "tableFiltrationTime",
            filtrationTime
        );


        setTableValue(
            "tableRetentionPercentage",
            retentionPercentage
        );


        setTableValue(
            "tableSeparationPercentage",
            separationPercentage
        );


        /*
        SAVE SIMULATION
        */

        const simulationData = {

            input_data: {

                initialMass:
                    initialMass,

                retainedMass:
                    retainedMass,

                filtrationTime:
                    filtrationTime

            },


            result_data: {

                filtration: {

                    initialMass:
                        initialMass,

                    retainedMass:
                        retainedMass,

                    filtrateMass:
                        filtrateMass,

                    retentionPercentage:
                        retentionPercentage,

                    separationPercentage:
                        separationPercentage,

                    filtrationTime:
                        filtrationTime

                }

            },


            savedAt:
                new Date().toISOString()

        };


        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(simulationData)
        );


        /*
        SAVE TABLE
        */

        saveMeasurementTable();


        showResults();


        showMessage(
            "Filtration simulation calculated and saved on this device.",
            "success"
        );

    }


    /*
    =====================================================
    SET SIMULATION RESULT
    =====================================================
    */

    function setResult(id, value) {

        const element =
            getElement(id);

        if (!element) {
            return;
        }

        element.textContent =
            value;

    }


    /*
    =====================================================
    SET TABLE VALUE
    =====================================================
    */

    function setTableValue(id, value) {

        const element =
            getElement(id);

        if (!element) {
            return;
        }

        if (
            Number.isFinite(value)
        ) {

            element.value =
                formatNumber(value, 2);

        }

    }


    /*
    =====================================================
    SHOW RESULTS
    =====================================================
    */

    function showResults() {

        const results =
            getElement(
                "offlineFiltrationResults"
            );

        if (!results) {
            return;
        }

        results.style.display =
            "block";

    }


    /*
    =====================================================
    SHOW MESSAGE
    =====================================================
    */

    function showMessage(
        message,
        type
    ) {

        const messageBox =
            getElement(
                "offlineFiltrationMessage"
            );

        if (!messageBox) {
            return;
        }

        messageBox.textContent =
            message;

        messageBox.className =
            "offline-record-status";


        if (type === "success") {

            messageBox.classList.add(
                "success"
            );

        }


        if (type === "error") {

            messageBox.classList.add(
                "error"
            );

        }

    }


    /*
    =====================================================
    SAVE EDITABLE MEASUREMENT TABLE
    =====================================================
    */

    function saveMeasurementTable() {

        const tableData = {

            initialMass:
                getTableValue(
                    "tableInitialMass"
                ),

            retainedMass:
                getTableValue(
                    "tableRetainedMass"
                ),

            filtrateMass:
                getTableValue(
                    "tableFiltrateMass"
                ),

            filtrationTime:
                getTableValue(
                    "tableFiltrationTime"
                ),

            retentionPercentage:
                getTableValue(
                    "tableRetentionPercentage"
                ),

            separationPercentage:
                getTableValue(
                    "tableSeparationPercentage"
                ),

            savedAt:
                new Date().toISOString()

        };


        localStorage.setItem(
            TABLE_STORAGE_KEY,
            JSON.stringify(tableData)
        );

    }


    /*
    =====================================================
    GET TABLE VALUE
    =====================================================
    */

    function getTableValue(id) {

        const element =
            getElement(id);

        if (!element) {
            return "";
        }

        return element.value;

    }


    /*
    =====================================================
    LOAD MEASUREMENT TABLE
    =====================================================
    */

    function loadMeasurementTable() {

        const saved =
            localStorage.getItem(
                TABLE_STORAGE_KEY
            );


        if (!saved) {
            return;
        }


        try {

            const data =
                JSON.parse(saved);


            setTableValueFromStorage(
                "tableInitialMass",
                data.initialMass
            );


            setTableValueFromStorage(
                "tableRetainedMass",
                data.retainedMass
            );


            setTableValueFromStorage(
                "tableFiltrateMass",
                data.filtrateMass
            );


            setTableValueFromStorage(
                "tableFiltrationTime",
                data.filtrationTime
            );


            setTableValueFromStorage(
                "tableRetentionPercentage",
                data.retentionPercentage
            );


            setTableValueFromStorage(
                "tableSeparationPercentage",
                data.separationPercentage
            );

        }
        catch (error) {

            console.error(
                "Could not restore measurement table:",
                error
            );

        }

    }


    /*
    =====================================================
    RESTORE TABLE VALUE
    =====================================================
    */

    function setTableValueFromStorage(
        id,
        value
    ) {

        const element =
            getElement(id);

        if (!element) {
            return;
        }

        if (
            value !== undefined &&
            value !== null
        ) {

            element.value =
                value;

        }

    }


    /*
    =====================================================
    LOAD SIMULATION
    =====================================================
    */

    function loadSimulation() {

        const saved =
            localStorage.getItem(
                STORAGE_KEY
            );


        if (!saved) {
            return;
        }


        try {

            const data =
                JSON.parse(saved);


            if (
                !data.input_data ||
                !data.result_data
            ) {

                return;

            }


            const initialMass =
                getElement(
                    "offlineInitialMass"
                );


            const retainedMass =
                getElement(
                    "offlineRetainedMass"
                );


            const filtrationTime =
                getElement(
                    "offlineFiltrationTime"
                );


            if (
                initialMass &&
                data.input_data.initialMass !== undefined
            ) {

                initialMass.value =
                    data.input_data.initialMass;

            }


            if (
                retainedMass &&
                data.input_data.retainedMass !== undefined
            ) {

                retainedMass.value =
                    data.input_data.retainedMass;

            }


            if (
                filtrationTime &&
                data.input_data.filtrationTime !== undefined
            ) {

                filtrationTime.value =
                    data.input_data.filtrationTime;

            }


            const filtration =
                data.result_data.filtration;


            if (filtration) {

                setResult(
                    "offlineFiltrateMass",
                    formatNumber(
                        filtration.filtrateMass,
                        2
                    ) + " g"
                );


                setResult(
                    "offlineRetentionPercentage",
                    formatNumber(
                        filtration.retentionPercentage,
                        2
                    ) + " %"
                );


                setResult(
                    "offlineSeparationPercentage",
                    formatNumber(
                        filtration.separationPercentage,
                        2
                    ) + " %"
                );


                setResult(
                    "offlineResultFiltrationTime",
                    formatNumber(
                        filtration.filtrationTime,
                        2
                    ) + " seconds"
                );


                showResults();

            }


            showMessage(
                "Previous offline simulation restored.",
                "success"
            );

        }
        catch (error) {

            console.error(
                "Could not restore offline filtration simulation:",
                error
            );

        }

    }


    /*
    =====================================================
    CLEAR SIMULATION AND TABLE
    =====================================================
    */

    function clearSimulation() {

        const confirmed =
            window.confirm(
                "Clear the saved offline filtration simulation and measurement table?"
            );


        if (!confirmed) {
            return;
        }


        localStorage.removeItem(
            STORAGE_KEY
        );


        localStorage.removeItem(
            TABLE_STORAGE_KEY
        );


        const initialMass =
            getElement(
                "offlineInitialMass"
            );


        const retainedMass =
            getElement(
                "offlineRetainedMass"
            );


        const filtrationTime =
            getElement(
                "offlineFiltrationTime"
            );


        if (initialMass) {
            initialMass.value = "";
        }


        if (retainedMass) {
            retainedMass.value = "";
        }


        if (filtrationTime) {
            filtrationTime.value = "";
        }


        /*
        CLEAR TABLE
        */

        clearTableValue(
            "tableInitialMass"
        );


        clearTableValue(
            "tableRetainedMass"
        );


        clearTableValue(
            "tableFiltrateMass"
        );


        clearTableValue(
            "tableFiltrationTime"
        );


        clearTableValue(
            "tableRetentionPercentage"
        );


        clearTableValue(
            "tableSeparationPercentage"
        );


        /*
        CLEAR RESULTS
        */

        setResult(
            "offlineFiltrateMass",
            "—"
        );


        setResult(
            "offlineRetentionPercentage",
            "—"
        );


        setResult(
            "offlineSeparationPercentage",
            "—"
        );


        setResult(
            "offlineResultFiltrationTime",
            "—"
        );


        const results =
            getElement(
                "offlineFiltrationResults"
            );


        if (results) {

            results.style.display =
                "none";

        }


        showMessage(
            "Offline filtration simulation and measurement table cleared.",
            "success"
        );

    }


    /*
    =====================================================
    CLEAR TABLE FIELD
    =====================================================
    */

    function clearTableValue(id) {

        const element =
            getElement(id);

        if (!element) {
            return;
        }

        element.value = "";

    }


    /*
    =====================================================
    INITIALIZE
    =====================================================
    */

    function initialize() {

        const simulation =
            getElement(
                "offlineFiltrationSimulation"
            );


        if (!simulation) {
            return;
        }


        /*
        CALCULATE BUTTON
        */

        const calculateButton =
            getElement(
                "calculateOfflineFiltration"
            );


        if (calculateButton) {

            calculateButton.addEventListener(
                "click",
                calculateFiltration
            );

        }


        /*
        CLEAR BUTTON
        */

        const clearButton =
            getElement(
                "clearOfflineFiltration"
            );


        if (clearButton) {

            clearButton.addEventListener(
                "click",
                clearSimulation
            );

        }


        /*
        TABLE INPUTS
        */

        const tableInputs = [

            "tableInitialMass",

            "tableRetainedMass",

            "tableFiltrateMass",

            "tableFiltrationTime",

            "tableRetentionPercentage",

            "tableSeparationPercentage"

        ];


        tableInputs.forEach(
            function (id) {

                const input =
                    getElement(id);


                if (!input) {
                    return;
                }


                input.addEventListener(
                    "input",
                    function () {

                        saveMeasurementTable();

                    }
                );

            }
        );


        /*
        RESTORE DATA
        */

        loadSimulation();

        loadMeasurementTable();

    }


    /*
    =====================================================
    START
    =====================================================
    */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize
        );

    }
    else {

        initialize();

    }

})();