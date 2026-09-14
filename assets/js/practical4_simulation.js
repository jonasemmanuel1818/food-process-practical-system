console.log("PRACTICAL 4 NEW JS LOADED");

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       GLOBAL VARIABLES
    ===================================================== */

    let sprayChart = null;
    let freezeChart = null;

    let spraySimulationData = null;
    let freezeSimulationData = null;


    /* =====================================================
       GET ELEMENTS
    ===================================================== */

    const sprayInletTemp =
        document.getElementById("sprayInletTemp");

    const sprayFeedFlow =
        document.getElementById("sprayFeedFlow");

    const sprayInitialMoisture =
        document.getElementById("sprayInitialMoisture");

    const sprayFinalMoisture =
        document.getElementById("sprayFinalMoisture");


    const freezeThickness =
        document.getElementById("freezeThickness");

    const freezePlateTemp =
        document.getElementById("freezePlateTemp");

    const freezePressure =
        document.getElementById("freezePressure");

    const freezeMoisture =
        document.getElementById("freezeMoisture");


    /* =====================================================
       BUTTONS
    ===================================================== */

    const runSprayBtn =
        document.getElementById("runSprayBtn");

    const saveSprayBtn =
        document.getElementById("saveSprayBtn");

    const printSprayBtn =
        document.getElementById("printSprayBtn");

    const newSprayBtn =
        document.getElementById("newSprayBtn");


    const runFreezeBtn =
        document.getElementById("runFreezeBtn");

    const saveFreezeBtn =
        document.getElementById("saveFreezeBtn");

    const printFreezeBtn =
        document.getElementById("printFreezeBtn");

    const newFreezeBtn =
        document.getElementById("newFreezeBtn");


    const printAllBtn =
        document.getElementById("printAllBtn");


    /* =====================================================
       RESULT AREAS
    ===================================================== */

    const sprayResultContent =
        document.getElementById(
            "sprayResultContent"
        );

    const freezeResultContent =
        document.getElementById(
            "freezeResultContent"
        );


    /* =====================================================
       HELPER FUNCTIONS
    ===================================================== */

    function numberValue(element) {

        if (!element) {
            return NaN;
        }

        return parseFloat(element.value);

    }


    function showAlert(message) {

        alert(message);

    }


    function formatNumber(
        value,
        decimals = 2
    ) {

        if (!Number.isFinite(value)) {
            return "N/A";
        }

        return Number(value).toFixed(decimals);

    }


    function sleep(milliseconds) {

        return new Promise(
            resolve =>
                setTimeout(
                    resolve,
                    milliseconds
                )
        );

    }


    /* =====================================================
       SPRAY DRYING VALIDATION
    ===================================================== */

    function validateSprayInputs() {

        const inletTemp =
            numberValue(
                sprayInletTemp
            );

        const feedFlow =
            numberValue(
                sprayFeedFlow
            );

        const initialMoisture =
            numberValue(
                sprayInitialMoisture
            );

        const finalMoisture =
            numberValue(
                sprayFinalMoisture
            );


        if (
            !Number.isFinite(inletTemp) ||
            inletTemp < 100 ||
            inletTemp > 300
        ) {

            showAlert(
                "Please enter a valid inlet air temperature between 100°C and 300°C."
            );

            return false;

        }


        if (
            !Number.isFinite(feedFlow) ||
            feedFlow <= 0
        ) {

            showAlert(
                "Please enter a valid feed flow rate greater than 0 kg/h."
            );

            return false;

        }


        if (
            !Number.isFinite(initialMoisture) ||
            initialMoisture <= 0 ||
            initialMoisture >= 100
        ) {

            showAlert(
                "Please enter an initial moisture value between 1% and 99%."
            );

            return false;

        }


        if (
            !Number.isFinite(finalMoisture) ||
            finalMoisture <= 0 ||
            finalMoisture >= 100
        ) {

            showAlert(
                "Please enter a final moisture value between 0.1% and 99%."
            );

            return false;

        }


        if (
            finalMoisture >= initialMoisture
        ) {

            showAlert(
                "Final moisture must be lower than initial moisture."
            );

            return false;

        }


        return true;

    }


    /* =====================================================
       FREEZE DRYING VALIDATION
    ===================================================== */

    function validateFreezeInputs() {

        const thickness =
            numberValue(
                freezeThickness
            );

        const plateTemp =
            numberValue(
                freezePlateTemp
            );

        const pressure =
            numberValue(
                freezePressure
            );

        const moisture =
            numberValue(
                freezeMoisture
            );


        if (
            !Number.isFinite(thickness) ||
            thickness <= 0
        ) {

            showAlert(
                "Please enter a valid sample thickness greater than 0 mm."
            );

            return false;

        }


        if (
            !Number.isFinite(plateTemp) ||
            plateTemp < 20 ||
            plateTemp > 40
        ) {

            showAlert(
                "Please enter a valid heating plate temperature between 20°C and 40°C."
            );

            return false;

        }


        if (
            !Number.isFinite(pressure) ||
            pressure < 0.01 ||
            pressure > 10
        ) {

            showAlert(
                "Please enter a valid chamber pressure between 0.01 and 10 mbar."
            );

            return false;

        }


        if (
            !Number.isFinite(moisture) ||
            moisture < 6 ||
            moisture > 99
        ) {

            showAlert(
                "Please enter an initial moisture value between 6% and 99%."
            );

            return false;

        }


        return true;

    }


    /* =====================================================
       SPRAY DRYING SIMULATION
    ===================================================== */

    async function runSpraySimulation() {

        if (!validateSprayInputs()) {
            return;
        }


        const inletTemp =
            numberValue(
                sprayInletTemp
            );

        const feedFlow =
            numberValue(
                sprayFeedFlow
            );

        const initialMoisture =
            numberValue(
                sprayInitialMoisture
            );

        const finalMoisture =
            numberValue(
                sprayFinalMoisture
            );


        /* Disable button */

        runSprayBtn.disabled = true;

        saveSprayBtn.disabled = true;

        printSprayBtn.disabled = true;


        /* Loading animation */

        const loading =
            document.getElementById(
                "sprayLoading"
            );

        const loadingBar =
            document.getElementById(
                "sprayLoadingBar"
            );


        loading.classList.add("show");

        loadingBar.style.width = "0%";


        for (
            let i = 0;
            i <= 100;
            i += 10
        ) {

            loadingBar.style.width =
                i + "%";

            await sleep(30);

        }


        /* =================================================
           SIMULATION MODEL
        ================================================= */

        /*
         * Higher inlet temperature
         * -> faster drying
         *
         * Higher feed flow
         * -> slower drying
         */

        const temperatureFactor =
            inletTemp / 205;


        const flowFactor =
            10 / feedFlow;


        let dryingRate =
            0.10 *
            temperatureFactor *
            flowFactor;


        if (dryingRate < 0.025) {
            dryingRate = 0.025;
        }


        if (dryingRate > 0.25) {
            dryingRate = 0.25;
        }


        const moistureRatio =
            finalMoisture /
            initialMoisture;


        let dryingTime =
            Math.log(
                1 / moistureRatio
            ) /
            dryingRate;


        if (
            !Number.isFinite(dryingTime) ||
            dryingTime <= 0
        ) {

            dryingTime = 10;

        }


        dryingTime =
            Math.max(
                5,
                Math.min(
                    dryingTime,
                    120
                )
            );


        const points = 50;

        const times = [];

        const moistureValues = [];


        for (
            let i = 0;
            i <= points;
            i++
        ) {

            const time =
                (dryingTime / points) *
                i;


            const moisture =
                initialMoisture *
                Math.exp(
                    -dryingRate *
                    time
                );


            times.push(
                Number(
                    time.toFixed(2)
                )
            );


            moistureValues.push(
                Number(
                    Math.max(
                        finalMoisture,
                        moisture
                    ).toFixed(2)
                )
            );

        }


        /* Force final point */

        moistureValues[
            moistureValues.length - 1
        ] = Number(
            finalMoisture.toFixed(2)
        );


        /* =================================================
           CALCULATIONS
        ================================================= */

        const waterRemoved =
            feedFlow *
            (
                initialMoisture -
                finalMoisture
            ) /
            100;


        const dryProduct =
            feedFlow *
            (
                1 -
                initialMoisture / 100
            );


        const approximateHeatDemand =
            waterRemoved *
            2257;


        const thermalEfficiency =
            Math.min(
                100,
                Math.max(
                    0,
                    (
                        1 -
                        (
                            feedFlow /
                            (
                                inletTemp *
                                10
                            )
                        )
                    ) *
                    100
                )
            );


        spraySimulationData = {

            simulation_type:
                "Spray Drying",

            input_data: {

                inlet_temperature:
                    inletTemp,

                feed_flow:
                    feedFlow,

                initial_moisture:
                    initialMoisture,

                final_moisture:
                    finalMoisture

            },

            result_data: {

                drying_time:
                    dryingTime,

                water_removed:
                    waterRemoved,

                dry_product:
                    dryProduct,

                heat_demand:
                    approximateHeatDemand,

                thermal_efficiency:
                    thermalEfficiency,

                times:
                    times,

                moisture:
                    moistureValues

            }

        };


        /* =================================================
           DRAW GRAPH
        ================================================= */

        drawSprayChart(
            times,
            moistureValues
        );


        /* =================================================
           SHOW RESULTS
        ================================================= */

        sprayResultContent.innerHTML = `

            <h5 class="result-title">

                <i class="bi bi-check-circle"></i>

                Spray Drying Results

            </h5>

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Estimated Drying Time
                        </div>

                        <div class="result-value">
                            ${formatNumber(dryingTime)}
                            min
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Water Removed
                        </div>

                        <div class="result-value">
                            ${formatNumber(waterRemoved)}
                            kg/h
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Dry Product
                        </div>

                        <div class="result-value">
                            ${formatNumber(dryProduct)}
                            kg/h
                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="stat-card">

                        <div class="stat-label">
                            Approx. Heat Demand
                        </div>

                        <div class="result-value">
                            ${formatNumber(
                                approximateHeatDemand
                            )}
                            kJ/h
                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="stat-card">

                        <div class="stat-label">
                            Estimated Thermal Efficiency
                        </div>

                        <div class="result-value">
                            ${formatNumber(
                                thermalEfficiency
                            )}%
                        </div>

                    </div>

                </div>

            </div>

        `;


        runSprayBtn.disabled = false;

        saveSprayBtn.disabled = false;

        printSprayBtn.disabled = false;

        loading.classList.remove("show");

    }


    /* =====================================================
       DRAW SPRAY CHART
    ===================================================== */

    function drawSprayChart(
        times,
        moistureValues
    ) {

        const canvas =
            document.getElementById(
                "sprayChart"
            );


        if (!canvas) {
            return;
        }


        if (sprayChart) {

            sprayChart.destroy();

        }


        sprayChart =
            new Chart(
                canvas,
                {

                    type: "line",

                    data: {

                        labels: times,

                        datasets: [

                            {

                                label:
                                    "Moisture (%)",

                                data:
                                    moistureValues,

                                tension: 0.35,

                                borderWidth: 3,

                                pointRadius: 2,

                                fill: true

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        animation: {

                            duration: 1800,

                            easing:
                                "easeOutQuart"

                        },


                        interaction: {

                            intersect: false,

                            mode: "index"

                        },


                        scales: {

                            x: {

                                title: {

                                    display: true,

                                    text:
                                        "Drying Time (min)"

                                }

                            },


                            y: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text:
                                        "Moisture (%)"

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
                                        function (
                                            context
                                        ) {

                                            return (
                                                " Moisture: " +
                                                context.parsed.y +
                                                "%"
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );

    }


    /* =====================================================
       FREEZE DRYING SIMULATION
    ===================================================== */

    async function runFreezeSimulation() {

        if (!validateFreezeInputs()) {
            return;
        }


        const thickness =
            numberValue(
                freezeThickness
            );

        const plateTemp =
            numberValue(
                freezePlateTemp
            );

        const pressure =
            numberValue(
                freezePressure
            );

        const initialMoisture =
            numberValue(
                freezeMoisture
            );


        runFreezeBtn.disabled = true;

        saveFreezeBtn.disabled = true;

        printFreezeBtn.disabled = true;


        /* Loading */

        const loading =
            document.getElementById(
                "freezeLoading"
            );

        const loadingBar =
            document.getElementById(
                "freezeLoadingBar"
            );


        loading.classList.add("show");

        loadingBar.style.width = "0%";


        for (
            let i = 0;
            i <= 100;
            i += 10
        ) {

            loadingBar.style.width =
                i + "%";

            await sleep(30);

        }


        /* =================================================
           FREEZE DRYING MODEL
        ================================================= */


        /*
         * Greater thickness:
         * slower drying.
         *
         * Higher plate temperature:
         * faster drying.
         *
         * Lower pressure:
         * generally supports faster sublimation
         * in this simplified model.
         */


        const thicknessFactor =
            10 /
            thickness;


        const temperatureFactor =
            plateTemp /
            30;


        const pressureFactor =
            Math.sqrt(
                0.5 /
                pressure
            );


        let dryingRate =
            0.035 *
            thicknessFactor *
            temperatureFactor *
            pressureFactor;


        dryingRate =
            Math.max(
                0.005,
                Math.min(
                    dryingRate,
                    0.12
                )
            );


        const targetMoisture =
            Math.max(
                1,
                initialMoisture *
                0.08
            );


        let dryingTime =
            Math.log(
                initialMoisture /
                targetMoisture
            ) /
            dryingRate;


        dryingTime =
            Math.max(
                30,
                Math.min(
                    dryingTime,
                    720
                )
            );


        const points = 60;

        const times = [];

        const moistureValues = [];


        for (
            let i = 0;
            i <= points;
            i++
        ) {

            const time =
                (
                    dryingTime /
                    points
                ) * i;


            const moisture =
                initialMoisture *
                Math.exp(
                    -dryingRate *
                    time
                );


            times.push(
                Number(
                    time.toFixed(2)
                )
            );


            moistureValues.push(
                Number(
                    Math.max(
                        targetMoisture,
                        moisture
                    ).toFixed(2)
                )
            );

        }


        moistureValues[
            moistureValues.length - 1
        ] = Number(
            targetMoisture.toFixed(2)
        );


        /* =================================================
           THEORETICAL / EXPERIMENTAL ESTIMATION
        ================================================= */

        const theoreticalTime =
            dryingTime;


        const experimentalVariation =
            1 +
            (
                (
                    thickness -
                    10
                ) *
                0.015
            );


        const experimentalTime =
            theoreticalTime *
            experimentalVariation;


        const difference =
            Math.abs(
                experimentalTime -
                theoreticalTime
            );


        const percentageDifference =
            (
                difference /
                theoreticalTime
            ) *
            100;


        freezeSimulationData = {

            simulation_type:
                "Freeze Drying",

            input_data: {

                thickness:
                    thickness,

                plate_temperature:
                    plateTemp,

                chamber_pressure:
                    pressure,

                initial_moisture:
                    initialMoisture

            },

            result_data: {

                theoretical_time:
                    theoreticalTime,

                experimental_time:
                    experimentalTime,

                difference:
                    difference,

                percentage_difference:
                    percentageDifference,

                final_moisture:
                    targetMoisture,

                times:
                    times,

                moisture:
                    moistureValues

            }

        };


        /* =================================================
           DRAW GRAPH
        ================================================= */

        drawFreezeChart(
            times,
            moistureValues
        );


        /* =================================================
           DISPLAY RESULTS
        ================================================= */

        freezeResultContent.innerHTML = `

            <h5 class="result-title">

                <i class="bi bi-check-circle"></i>

                Freeze Drying Results

            </h5>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Theoretical Drying Time
                        </div>

                        <div class="result-value">

                            ${formatNumber(
                                theoreticalTime
                            )}
                            min

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Estimated Experimental Time
                        </div>

                        <div class="result-value">

                            ${formatNumber(
                                experimentalTime
                            )}
                            min

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-label">
                            Final Moisture
                        </div>

                        <div class="result-value">

                            ${formatNumber(
                                targetMoisture
                            )}%

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="stat-card">

                        <div class="stat-label">
                            Difference
                        </div>

                        <div class="result-value">

                            ${formatNumber(
                                difference
                            )}
                            min

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="stat-card">

                        <div class="stat-label">
                            Percentage Difference
                        </div>

                        <div class="result-value">

                            ${formatNumber(
                                percentageDifference
                            )}%

                        </div>

                    </div>

                </div>

            </div>

        `;


        runFreezeBtn.disabled = false;

        saveFreezeBtn.disabled = false;

        printFreezeBtn.disabled = false;

        loading.classList.remove("show");

    }


    /* =====================================================
       DRAW FREEZE CHART
    ===================================================== */

    function drawFreezeChart(
        times,
        moistureValues
    ) {

        const canvas =
            document.getElementById(
                "freezeChart"
            );


        if (!canvas) {
            return;
        }


        if (freezeChart) {

            freezeChart.destroy();

        }


        freezeChart =
            new Chart(
                canvas,
                {

                    type: "line",

                    data: {

                        labels: times,

                        datasets: [

                            {

                                label:
                                    "Moisture (%)",

                                data:
                                    moistureValues,

                                tension: 0.35,

                                borderWidth: 3,

                                pointRadius: 2,

                                fill: true

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        animation: {

                            duration: 2200,

                            easing:
                                "easeOutQuart"

                        },


                        interaction: {

                            intersect: false,

                            mode: "index"

                        },


                        scales: {

                            x: {

                                title: {

                                    display: true,

                                    text:
                                        "Drying Time (min)"

                                }

                            },


                            y: {

                                beginAtZero: true,

                                title: {

                                    display: true,

                                    text:
                                        "Moisture (%)"

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
                                        function (
                                            context
                                        ) {

                                            return (
                                                " Moisture: " +
                                                context.parsed.y +
                                                "%"
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );

    }


    /* =====================================================
       SAVE SPRAY RESULT
    ===================================================== */

    async function saveSprayResult() {

        if (!spraySimulationData) {

            showAlert(
                "Please run the Spray Drying simulation first."
            );

            return;

        }


        saveSprayBtn.disabled = true;


        try {

            const response =
                await fetch(
                    "../practical/save_simulation4_result.php",
                    {

                        method: "POST",

                        headers: {

                            "Content-Type":
                                "application/json"

                        },

                        body:
                            JSON.stringify(
                                spraySimulationData
                            )

                    }
                );


            const data =
                await response.json();


            if (data.success) {

                showAlert(
                    "Spray Drying result saved successfully."
                );

            } else {

                showAlert(
                    data.message ||
                    "Could not save the result."
                );

            }

        }
        catch (error) {

            console.error(error);

            showAlert(
                "Could not connect to the server."
            );

        }
        finally {

            saveSprayBtn.disabled = false;

        }

    }


    /* =====================================================
       SAVE FREEZE RESULT
    ===================================================== */

    async function saveFreezeResult() {

        if (!freezeSimulationData) {

            showAlert(
                "Please run the Freeze Drying simulation first."
            );

            return;

        }


        saveFreezeBtn.disabled = true;


        try {

            const response =
                await fetch(
                    "../practical/save_simulation4_result.php",
                    {

                        method: "POST",

                        headers: {

                            "Content-Type":
                                "application/json"

                        },

                        body:
                            JSON.stringify(
                                freezeSimulationData
                            )

                    }
                );


            const data =
                await response.json();


            if (data.success) {

                showAlert(
                    "Freeze Drying result saved successfully."
                );

            } else {

                showAlert(
                    data.message ||
                    "Could not save the result."
                );

            }

        }
        catch (error) {

            console.error(error);

            showAlert(
                "Could not connect to the server."
            );

        }
        finally {

            saveFreezeBtn.disabled = false;

        }

    }


    /* =====================================================
       PRINT SPRAY RESULT
    ===================================================== */

    function printSprayResult() {

        if (!spraySimulationData) {

            showAlert(
                "Please run the Spray Drying simulation first."
            );

            return;

        }


        const chartImage =
            sprayChart
                ? sprayChart.toBase64Image()
                : "";


        const result =
            spraySimulationData.result_data;

        const input =
            spraySimulationData.input_data;


        const printWindow =
            window.open(
                "",
                "_blank",
                "width=900,height=700"
            );


        if (!printWindow) {

            showAlert(
                "Please allow pop-ups to print the result."
            );

            return;

        }


        printWindow.document.write(`

            <!DOCTYPE html>

            <html>

            <head>

                <title>
                    Spray Drying Simulation Result
                </title>

                <style>

                    body {

                        font-family: Arial;

                        padding: 30px;

                        color: #222;

                    }

                    h1 {

                        margin-bottom: 5px;

                    }

                    table {

                        width: 100%;

                        border-collapse:
                            collapse;

                        margin-top: 20px;

                    }

                    th,
                    td {

                        border:
                            1px solid #ccc;

                        padding: 10px;

                        text-align: left;

                    }

                    th {

                        background:
                            #f2f2f2;

                    }

                    img {

                        max-width: 100%;

                        margin-top: 25px;

                    }

                </style>

            </head>

            <body>

                <h1>
                    Spray Drying Simulation
                </h1>

                <p>
                    Practical 4
                </p>


                <h3>
                    Input Data
                </h3>

                <table>

                    <tr>
                        <th>Parameter</th>
                        <th>Value</th>
                    </tr>

                    <tr>
                        <td>Inlet Temperature</td>
                        <td>${input.inlet_temperature} °C</td>
                    </tr>

                    <tr>
                        <td>Feed Flow</td>
                        <td>${input.feed_flow} kg/h</td>
                    </tr>

                    <tr>
                        <td>Initial Moisture</td>
                        <td>${input.initial_moisture}%</td>
                    </tr>

                    <tr>
                        <td>Final Moisture</td>
                        <td>${input.final_moisture}%</td>
                    </tr>

                </table>


                <h3>
                    Results
                </h3>

                <table>

                    <tr>
                        <th>Result</th>
                        <th>Value</th>
                    </tr>

                    <tr>
                        <td>Drying Time</td>
                        <td>
                            ${formatNumber(result.drying_time)}
                            min
                        </td>
                    </tr>

                    <tr>
                        <td>Water Removed</td>
                        <td>
                            ${formatNumber(result.water_removed)}
                            kg/h
                        </td>
                    </tr>

                    <tr>
                        <td>Dry Product</td>
                        <td>
                            ${formatNumber(result.dry_product)}
                            kg/h
                        </td>
                    </tr>

                    <tr>
                        <td>Approx. Heat Demand</td>
                        <td>
                            ${formatNumber(result.heat_demand)}
                            kJ/h
                        </td>
                    </tr>

                    <tr>
                        <td>Thermal Efficiency</td>
                        <td>
                            ${formatNumber(result.thermal_efficiency)}
                            %
                        </td>
                    </tr>

                </table>


                ${
                    chartImage
                    ?
                    `<img src="${chartImage}">`
                    :
                    ""
                }


                <script>

                    window.onload =
                        function () {

                            window.print();

                        };

                <\/script>

            </body>

            </html>

        `);


        printWindow.document.close();

    }


    /* =====================================================
       PRINT FREEZE RESULT
    ===================================================== */

    function printFreezeResult() {

        if (!freezeSimulationData) {

            showAlert(
                "Please run the Freeze Drying simulation first."
            );

            return;

        }


        const chartImage =
            freezeChart
                ? freezeChart.toBase64Image()
                : "";


        const result =
            freezeSimulationData.result_data;

        const input =
            freezeSimulationData.input_data;


        const printWindow =
            window.open(
                "",
                "_blank",
                "width=900,height=700"
            );


        if (!printWindow) {

            showAlert(
                "Please allow pop-ups to print the result."
            );

            return;

        }


        printWindow.document.write(`

            <!DOCTYPE html>

            <html>

            <head>

                <title>
                    Freeze Drying Simulation Result
                </title>

                <style>

                    body {

                        font-family: Arial;

                        padding: 30px;

                        color: #222;

                    }

                    h1 {

                        margin-bottom: 5px;

                    }

                    table {

                        width: 100%;

                        border-collapse:
                            collapse;

                        margin-top: 20px;

                    }

                    th,
                    td {

                        border:
                            1px solid #ccc;

                        padding: 10px;

                        text-align: left;

                    }

                    th {

                        background:
                            #f2f2f2;

                    }

                    img {

                        max-width: 100%;

                        margin-top: 25px;

                    }

                </style>

            </head>

            <body>

                <h1>
                    Freeze Drying Simulation
                </h1>

                <p>
                    Practical 4
                </p>


                <h3>
                    Input Data
                </h3>

                <table>

                    <tr>
                        <th>Parameter</th>
                        <th>Value</th>
                    </tr>

                    <tr>
                        <td>Sample Thickness</td>
                        <td>
                            ${input.thickness} mm
                        </td>
                    </tr>

                    <tr>
                        <td>Heating Plate Temperature</td>
                        <td>
                            ${input.plate_temperature} °C
                        </td>
                    </tr>

                    <tr>
                        <td>Chamber Pressure</td>
                        <td>
                            ${input.chamber_pressure} mbar
                        </td>
                    </tr>

                    <tr>
                        <td>Initial Moisture</td>
                        <td>
                            ${input.initial_moisture}%
                        </td>
                    </tr>

                </table>


                <h3>
                    Results
                </h3>

                <table>

                    <tr>
                        <th>Result</th>
                        <th>Value</th>
                    </tr>

                    <tr>
                        <td>
                            Theoretical Drying Time
                        </td>

                        <td>
                            ${formatNumber(
                                result.theoretical_time
                            )}
                            min
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Experimental Drying Time
                        </td>

                        <td>
                            ${formatNumber(
                                result.experimental_time
                            )}
                            min
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Final Moisture
                        </td>

                        <td>
                            ${formatNumber(
                                result.final_moisture
                            )}%
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Difference
                        </td>

                        <td>
                            ${formatNumber(
                                result.difference
                            )}
                            min
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Percentage Difference
                        </td>

                        <td>
                            ${formatNumber(
                                result.percentage_difference
                            )}%
                        </td>
                    </tr>

                </table>


                ${
                    chartImage
                    ?
                    `<img src="${chartImage}">`
                    :
                    ""
                }


                <script>

                    window.onload =
                        function () {

                            window.print();

                        };

                <\/script>

            </body>

            </html>

        `);


        printWindow.document.close();

    }


    /* =====================================================
       START NEW SPRAY SIMULATION
    ===================================================== */

    function newSpraySimulation() {

        sprayInletTemp.value = 205;

        sprayFeedFlow.value = 10;

        sprayInitialMoisture.value = 60;

        sprayFinalMoisture.value = 5;


        spraySimulationData = null;


        saveSprayBtn.disabled = true;

        printSprayBtn.disabled = true;


        sprayResultContent.innerHTML = `

            <h5 class="result-title">

                Spray Drying Results

            </h5>

            <p class="text-muted mb-0">

                Run the simulation to display
                calculated results.

            </p>

        `;


        if (sprayChart) {

            sprayChart.destroy();

            sprayChart = null;

        }

    }


    /* =====================================================
       START NEW FREEZE SIMULATION
    ===================================================== */

    function newFreezeSimulation() {

        freezeThickness.value = 10;

        freezePlateTemp.value = 30;

        freezePressure.value = 0.5;

        freezeMoisture.value = 70;


        freezeSimulationData = null;


        saveFreezeBtn.disabled = true;

        printFreezeBtn.disabled = true;


        freezeResultContent.innerHTML = `

            <h5 class="result-title">

                Freeze Drying Results

            </h5>

            <p class="text-muted mb-0">

                Run the simulation to display
                calculated results.

            </p>

        `;


        if (freezeChart) {

            freezeChart.destroy();

            freezeChart = null;

        }

    }


    /* =====================================================
       PRINT ALL
    ===================================================== */

    function printAllResults() {

        if (
            !spraySimulationData &&
            !freezeSimulationData
        ) {

            showAlert(
                "Please run at least one simulation before printing."
            );

            return;

        }


        if (spraySimulationData) {

            printSprayResult();

        }


        if (
            freezeSimulationData &&
            !spraySimulationData
        ) {

            printFreezeResult();

        }


        if (
            spraySimulationData &&
            freezeSimulationData
        ) {

            setTimeout(
                function () {

                    showAlert(
                        "Both simulations have results. Use each section's Print Result button to print them separately."
                    );

                },
                500
            );

        }

    }


    /* =====================================================
       EVENT LISTENERS
    ===================================================== */

    if (runSprayBtn) {

        runSprayBtn.addEventListener(
            "click",
            runSpraySimulation
        );

    }


    if (saveSprayBtn) {

        saveSprayBtn.addEventListener(
            "click",
            saveSprayResult
        );

    }


    if (printSprayBtn) {

        printSprayBtn.addEventListener(
            "click",
            printSprayResult
        );

    }


    if (newSprayBtn) {

        newSprayBtn.addEventListener(
            "click",
            newSpraySimulation
        );

    }


    if (runFreezeBtn) {

        runFreezeBtn.addEventListener(
            "click",
            runFreezeSimulation
        );

    }


    if (saveFreezeBtn) {

        saveFreezeBtn.addEventListener(
            "click",
            saveFreezeResult
        );

    }


    if (printFreezeBtn) {

        printFreezeBtn.addEventListener(
            "click",
            printFreezeResult
        );

    }


    if (newFreezeBtn) {

        newFreezeBtn.addEventListener(
            "click",
            newFreezeSimulation
        );

    }


    if (printAllBtn) {

        printAllBtn.addEventListener(
            "click",
            printAllResults
        );

    }


    /* =====================================================
       INITIAL STATE
    ===================================================== */

    if (saveSprayBtn) {

        saveSprayBtn.disabled = true;

    }


    if (printSprayBtn) {

        printSprayBtn.disabled = true;

    }


    if (saveFreezeBtn) {

        saveFreezeBtn.disabled = true;

    }


    if (printFreezeBtn) {

        printFreezeBtn.disabled = true;

    }

});