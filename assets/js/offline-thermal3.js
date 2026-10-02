(function () {

    "use strict";

    /*
     * PRACTICAL 3
     * THERMAL PROCESSING OFFLINE SIMULATION
     *
     * This script:
     * - Runs heating and cooling simulation
     * - Generates temperature-time data
     * - Displays the maximum product temperature
     * - Calculates heating rate
     * - Displays final product temperature
     * - Draws the temperature curve
     * - Saves the latest simulation locally
     */

    const STORAGE_KEY =
        "food_process_offline_practical_3_thermal_simulation";


    const initialTempInput =
        document.getElementById(
            "offlineInitialTemp"
        );

    const retortTempInput =
        document.getElementById(
            "offlineRetortTemp"
        );

    const heatingTimeInput =
        document.getElementById(
            "offlineHeatingTime"
        );

    const coolingTimeInput =
        document.getElementById(
            "offlineCoolingTime"
        );


    const runButton =
        document.getElementById(
            "runOfflineThermal"
        );

    const clearButton =
        document.getElementById(
            "clearOfflineThermal"
        );


    const statusBox =
        document.getElementById(
            "offlineSimulationStatus"
        );


    const maxTempDisplay =
        document.getElementById(
            "offlineMaxTemp"
        );

    const heatingRateDisplay =
        document.getElementById(
            "offlineHeatingRate"
        );

    const finalTempDisplay =
        document.getElementById(
            "offlineFinalTemp"
        );


    const chartCanvas =
        document.getElementById(
            "offlineThermalChart"
        );


    let thermalChart = null;


    /*
     * Show simulation status
     */

    function showStatus(message, type) {

        if (!statusBox) {
            return;
        }


        statusBox.textContent = message;


        if (type === "success") {

            statusBox.style.background =
                "#eef5f7";

            statusBox.style.borderColor =
                "#d3e2e7";

            statusBox.style.color =
                "#17495a";

        } else if (type === "error") {

            statusBox.style.background =
                "#f8eeee";

            statusBox.style.borderColor =
                "#e2caca";

            statusBox.style.color =
                "#7a3030";

        } else {

            statusBox.style.background =
                "#f8f5ea";

            statusBox.style.borderColor =
                "#e4dcc2";

            statusBox.style.color =
                "#6b5a20";

        }

    }


    /*
     * Read and validate simulation inputs
     */

    function getInputs() {

        const initialTemp =
            Number(initialTempInput.value);

        const retortTemp =
            Number(retortTempInput.value);

        const heatingTime =
            Number(heatingTimeInput.value);

        const coolingTime =
            Number(coolingTimeInput.value);


        if (
            !Number.isFinite(initialTemp) ||
            !Number.isFinite(retortTemp) ||
            !Number.isFinite(heatingTime) ||
            !Number.isFinite(coolingTime)
        ) {

            throw new Error(
                "Please enter valid numerical values."
            );

        }


        if (heatingTime <= 0) {

            throw new Error(
                "Heating time must be greater than zero."
            );

        }


        if (coolingTime <= 0) {

            throw new Error(
                "Cooling time must be greater than zero."
            );

        }


        if (retortTemp <= initialTemp) {

            throw new Error(
                "Retort temperature should be higher than the initial product temperature."
            );

        }


        return {

            initialTemp: initialTemp,

            retortTemp: retortTemp,

            heatingTime: heatingTime,

            coolingTime: coolingTime

        };

    }


    /*
     * Generate heating data
     *
     * The product temperature approaches the retort
     * temperature progressively rather than jumping
     * immediately to the retort temperature.
     */

    function generateHeatingData(
        initialTemp,
        retortTemp,
        heatingTime
    ) {

        const data = [];

        const points = Math.max(
            10,
            Math.round(heatingTime / 2)
        );


        /*
         * Heating constant.
         *
         * A larger value gives faster heating.
         */

        const heatingConstant =
            4 / heatingTime;


        for (
            let i = 0;
            i <= points;
            i++
        ) {

            const time =
                (i / points) * heatingTime;


            const fraction =
                1 -
                Math.exp(
                    -heatingConstant * time
                );


            let temperature =
                initialTemp +
                (
                    retortTemp -
                    initialTemp
                ) *
                fraction;


            /*
             * Prevent the simulated product
             * temperature from exceeding the retort
             * temperature.
             */

            temperature =
                Math.min(
                    temperature,
                    retortTemp
                );


            data.push({

                time: time,

                temperature: temperature,

                phase: "Heating"

            });

        }


        return data;

    }


    /*
     * Generate cooling data
     */

    function generateCoolingData(
        maximumTemperature,
        initialTemp,
        coolingTime,
        heatingTime
    ) {

        const data = [];

        const points = Math.max(
            10,
            Math.round(coolingTime / 2)
        );


        const coolingConstant =
            4 / coolingTime;


        for (
            let i = 1;
            i <= points;
            i++
        ) {

            const coolingElapsed =
                (i / points) *
                coolingTime;


            const fraction =
                Math.exp(
                    -coolingConstant *
                    coolingElapsed
                );


            let temperature =
                initialTemp +
                (
                    maximumTemperature -
                    initialTemp
                ) *
                fraction;


            /*
             * Avoid dropping below the
             * starting product temperature.
             */

            temperature =
                Math.max(
                    temperature,
                    initialTemp
                );


            data.push({

                time:
                    heatingTime +
                    coolingElapsed,

                temperature:
                    temperature,

                phase: "Cooling"

            });

        }


        return data;

    }


    /*
     * Generate complete simulation
     */

    function generateSimulation(inputs) {

        const heatingData =
            generateHeatingData(
                inputs.initialTemp,
                inputs.retortTemp,
                inputs.heatingTime
            );


        const maximumTemperature =
            heatingData.reduce(
                function (maximum, point) {

                    return Math.max(
                        maximum,
                        point.temperature
                    );

                },
                inputs.initialTemp
            );


        const coolingData =
            generateCoolingData(
                maximumTemperature,
                inputs.initialTemp,
                inputs.coolingTime,
                inputs.heatingTime
            );


        const allData =
            heatingData.concat(
                coolingData
            );


        /*
         * Calculate heating rate using:
         *
         * Heating Rate =
         * (Final Temperature - Initial Temperature)
         * / Processing Time
         */

        const heatingRate =
            (
                maximumTemperature -
                inputs.initialTemp
            ) /
            inputs.heatingTime;


        const finalPoint =
            allData[allData.length - 1];


        return {

            inputs: inputs,

            heatingData: heatingData,

            coolingData: coolingData,

            data: allData,

            maximumTemperature:
                maximumTemperature,

            heatingRate:
                heatingRate,

            finalTemperature:
                finalPoint
                    ? finalPoint.temperature
                    : inputs.initialTemp,

            createdAt:
                new Date().toISOString()

        };

    }


    /*
     * Draw chart
     */

    function drawChart(simulation) {

        if (!chartCanvas) {
            return;
        }


        if (
            typeof Chart === "undefined"
        ) {

            showStatus(
                "Chart.js is not available. The simulation results were still calculated.",
                "warning"
            );

            return;

        }


        if (thermalChart) {

            thermalChart.destroy();

            thermalChart = null;

        }


        const labels =
            simulation.data.map(
                function (point) {

                    return Number(
                        point.time.toFixed(1)
                    );

                }
            );


        const temperatures =
            simulation.data.map(
                function (point) {

                    return Number(
                        point.temperature.toFixed(2)
                    );

                }
            );


        const heatingPoints =
            simulation.data.map(
                function (point) {

                    return point.phase === "Heating"
                        ? Number(
                            point.temperature.toFixed(2)
                        )
                        : null;

                }
            );


        const coolingPoints =
            simulation.data.map(
                function (point) {

                    return point.phase === "Cooling"
                        ? Number(
                            point.temperature.toFixed(2)
                        )
                        : null;

                }
            );


        thermalChart =
            new Chart(
                chartCanvas,
                {

                    type: "line",

                    data: {

                        labels: labels,

                        datasets: [

                            {

                                label:
                                    "Product Temperature",

                                data:
                                    temperatures,

                                borderColor:
                                    "#1f5f75",

                                backgroundColor:
                                    "rgba(31, 95, 117, 0.08)",

                                borderWidth: 2,

                                pointRadius: 2,

                                tension: 0.25,

                                fill: false

                            },

                            {

                                label:
                                    "Heating",

                                data:
                                    heatingPoints,

                                borderColor:
                                    "#17495a",

                                borderWidth: 2,

                                pointRadius: 0,

                                tension: 0.25,

                                fill: false

                            },

                            {

                                label:
                                    "Cooling",

                                data:
                                    coolingPoints,

                                borderColor:
                                    "#6f8790",

                                borderWidth: 2,

                                pointRadius: 0,

                                tension: 0.25,

                                fill: false

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        interaction: {

                            intersect: false,

                            mode: "index"

                        },

                        scales: {

                            x: {

                                title: {

                                    display: true,

                                    text:
                                        "Processing Time (min)"

                                }

                            },

                            y: {

                                title: {

                                    display: true,

                                    text:
                                        "Product Temperature (°C)"

                                }

                            }

                        },

                        plugins: {

                            legend: {

                                display: true

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (context) {

                                            return (
                                                context.dataset.label +
                                                ": " +
                                                context.parsed.y +
                                                " °C"
                                            );

                                        }

                                }

                            }

                        }

                    }

                }

            );

    }


    /*
     * Display results
     */

    function displayResults(simulation) {

        if (maxTempDisplay) {

            maxTempDisplay.textContent =
                simulation.maximumTemperature
                    .toFixed(2) +
                " °C";

        }


        if (heatingRateDisplay) {

            heatingRateDisplay.textContent =
                simulation.heatingRate
                    .toFixed(2) +
                " °C/min";

        }


        if (finalTempDisplay) {

            finalTempDisplay.textContent =
                simulation.finalTemperature
                    .toFixed(2) +
                " °C";

        }

    }


    /*
     * Save simulation
     */

    function saveSimulation(simulation) {

        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(simulation)
            );

            return true;

        } catch (error) {

            console.error(
                "Unable to save Practical 3 simulation:",
                error
            );

            return false;

        }

    }


    /*
     * Run simulation
     */

    function runSimulation() {

        try {

            const inputs =
                getInputs();


            const simulation =
                generateSimulation(
                    inputs
                );


            displayResults(
                simulation
            );


            drawChart(
                simulation
            );


            const saved =
                saveSimulation(
                    simulation
                );


            if (saved) {

                showStatus(
                    "Thermal processing simulation completed and saved on this device.",
                    "success"
                );

            } else {

                showStatus(
                    "Simulation completed, but the result could not be saved.",
                    "warning"
                );

            }


            /*
             * Make the latest result available
             * to the synchronization script.
             */

            window.Practical3OfflineThermal =
                {

                    storageKey:
                        STORAGE_KEY,

                    getLatestResult:
                        function () {

                            return simulation;

                        }

                };


        } catch (error) {

            console.error(
                "Practical 3 simulation error:",
                error
            );


            showStatus(
                error.message,
                "error"
            );

        }

    }


    /*
     * Load previous simulation
     */

    function loadSimulation() {

        let savedSimulation = null;


        try {

            const stored =
                localStorage.getItem(
                    STORAGE_KEY
                );


            if (stored) {

                savedSimulation =
                    JSON.parse(stored);

            }

        } catch (error) {

            console.error(
                "Unable to load Practical 3 simulation:",
                error
            );

        }


        if (
            !savedSimulation ||
            typeof savedSimulation !== "object"
        ) {

            return;

        }


        /*
         * Restore input values.
         */

        if (
            savedSimulation.inputs
        ) {

            if (
                initialTempInput &&
                Number.isFinite(
                    Number(
                        savedSimulation
                            .inputs
                            .initialTemp
                    )
                )
            ) {

                initialTempInput.value =
                    savedSimulation
                        .inputs
                        .initialTemp;

            }


            if (
                retortTempInput &&
                Number.isFinite(
                    Number(
                        savedSimulation
                            .inputs
                            .retortTemp
                    )
                )
            ) {

                retortTempInput.value =
                    savedSimulation
                        .inputs
                        .retortTemp;

            }


            if (
                heatingTimeInput &&
                Number.isFinite(
                    Number(
                        savedSimulation
                            .inputs
                            .heatingTime
                    )
                )
            ) {

                heatingTimeInput.value =
                    savedSimulation
                        .inputs
                        .heatingTime;

            }


            if (
                coolingTimeInput &&
                Number.isFinite(
                    Number(
                        savedSimulation
                            .inputs
                            .coolingTime
                    )
                )
            ) {

                coolingTimeInput.value =
                    savedSimulation
                        .inputs
                        .coolingTime;

            }

        }


        /*
         * Restore displayed results.
         */

        displayResults(
            savedSimulation
        );


        /*
         * Restore chart.
         */

        if (
            Array.isArray(
                savedSimulation.data
            )
        ) {

            drawChart(
                savedSimulation
            );

        }


        /*
         * Make the saved result available
         * to the synchronization script.
         */

        window.Practical3OfflineThermal =
            {

                storageKey:
                    STORAGE_KEY,

                getLatestResult:
                    function () {

                        return savedSimulation;

                    }

            };


        showStatus(
            "Previously saved Practical 3 simulation loaded from this device.",
            "success"
        );

    }


    /*
     * Clear simulation
     */

    function clearSimulation() {

        const confirmed =
            window.confirm(
                "Are you sure you want to clear the Practical 3 thermal simulation?"
            );


        if (!confirmed) {
            return;
        }


        try {

            localStorage.removeItem(
                STORAGE_KEY
            );

        } catch (error) {

            console.error(
                "Unable to clear Practical 3 simulation:",
                error
            );

        }


        if (thermalChart) {

            thermalChart.destroy();

            thermalChart = null;

        }


        if (maxTempDisplay) {
            maxTempDisplay.textContent = "—";
        }

        if (heatingRateDisplay) {
            heatingRateDisplay.textContent = "—";
        }

        if (finalTempDisplay) {
            finalTempDisplay.textContent = "—";
        }


        window.Practical3OfflineThermal =
            null;


        showStatus(
            "Practical 3 thermal simulation has been cleared.",
            "success"
        );

    }


    /*
     * Button events
     */

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


    /*
     * Expose useful methods for
     * offline-sync3.js
     */

    window.Practical3OfflineThermalAPI = {

        storageKey:
            STORAGE_KEY,

        run:
            runSimulation,

        clear:
            clearSimulation,

        getLatestResult:
            function () {

                if (
                    window.Practical3OfflineThermal &&
                    typeof
                        window.Practical3OfflineThermal
                            .getLatestResult ===
                        "function"
                ) {

                    return
                        window.Practical3OfflineThermal
                            .getLatestResult();

                }


                return null;

            }

    };


    /*
     * Load previous simulation when page opens.
     */

    loadSimulation();

})();