"use strict";

/*
 * Practical 3
 * Thermal Processing Simulation
 *
 * This JavaScript keeps the original simulation model:
 * - Heating
 * - Processing
 * - Cooling
 * - Heating regression
 * - Cooling regression
 * - Maximum product temperature
 * - Thermal lag
 */


/* =========================================================
   GLOBAL VARIABLES
   ========================================================= */

let simulationData = null;

let simulationPaused = false;
let simulationStopped = false;

let currentPhase = null;
let currentProgress = 0;

let simulationTimer = null;

let temperatureChart = null;
let heatingRegressionChart = null;
let coolingRegressionChart = null;


/* =========================================================
   HELPER
   ========================================================= */

function get(id) {
    return document.getElementById(id);
}


/* =========================================================
   LINEAR REGRESSION
   ========================================================= */

function linearRegression(x, y) {

    if (!x.length || !y.length || x.length !== y.length) {
        return {
            slope: 0,
            intercept: 0,
            r2: 0
        };
    }

    const n = x.length;

    const meanX =
        x.reduce((sum, value) => sum + value, 0) / n;

    const meanY =
        y.reduce((sum, value) => sum + value, 0) / n;

    let numerator = 0;
    let denominator = 0;

    for (let i = 0; i < n; i++) {

        numerator +=
            (x[i] - meanX) *
            (y[i] - meanY);

        denominator +=
            Math.pow(x[i] - meanX, 2);
    }

    const slope =
        denominator === 0
            ? 0
            : numerator / denominator;

    const intercept =
        meanY - slope * meanX;


    let ssTotal = 0;
    let ssResidual = 0;

    for (let i = 0; i < n; i++) {

        const predicted =
            slope * x[i] + intercept;

        ssTotal +=
            Math.pow(y[i] - meanY, 2);

        ssResidual +=
            Math.pow(y[i] - predicted, 2);
    }

    const r2 =
        ssTotal === 0
            ? 1
            : 1 - (ssResidual / ssTotal);


    return {
        slope: slope,
        intercept: intercept,
        r2: r2
    };
}


/* =========================================================
   CREATE SIMULATION DATA
   ========================================================= */

function createSimulationData() {

    const initialTemp =
        parseFloat(get("initialTemp").value);

    const retortTemp =
        parseFloat(get("retortTemp").value);

    const heatingTime =
        parseFloat(get("heatingTime").value);

    const processingTime =
        parseFloat(get("processingTime").value);

    const coolingTime =
        parseFloat(get("coolingTime").value);


    /* Validation */

    if (
        !Number.isFinite(initialTemp) ||
        !Number.isFinite(retortTemp) ||
        !Number.isFinite(heatingTime) ||
        !Number.isFinite(processingTime) ||
        !Number.isFinite(coolingTime)
    ) {
        alert("Please enter valid numerical values.");
        return null;
    }


    if (
        heatingTime <= 0 ||
        processingTime <= 0 ||
        coolingTime <= 0
    ) {
        alert("Heating, processing and cooling times must be greater than zero.");
        return null;
    }


    if (retortTemp <= initialTemp) {
        alert("Retort temperature must be greater than the initial product temperature.");
        return null;
    }


    /*
     * Original model values
     */

    const heatingFModel = 10;
    const heatingJModel = 1;

    const coolingFModel = 11;
    const coolingJModel = 1;


    const data = [];

    const totalTime =
        heatingTime +
        processingTime +
        coolingTime;


    /*
     * Use one-minute intervals.
     */

    for (
        let time = 0;
        time <= totalTime;
        time += 1
    ) {

        let retortTemperature;
        let productTemperature;
        let stage;


        /* =====================================================
           HEATING
           ===================================================== */

        if (time <= heatingTime) {

            stage = "Heating";

            const progress =
                time / heatingTime;


            retortTemperature =
                initialTemp +
                (retortTemp - initialTemp) *
                progress;


            /*
             * Product temperature response
             */

            productTemperature =
                initialTemp +
                (retortTemp - initialTemp) *
                (1 - Math.exp(-2 * progress));

        }


        /* =====================================================
           PROCESSING
           ===================================================== */

        else if (
            time <=
            heatingTime + processingTime
        ) {

            stage = "Processing";

            const processingElapsed =
                time - heatingTime;


            retortTemperature =
                retortTemp;


            /*
             * Product temperature approaches retort temperature.
             */

            const heatingEndProgress = 1;

            const heatingEndProductTemp =
                initialTemp +
                (retortTemp - initialTemp) *
                (1 - Math.exp(-2 * heatingEndProgress));


            productTemperature =
                retortTemp -
                (
                    retortTemp -
                    heatingEndProductTemp
                ) *
                Math.pow(
                    10,
                    -processingElapsed / heatingFModel
                );

        }


        /* =====================================================
           COOLING
           ===================================================== */

        else {

            stage = "Cooling";

            const coolingElapsed =
                time -
                heatingTime -
                processingTime;


            const coolingProgress =
                coolingElapsed /
                coolingTime;


            retortTemperature =
                retortTemp -
                (retortTemp - initialTemp) *
                coolingProgress;


            /*
             * Product temperature after processing.
             */

            const heatingEndProductTemp =
                initialTemp +
                (retortTemp - initialTemp) *
                (1 - Math.exp(-2));


            const processingEndProductTemp =
                retortTemp -
                (
                    retortTemp -
                    heatingEndProductTemp
                ) *
                Math.pow(
                    10,
                    -processingTime / heatingFModel
                );


            productTemperature =
                initialTemp +
                (
                    processingEndProductTemp -
                    initialTemp
                ) *
                Math.pow(
                    10,
                    -coolingElapsed / coolingFModel
                );
        }


        data.push({
            time: time,
            retortTemp: retortTemperature,
            productTemp: productTemperature,
            stage: stage
        });
    }


    /* =========================================================
       MAXIMUM PRODUCT TEMPERATURE
       ========================================================= */

    const maxTemperature =
        Math.max(
            ...data.map(item => item.productTemp)
        );


    /* =========================================================
       HEATING REGRESSION
       ========================================================= */

    const processingData =
        data.filter(
            item => item.stage === "Processing"
        );


    const heatingEndProductTemp =
        initialTemp +
        (retortTemp - initialTemp) *
        (1 - Math.exp(-2));


    const heatingX = [];
    const heatingY = [];


    processingData.forEach(item => {

        const temperatureRatio =
            (
                item.retortTemp -
                item.productTemp
            ) /
            (
                item.retortTemp -
                heatingEndProductTemp
            );


        if (temperatureRatio > 0) {

            const logValue =
                Math.log10(temperatureRatio);


            heatingX.push(item.time - heatingTime);
            heatingY.push(logValue);
        }
    });


    const heatingRegression =
        linearRegression(
            heatingX,
            heatingY
        );


    const heatingF =
        heatingRegression.slope !== 0
            ? Math.abs(1 / heatingRegression.slope)
            : 0;


    const heatingJ =
        Math.pow(
            10,
            heatingRegression.intercept
        );


    /* =========================================================
       COOLING REGRESSION
       ========================================================= */

    const coolingData =
        data.filter(
            item => item.stage === "Cooling"
        );


    const processingEndProductTemp =
        retortTemp -
        (
            retortTemp -
            heatingEndProductTemp
        ) *
        Math.pow(
            10,
            -processingTime / heatingFModel
        );


    const coolingX = [];
    const coolingY = [];


    coolingData.forEach(item => {

        const temperatureRatio =
            (
                item.productTemp -
                initialTemp
            ) /
            (
                processingEndProductTemp -
                initialTemp
            );


        if (temperatureRatio > 0) {

            const logValue =
                Math.log10(temperatureRatio);


            coolingX.push(
                item.time -
                heatingTime -
                processingTime
            );

            coolingY.push(logValue);
        }
    });


    const coolingRegression =
        linearRegression(
            coolingX,
            coolingY
        );


    const coolingF =
        coolingRegression.slope !== 0
            ? Math.abs(1 / coolingRegression.slope)
            : 0;


    const coolingJ =
        Math.pow(
            10,
            coolingRegression.intercept
        );


    /* =========================================================
       RETURN ALL DATA
       ========================================================= */

    return {

        parameters: {
            initialTemp,
            retortTemp,
            heatingTime,
            processingTime,
            coolingTime
        },

        data,

        maxTemperature,

        heatingRate:
            (
                retortTemp -
                initialTemp
            ) /
            heatingTime,

        coolingRate:
            (
                retortTemp -
                initialTemp
            ) /
            coolingTime,

        thermalLag:
            heatingJ,

        regressionSlope:
            heatingRegression.slope,

        heatingRegression: {
            slope: heatingRegression.slope,
            intercept: heatingRegression.intercept,
            r2: heatingRegression.r2,
            f: heatingF,
            j: heatingJ,
            x: heatingX,
            y: heatingY
        },

        coolingRegression: {
            slope: coolingRegression.slope,
            intercept: coolingRegression.intercept,
            r2: coolingRegression.r2,
            fc: coolingF,
            jc: coolingJ,
            x: coolingX,
            y: coolingY
        }

    };
}


/* =========================================================
   DISPLAY RESULTS
   ========================================================= */

function displayResults(data) {

    if (!data) {
        return;
    }


    const parameters =
        data.parameters;


    get("resultHeatingTime").textContent =
        parameters.heatingTime + " min";


    get("resultProcessingTime").textContent =
        parameters.processingTime + " min";


    get("resultCoolingTime").textContent =
        parameters.coolingTime + " min";


    get("maxTemperature").textContent =
        data.maxTemperature.toFixed(2) + " °C";


    get("heatingRate").textContent =
        data.heatingRate.toFixed(2) + " °C/min";


    get("coolingRate").textContent =
        data.coolingRate.toFixed(2) + " °C/min";


    get("thermalLag").textContent =
        data.thermalLag.toFixed(4);


    get("regressionSlope").textContent =
        data.regressionSlope.toFixed(4);


    /* Heating */

    get("analysisHeatingF").textContent =
        data.heatingRegression.f.toFixed(4);


    get("analysisHeatingJ").textContent =
        data.heatingRegression.j.toFixed(4);


    get("heatingRegressionSlope").textContent =
        data.heatingRegression.slope.toFixed(4);


    get("heatingRegressionIntercept").textContent =
        data.heatingRegression.intercept.toFixed(4);


    get("heatingRegressionR2").textContent =
        data.heatingRegression.r2.toFixed(4);


    /* Cooling */

    get("analysisCoolingF").textContent =
        data.coolingRegression.fc.toFixed(4);


    get("analysisCoolingJ").textContent =
        data.coolingRegression.jc.toFixed(4);


    get("coolingRegressionSlope").textContent =
        data.coolingRegression.slope.toFixed(4);


    get("coolingRegressionIntercept").textContent =
        data.coolingRegression.intercept.toFixed(4);


    get("coolingRegressionR2").textContent =
        data.coolingRegression.r2.toFixed(4);


    get("analysisMaxPT").textContent =
        data.maxTemperature.toFixed(2) + " °C";


    if (get("thermalLagAnalysis")) {
        get("thermalLagAnalysis").textContent =
            data.thermalLag.toFixed(4);
    }


    createTemperatureTable(data.data);

    createTemperatureChart(data);

    createHeatingRegressionChart(data);

    createCoolingRegressionChart(data);
}


/* =========================================================
   TEMPERATURE CHART
   ========================================================= */

function createTemperatureChart(data) {

    const ctx =
        get("temperatureChart").getContext("2d");


    if (temperatureChart) {
        temperatureChart.destroy();
    }


    temperatureChart =
        new Chart(ctx, {

            type: "line",

            data: {

                labels:
                    data.data.map(
                        item => item.time
                    ),

                datasets: [

                    {
                        label: "Retort Temperature (°C)",

                        data:
                            data.data.map(
                                item => item.retortTemp
                            ),

                        borderWidth: 2,

                        tension: 0.25,

                        fill: false
                    },


                    {
                        label: "Product Temperature (°C)",

                        data:
                            data.data.map(
                                item => item.productTemp
                            ),

                        borderWidth: 3,

                        tension: 0.25,

                        fill: false
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {
                    mode: "index",
                    intersect: false
                },

                scales: {

                    x: {
                        title: {
                            display: true,
                            text: "Time (min)"
                        }
                    },

                    y: {
                        title: {
                            display: true,
                            text: "Temperature (°C)"
                        }
                    }

                }

            }

        });
}


/* =========================================================
   HEATING REGRESSION CHART
   ========================================================= */

function createHeatingRegressionChart(data) {

    const ctx =
        get("heatingRegressionChart")
            .getContext("2d");


    if (heatingRegressionChart) {
        heatingRegressionChart.destroy();
    }


    const regression =
        data.heatingRegression;


    const points =
        regression.x.map(
            (x, index) => ({
                x: x,
                y: regression.y[index]
            })
        );


    const lineMin =
        Math.min(...regression.x);


    const lineMax =
        Math.max(...regression.x);


    const linePoints = [

        {
            x: lineMin,

            y:
                regression.slope * lineMin +
                regression.intercept
        },

        {
            x: lineMax,

            y:
                regression.slope * lineMax +
                regression.intercept
        }

    ];


    heatingRegressionChart =
        new Chart(ctx, {

            type: "scatter",

            data: {

                datasets: [

                    {
                        label: "Heating Data",

                        data: points,

                        pointRadius: 4
                    },


                    {
                        type: "line",

                        label: "Regression Line",

                        data: linePoints,

                        borderWidth: 2,

                        pointRadius: 0
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    x: {
                        title: {
                            display: true,
                            text: "Time During Processing (min)"
                        }
                    },

                    y: {
                        title: {
                            display: true,
                            text: "log₁₀ Temperature Ratio"
                        }
                    }

                }

            }

        });
}


/* =========================================================
   COOLING REGRESSION CHART
   ========================================================= */

function createCoolingRegressionChart(data) {

    const ctx =
        get("coolingRegressionChart")
            .getContext("2d");


    if (coolingRegressionChart) {
        coolingRegressionChart.destroy();
    }


    const regression =
        data.coolingRegression;


    const points =
        regression.x.map(
            (x, index) => ({
                x: x,
                y: regression.y[index]
            })
        );


    const lineMin =
        Math.min(...regression.x);


    const lineMax =
        Math.max(...regression.x);


    const linePoints = [

        {
            x: lineMin,

            y:
                regression.slope * lineMin +
                regression.intercept
        },

        {
            x: lineMax,

            y:
                regression.slope * lineMax +
                regression.intercept
        }

    ];


    coolingRegressionChart =
        new Chart(ctx, {

            type: "scatter",

            data: {

                datasets: [

                    {
                        label: "Cooling Data",

                        data: points,

                        pointRadius: 4
                    },


                    {
                        type: "line",

                        label: "Regression Line",

                        data: linePoints,

                        borderWidth: 2,

                        pointRadius: 0
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    x: {
                        title: {
                            display: true,
                            text: "Cooling Time (min)"
                        }
                    },

                    y: {
                        title: {
                            display: true,
                            text: "log₁₀ Temperature Ratio"
                        }
                    }

                }

            }

        });
}


/* =========================================================
   TEMPERATURE TABLE
   ========================================================= */

function createTemperatureTable(data) {

    const tbody =
        get("temperatureTableBody");


    tbody.innerHTML = "";


    data.forEach(item => {

        const row =
            document.createElement("tr");


        row.innerHTML = `

            <td>
                ${item.time}
            </td>

            <td>
                ${item.retortTemp.toFixed(2)}
            </td>

            <td>
                ${item.productTemp.toFixed(2)}
            </td>

            <td>
                ${item.stage}
            </td>

        `;


        tbody.appendChild(row);
    });
}


/* =========================================================
   PROGRESS BAR
   ========================================================= */

function updateProgressBar(
    progressId,
    percentId,
    value
) {

    const progress =
        Math.max(
            0,
            Math.min(100, value)
        );


    get(progressId).style.width =
        progress + "%";


    get(progressId).textContent =
        Math.round(progress) + "%";


    get(percentId).textContent =
        Math.round(progress) + "%";
}


/* =========================================================
   RESET PROGRESS
   ========================================================= */

function resetProgress() {

    updateProgressBar(
        "heatingProgress",
        "heatingPercent",
        0
    );

    updateProgressBar(
        "processingProgress",
        "processingPercent",
        0
    );

    updateProgressBar(
        "coolingProgress",
        "coolingPercent",
        0
    );
}


/* =========================================================
   RUN PHASE
   ========================================================= */

function runPhase(
    phase,
    duration,
    callback
) {

    currentPhase = phase;
    currentProgress = 0;


    const interval =
        Math.max(
            40,
            Math.min(
                150,
                (duration * 1000) / 100
            )
        );


    if (simulationTimer) {
        clearInterval(simulationTimer);
    }


    simulationTimer =
        setInterval(() => {

            if (simulationStopped) {

                clearInterval(simulationTimer);

                simulationTimer = null;

                return;
            }


            if (simulationPaused) {
                return;
            }


            currentProgress += 1;


            if (phase === "Heating") {

                updateProgressBar(
                    "heatingProgress",
                    "heatingPercent",
                    currentProgress
                );

            }


            if (phase === "Processing") {

                updateProgressBar(
                    "processingProgress",
                    "processingPercent",
                    currentProgress
                );

            }


            if (phase === "Cooling") {

                updateProgressBar(
                    "coolingProgress",
                    "coolingPercent",
                    currentProgress
                );

            }


            if (currentProgress >= 100) {

                clearInterval(simulationTimer);

                simulationTimer = null;

                callback();

            }

        }, interval);
}


/* =========================================================
   START SIMULATION
   ========================================================= */

function startSimulation() {

    /*
     * If a simulation already exists and has completed,
     * create a fresh simulation instead of doing nothing.
     */

    if (simulationData && !simulationStopped) {

        simulationData =
            createSimulationData();

        if (!simulationData) {
            return;
        }

    } else {

        simulationData =
            createSimulationData();

        if (!simulationData) {
            return;
        }
    }


    simulationPaused = false;
    simulationStopped = false;


    resetProgress();

    displayResults(simulationData);


    setSimulationStatus(
        "Heating...",
        "primary"
    );


    setButtonStates("running");


    runPhase(
        "Heating",
        simulationData.parameters.heatingTime,
        () => {

            if (simulationStopped) {
                return;
            }


            setSimulationStatus(
                "Processing...",
                "warning"
            );


            runPhase(
                "Processing",
                simulationData.parameters.processingTime,
                () => {

                    if (simulationStopped) {
                        return;
                    }


                    setSimulationStatus(
                        "Cooling...",
                        "info"
                    );


                    runPhase(
                        "Cooling",
                        simulationData.parameters.coolingTime,
                        () => {

                            if (simulationStopped) {
                                return;
                            }


                            setSimulationStatus(
                                "Simulation Complete",
                                "success"
                            );


                            setButtonStates(
                                "completed"
                            );

                            const saveButton =
    get("saveResultsButton");

if (saveButton) {
    saveButton.disabled = false;
}


                            get(
                                "generateReportButton"
                            ).disabled = false;

                        }
                    );

                }
            );

        }
    );
}


/* =========================================================
   PAUSE
   ========================================================= */

function pauseSimulation() {

    if (!simulationData || simulationStopped) {
        return;
    }


    simulationPaused = true;


    setSimulationStatus(
        "Paused",
        "warning"
    );


    setButtonStates("paused");
}


/* =========================================================
   RESUME
   ========================================================= */

function resumeSimulation() {

    if (!simulationData || simulationStopped) {
        return;
    }


    simulationPaused = false;


    setSimulationStatus(
        currentPhase + " — Running",
        "primary"
    );


    setButtonStates("running");
}


/* =========================================================
   STOP
   ========================================================= */

function stopSimulation() {

    simulationStopped = true;
    simulationPaused = false;


    if (simulationTimer) {

        clearInterval(simulationTimer);

        simulationTimer = null;
    }


    setSimulationStatus(
        "Stopped",
        "danger"
    );


    setButtonStates("ready");
}


/* =========================================================
   RESET
   ========================================================= */

function resetSimulation() {

    simulationPaused = false;
    simulationStopped = false;

    simulationData = null;

    currentPhase = null;
    currentProgress = 0;


    if (simulationTimer) {

        clearInterval(simulationTimer);

        simulationTimer = null;
    }


    resetProgress();


    setSimulationStatus(
        "Ready",
        "secondary"
    );


    setButtonStates("ready");


    get(
        "generateReportButton"
    ).disabled = true;

    const saveButton =
    get("saveResultsButton");

if (saveButton) {
    saveButton.disabled = true;
}


    get("resultHeatingTime").textContent = "—";
    get("resultProcessingTime").textContent = "—";
    get("resultCoolingTime").textContent = "—";

    get("maxTemperature").textContent = "—";
    get("heatingRate").textContent = "—";
    get("coolingRate").textContent = "—";
    get("thermalLag").textContent = "—";
    get("regressionSlope").textContent = "—";

    get("analysisHeatingF").textContent = "—";
    get("analysisHeatingJ").textContent = "—";

    get("analysisCoolingF").textContent = "—";
    get("analysisCoolingJ").textContent = "—";

    get("analysisMaxPT").textContent = "—";

    get("heatingRegressionSlope").textContent = "—";
    get("heatingRegressionIntercept").textContent = "—";
    get("heatingRegressionR2").textContent = "—";

    get("coolingRegressionSlope").textContent = "—";
    get("coolingRegressionIntercept").textContent = "—";
    get("coolingRegressionR2").textContent = "—";


    if (get("thermalLagAnalysis")) {
        get("thermalLagAnalysis").textContent = "—";
    }


    get("temperatureTableBody").innerHTML = `

        <tr>

            <td
                colspan="4"
                class="text-center text-muted"
            >
                Run the simulation to generate data.
            </td>

        </tr>

    `;


    if (temperatureChart) {

        temperatureChart.destroy();

        temperatureChart = null;
    }


    if (heatingRegressionChart) {

        heatingRegressionChart.destroy();

        heatingRegressionChart = null;
    }


    if (coolingRegressionChart) {

        coolingRegressionChart.destroy();

        coolingRegressionChart = null;
    }
}


/* =========================================================
   BUTTON STATES
   ========================================================= */

function setButtonStates(state) {

    const start =
        get("startSimulationButton");

    const pause =
        get("pauseSimulationButton");

    const resume =
        get("resumeSimulationButton");

    const stop =
        get("stopSimulationButton");


    if (state === "ready") {

        start.disabled = false;

        pause.disabled = true;

        resume.disabled = true;

        stop.disabled = true;
    }


    if (state === "running") {

        start.disabled = true;

        pause.disabled = false;

        resume.disabled = true;

        stop.disabled = false;
    }


    if (state === "paused") {

        start.disabled = true;

        pause.disabled = true;

        resume.disabled = false;

        stop.disabled = false;
    }


    if (state === "completed") {

        start.disabled = false;

        pause.disabled = true;

        resume.disabled = true;

        stop.disabled = true;
    }
}


/* =========================================================
   STATUS
   ========================================================= */

function setSimulationStatus(
    text,
    type
) {

    const status =
        get("simulationStatus");


    status.textContent = text;


    status.className =
        "badge status-badge bg-" + type;
}


/* =========================================================
   GENERATE REPORT
   ========================================================= */

function generateReport() {

    if (!simulationData) {

        alert(
            "Please run the simulation first."
        );

        return;
    }


    const data =
        simulationData;


    const parameters =
        data.parameters;


    const reportWindow =
        window.open(
            "",
            "_blank",
            "width=1000,height=800"
        );


    if (!reportWindow) {

        alert(
            "Please allow pop-ups to generate the report."
        );

        return;
    }


    const rows =
        data.data.map(item => `

            <tr>

                <td>${item.time}</td>

                <td>
                    ${item.retortTemp.toFixed(2)}
                </td>

                <td>
                    ${item.productTemp.toFixed(2)}
                </td>

                <td>
                    ${item.stage}
                </td>

            </tr>

        `).join("");


    reportWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                Thermal Processing Simulation Report
            </title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    margin: 40px;
                    color: #222;
                }

                h1, h2 {
                    margin-bottom: 10px;
                }

                .header {
                    text-align: center;
                    border-bottom: 2px solid #222;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }

                .section {
                    margin-bottom: 25px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }

                th, td {
                    border: 1px solid #aaa;
                    padding: 8px;
                    text-align: left;
                }

                th {
                    background: #eee;
                }

                .grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 10px;
                }

                .box {
                    border: 1px solid #ccc;
                    padding: 12px;
                }

                .print-button {
                    padding: 10px 18px;
                    border: none;
                    background: #222;
                    color: white;
                    cursor: pointer;
                    margin-bottom: 20px;
                }

                @media print {

                    .print-button {
                        display: none;
                    }

                    body {
                        margin: 20px;
                    }

                }

            </style>

        </head>


        <body>

            <button
                class="print-button"
                onclick="window.print()"
            >
                Print / Save as PDF
            </button>


            <div class="header">

                <h1>
                    Thermal Processing Simulation Report
                </h1>

                <p>
                    Practical 3 — Thermal Processing
                </p>

            </div>


            <div class="section">

                <h2>Simulation Parameters</h2>

                <div class="grid">

                    <div class="box">
                        Initial Product Temperature:
                        ${parameters.initialTemp} °C
                    </div>

                    <div class="box">
                        Retort Temperature:
                        ${parameters.retortTemp} °C
                    </div>

                    <div class="box">
                        Heating Time:
                        ${parameters.heatingTime} min
                    </div>

                    <div class="box">
                        Processing Time:
                        ${parameters.processingTime} min
                    </div>

                    <div class="box">
                        Cooling Time:
                        ${parameters.coolingTime} min
                    </div>

                </div>

            </div>


            <div class="section">

                <h2>Thermal Results</h2>

                <div class="grid">

                    <div class="box">
                        Maximum Product Temperature:
                        ${data.maxTemperature.toFixed(2)} °C
                    </div>

                    <div class="box">
                        Heating Rate:
                        ${data.heatingRate.toFixed(4)} °C/min
                    </div>

                    <div class="box">
                        Cooling Rate:
                        ${data.coolingRate.toFixed(4)} °C/min
                    </div>

                    <div class="box">
                        Thermal Lag:
                        ${data.thermalLag.toFixed(4)}
                    </div>

                </div>

            </div>


            <div class="section">

                <h2>Heating Regression</h2>

                <div class="grid">

                    <div class="box">
                        f =
                        ${data.heatingRegression.f.toFixed(4)}
                    </div>

                    <div class="box">
                        j =
                        ${data.heatingRegression.j.toFixed(4)}
                    </div>

                    <div class="box">
                        Slope =
                        ${data.heatingRegression.slope.toFixed(4)}
                    </div>

                    <div class="box">
                        Intercept =
                        ${data.heatingRegression.intercept.toFixed(4)}
                    </div>

                    <div class="box">
                        R² =
                        ${data.heatingRegression.r2.toFixed(4)}
                    </div>

                </div>

            </div>


            <div class="section">

                <h2>Cooling Regression</h2>

                <div class="grid">

                    <div class="box">
                        fc =
                        ${data.coolingRegression.fc.toFixed(4)}
                    </div>

                    <div class="box">
                        jc =
                        ${data.coolingRegression.jc.toFixed(4)}
                    </div>

                    <div class="box">
                        Slope =
                        ${data.coolingRegression.slope.toFixed(4)}
                    </div>

                    <div class="box">
                        Intercept =
                        ${data.coolingRegression.intercept.toFixed(4)}
                    </div>

                    <div class="box">
                        R² =
                        ${data.coolingRegression.r2.toFixed(4)}
                    </div>

                </div>

            </div>


            <div class="section">

                <h2>Temperature Data</h2>

                <table>

                    <thead>

                        <tr>

                            <th>
                                Time (min)
                            </th>

                            <th>
                                Retort Temp (°C)
                            </th>

                            <th>
                                Product Temp (°C)
                            </th>

                            <th>
                                Stage
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        ${rows}

                    </tbody>

                </table>

            </div>


            <div class="section">

                <h2>Conclusion</h2>

                <p>
                    The simulation demonstrates the thermal response
                    of a food product during heating, processing and
                    cooling. The generated temperature data can be used
                    to study heat penetration and regression behaviour.
                </p>

            </div>


            <script>

                window.onload = function() {
                    window.focus();
                };

            <\/script>

        </body>

        </html>

    `);


    reportWindow.document.close();
}

/* =========================================================
   SAVE RESULTS TO DATABASE
   ========================================================= */

async function saveSimulationResults() {

    if (!simulationData) {

        alert("Please run the simulation first.");

        return;
    }

    const data = simulationData;

    const inputData = {

        initialTemp:
            data.parameters.initialTemp,

        retortTemp:
            data.parameters.retortTemp,

        heatingTime:
            data.parameters.heatingTime,

        processingTime:
            data.parameters.processingTime,

        coolingTime:
            data.parameters.coolingTime

    };


    const resultData = {

        maxTemperature:
            data.maxTemperature,

        heatingRate:
            data.heatingRate,

        coolingRate:
            data.coolingRate,

        thermalLag:
            data.thermalLag,

        regressionSlope:
            data.regressionSlope,

        heatingRegression: {

            f:
                data.heatingRegression.f,

            j:
                data.heatingRegression.j,

            slope:
                data.heatingRegression.slope,

            intercept:
                data.heatingRegression.intercept,

            r2:
                data.heatingRegression.r2

        },

        coolingRegression: {

            fc:
                data.coolingRegression.fc,

            jc:
                data.coolingRegression.jc,

            slope:
                data.coolingRegression.slope,

            intercept:
                data.coolingRegression.intercept,

            r2:
                data.coolingRegression.r2

        }

    };


    const formData = new FormData();

    formData.append(
        "input_data",
        JSON.stringify(inputData)
    );

    formData.append(
        "result_data",
        JSON.stringify(resultData)
    );


    const button =
        get("saveResultsButton");


    if (button) {
        button.disabled = true;
        button.innerHTML =
            '<i class="bi bi-hourglass-split"></i> Saving...';
    }


    try {

        const response =
            await fetch(
                "../practical/save_simulation3_result.php",
                {
                    method: "POST",
                    body: formData
                }
            );


        const result =
            await response.json();


        if (result.success) {

            setSimulationStatus(
                "Results Saved",
                "success"
            );

            alert(
                "Simulation results saved successfully."
            );

        } else {

            alert(
                result.message ||
                "Could not save simulation results."
            );

            if (button) {
                button.disabled = false;
            }

        }


    } catch (error) {

        console.error(
            "Save Results Error:",
            error
        );

        alert(
            "Unable to connect to the database."
        );

        if (button) {
            button.disabled = false;
        }

    }


    if (button) {

        button.innerHTML =
            '<i class="bi bi-database"></i> Save Results';

    }

}


/* =========================================================
   DOM READY
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        setButtonStates("ready");


        get(
            "startSimulationButton"
        ).addEventListener(
            "click",
            startSimulation
        );


        get(
            "pauseSimulationButton"
        ).addEventListener(
            "click",
            pauseSimulation
        );


        get(
            "resumeSimulationButton"
        ).addEventListener(
            "click",
            resumeSimulation
        );


        get(
            "stopSimulationButton"
        ).addEventListener(
            "click",
            stopSimulation
        );


        get(
            "resetButton"
        ).addEventListener(
            "click",
            resetSimulation
        );


        get(
            "generateReportButton"
        ).addEventListener(
            "click",
            generateReport
        );

        const saveButton =
    get("saveResultsButton");

if (saveButton) {

    saveButton.addEventListener(
        "click",
        saveSimulationResults
    );

}


        get(
            "simulationForm"
        ).addEventListener(
            "submit",
            function(event) {

                event.preventDefault();

                startSimulation();

            }
        );

    }
);