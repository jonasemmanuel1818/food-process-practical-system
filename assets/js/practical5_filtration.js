/* =========================================================
   PRACTICAL 5 — FILTRATION AND SEPARATION
   Interactive Virtual Laboratory
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const sampleBeaker = document.getElementById("sampleBeaker");
    const filterArea = document.getElementById("filterArea");
    const filterPaper = document.querySelector(".filter-paper");

    const installFilterBtn =
        document.getElementById("installFilterBtn");

    const startBtn =
        document.getElementById("startBtn");

    const resetBtn =
        document.getElementById("resetBtn");

    const statusBox =
        document.getElementById("statusBox");

    const experimentStatus =
        document.getElementById("experimentStatus");

    const retainedSolids =
        document.getElementById("retainedSolids");

    const receiverLiquid =
        document.getElementById("receiverLiquid");

    const filtrateDrop =
        document.getElementById("filtrateDrop");

    const initialMassInput =
        document.getElementById("initialMass");

    const retainedMassInput =
        document.getElementById("retainedMass");

    const filtrationTimeInput =
        document.getElementById("filtrationTime");

    const resultCard =
        document.getElementById("resultCard");

    const chartCard =
        document.getElementById("chartCard");

    const filtrateMassResult =
        document.getElementById("filtrateMassResult");

    const retainedMassResult =
        document.getElementById("retainedMassResult");

    const efficiencyResult =
        document.getElementById("efficiencyResult");

    const timeResult =
        document.getElementById("timeResult");

    const saveBtn =
        document.getElementById("saveBtn");

    const savedMessage =
        document.getElementById("savedMessage");

    const chartCanvas =
        document.getElementById("filtrationChart");


    /* =====================================================
       SIMULATION VARIABLES
    ===================================================== */

    let samplePlaced = false;

    let filterInstalled = false;

    let simulationRunning = false;

    let simulationCompleted = false;

    let filtrationChart = null;

    let filtrationTimer = null;

    let simulationData = null;


    /* =====================================================
       STATUS MESSAGE
    ===================================================== */

    function setStatus(message, type = "normal") {

        if (statusBox) {

            statusBox.textContent = message;

            statusBox.classList.remove(
                "success",
                "warning"
            );

            if (type === "success") {
                statusBox.classList.add("success");
            }

            if (type === "warning") {
                statusBox.classList.add("warning");
            }
        }


        if (experimentStatus) {

            if (type === "success") {

                experimentStatus.textContent =
                    "Completed";

            } else if (type === "warning") {

                experimentStatus.textContent =
                    "Attention";

            } else if (simulationRunning) {

                experimentStatus.textContent =
                    "Running";

            } else {

                experimentStatus.textContent =
                    "Ready";
            }
        }
    }


    /* =====================================================
       GET EXPERIMENT PARAMETERS
    ===================================================== */

    function getParameters() {

        const initialMass =
            parseFloat(initialMassInput.value);

        const retainedMass =
            parseFloat(retainedMassInput.value);

        const filtrationTime =
            parseInt(
                filtrationTimeInput.value,
                10
            );


        if (
            !Number.isFinite(initialMass) ||
            initialMass <= 0
        ) {

            throw new Error(
                "Initial sample mass must be greater than 0 g."
            );
        }


        if (
            !Number.isFinite(retainedMass) ||
            retainedMass < 0
        ) {

            throw new Error(
                "Retained solid mass cannot be negative."
            );
        }


        if (retainedMass > initialMass) {

            throw new Error(
                "Retained solid mass cannot be greater than the initial sample mass."
            );
        }


        if (
            !Number.isFinite(filtrationTime) ||
            filtrationTime < 3 ||
            filtrationTime > 60
        ) {

            throw new Error(
                "Filtration time must be between 3 and 60 seconds."
            );
        }


        return {
            initialMass,
            retainedMass,
            filtrationTime
        };
    }


    /* =====================================================
       CREATE GRAPH DATA
    ===================================================== */

    function buildGraphData(
        initialMass,
        retainedMass,
        filtrationTime
    ) {

        const filtrateMass =
            initialMass - retainedMass;

        const labels = [];

        const filtrateValues = [];

        const retainedValues = [];


        for (
            let second = 0;
            second <= filtrationTime;
            second++
        ) {

            const progress =
                second / filtrationTime;


            labels.push(second);


            filtrateValues.push(
                Number(
                    (
                        filtrateMass *
                        progress
                    ).toFixed(3)
                )
            );


            retainedValues.push(
                Number(
                    (
                        retainedMass *
                        progress
                    ).toFixed(3)
                )
            );
        }


        return {

            labels,

            filtrateMass:
                filtrateValues,

            retainedMass:
                retainedValues
        };
    }


    /* =====================================================
       DRAW FILTRATION GRAPH
    ===================================================== */

    function drawChart(graphData) {

        if (
            !chartCanvas ||
            typeof Chart === "undefined"
        ) {
            return;
        }


        if (filtrationChart) {

            filtrationChart.destroy();
        }


        filtrationChart = new Chart(
            chartCanvas,
            {

                type: "line",

                data: {

                    labels:
                        graphData.labels,

                    datasets: [

                        {

                            label:
                                "Filtrate Mass (g)",

                            data:
                                graphData.filtrateMass,

                            borderWidth: 2,

                            tension: 0.25,

                            fill: false
                        },


                        {

                            label:
                                "Retained Solid Mass (g)",

                            data:
                                graphData.retainedMass,

                            borderWidth: 2,

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
                                    "Filtration Time (seconds)"
                            }
                        },


                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text:
                                    "Mass (g)"
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
                                            " " +
                                            context.dataset.label +
                                            ": " +
                                            Number(
                                                context.parsed.y
                                            ).toFixed(2) +
                                            " g"
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
       PLACE SAMPLE
    ===================================================== */

    function placeSample() {

        if (samplePlaced) {
            return;
        }


        samplePlaced = true;


        if (sampleBeaker) {

            sampleBeaker.classList.add(
                "dragging"
            );


            setTimeout(
                function () {

                    sampleBeaker.style.transform =
                        "translate(230px, 0) scale(1.03)";

                    sampleBeaker.style.cursor =
                        "default";

                },
                50
            );
        }


        setTimeout(
            function () {

                if (sampleBeaker) {

                    sampleBeaker.style.transform =
                        "translate(230px, 125px) scale(1.03)";
                }


                if (filterArea) {

                    filterArea.classList.add(
                        "ready"
                    );
                }


                setStatus(

                    "Sample positioned. Install the filter paper before starting filtration.",

                    "success"
                );


                if (sampleBeaker) {

                    sampleBeaker.classList.remove(
                        "dragging"
                    );
                }

            },
            450
        );
    }


    /* =====================================================
       DRAG START
    ===================================================== */

    function handleDragStart(event) {

        if (
            simulationRunning ||
            simulationCompleted
        ) {

            event.preventDefault();

            return;
        }


        event.dataTransfer.setData(
            "text/plain",
            "sample-beaker"
        );


        if (sampleBeaker) {

            sampleBeaker.classList.add(
                "dragging"
            );
        }
    }


    /* =====================================================
       DRAG END
    ===================================================== */

    function handleDragEnd() {

        if (sampleBeaker) {

            sampleBeaker.classList.remove(
                "dragging"
            );
        }
    }


    /* =====================================================
       DROP SAMPLE
    ===================================================== */

    function handleDrop(event) {

        event.preventDefault();


        if (
            simulationRunning ||
            simulationCompleted
        ) {
            return;
        }


        const data =
            event.dataTransfer.getData(
                "text/plain"
            );


        if (
            data === "sample-beaker"
        ) {

            placeSample();
        }
    }


    /* =====================================================
       INSTALL FILTER PAPER
    ===================================================== */

    function installFilter() {

        if (!samplePlaced) {

            setStatus(

                "Place the sample beaker on the filtration funnel first.",

                "warning"
            );

            return;
        }


        filterInstalled = true;


        if (filterPaper) {

            filterPaper.style.background =
                "#ffffff";

            filterPaper.style.borderColor =
                "#9daab1";

            filterPaper.style.boxShadow =
                "0 0 0 3px rgba(31,95,117,.12)";
        }


        if (filterArea) {

            filterArea.classList.add(
                "ready"
            );
        }


        startBtn.disabled = false;


        setStatus(

            "Filter paper installed. The filtration process is ready to start.",

            "success"
        );


        installFilterBtn.disabled = true;
    }


    /* =====================================================
       START FILTRATION
    ===================================================== */

    function startFiltration() {

        if (simulationRunning) {
            return;
        }


        if (!samplePlaced) {

            setStatus(

                "Place the sample beaker on the filtration funnel first.",

                "warning"
            );

            return;
        }


        if (!filterInstalled) {

            setStatus(

                "Install the filter paper before starting filtration.",

                "warning"
            );

            return;
        }


        let parameters;


        try {

            parameters =
                getParameters();

        } catch (error) {

            setStatus(
                error.message,
                "warning"
            );

            return;
        }


        simulationRunning = true;

        simulationCompleted = false;


        startBtn.disabled = true;

        resetBtn.disabled = true;

        saveBtn.disabled = true;


        resultCard.style.display =
            "none";

        chartCard.style.display =
            "none";

        savedMessage.style.display =
            "none";


        if (retainedSolids) {

            retainedSolids.style.opacity =
                "1";
        }


        if (filtrateDrop) {

            filtrateDrop.classList.add(
                "animate"
            );
        }


        receiverLiquid.style.height =
            "0";


        setStatus(

            "Filtration is in progress. Observe the filtrate entering the receiving beaker."
        );


        const totalSeconds =
            parameters.filtrationTime;

        const intervalMs = 1000;

        let elapsed = 0;


        filtrationTimer =
            setInterval(
                function () {

                    elapsed++;


                    const progress =
                        Math.min(
                            elapsed /
                                totalSeconds,
                            1
                        );


                    const liquidHeight =
                        Math.max(
                            3,
                            progress * 85
                        );


                    receiverLiquid.style.height =
                        liquidHeight + "px";


                    if (
                        elapsed >=
                        totalSeconds
                    ) {

                        clearInterval(
                            filtrationTimer
                        );

                        filtrationTimer =
                            null;


                        finishFiltration(
                            parameters
                        );
                    }

                },
                intervalMs
            );
    }


    /* =====================================================
       FINISH FILTRATION
    ===================================================== */

    function finishFiltration(
        parameters
    ) {

        const filtrateMass =
            parameters.initialMass -
            parameters.retainedMass;


        const retentionPercentage =
            (
                parameters.retainedMass /
                parameters.initialMass
            ) * 100;


        const separationPercentage =
            (
                filtrateMass /
                parameters.initialMass
            ) * 100;


        const graphData =
            buildGraphData(

                parameters.initialMass,

                parameters.retainedMass,

                parameters.filtrationTime
            );


        /*
         * IMPORTANT:
         * Graph data is included inside
         * result_data so PHP stores it
         * in the database.
         */

        simulationData = {

            input_data: {

                initialMass:
                    parameters.initialMass,

                retainedMass:
                    parameters.retainedMass,

                filtrationTime:
                    parameters.filtrationTime
            },


            result_data: {

                filtration: {

                    initialMass:
                        parameters.initialMass,

                    retainedMass:
                        Number(
                            parameters.retainedMass
                        .toFixed(3)
                        ),

                    filtrateMass:
                        Number(
                            filtrateMass
                        .toFixed(3)
                        ),

                    retentionPercentage:
                        Number(
                            retentionPercentage
                        .toFixed(3)
                        ),

                    separationPercentage:
                        Number(
                            separationPercentage
                        .toFixed(3)
                        ),

                    filtrationTime:
                        parameters.filtrationTime
                },


                /* =====================================
                   GRAPH DATA
                ===================================== */

                graphData: {

                    labels:
                        graphData.labels,

                    filtrateMass:
                        graphData.filtrateMass,

                    retainedMass:
                        graphData.retainedMass
                },


                /*
                 * Compatibility structure for
                 * administration/reporting pages.
                 */

                filtrationGraph: {

                    labels:
                        graphData.labels,

                    filtrateMass:
                        graphData.filtrateMass,

                    retainedMass:
                        graphData.retainedMass
                },


                times:
                    graphData.labels,

                filtrate:
                    graphData.filtrateMass,

                retained:
                    graphData.retainedMass
            }
        };


        simulationRunning = false;

        simulationCompleted = true;


        if (filtrateDrop) {

            filtrateDrop.classList.remove(
                "animate"
            );

            filtrateDrop.style.opacity =
                "0";
        }


        receiverLiquid.style.height =
            "85px";


        filtrateMassResult.textContent =
            filtrateMass.toFixed(2);


        retainedMassResult.textContent =
            parameters.retainedMass.toFixed(2);


        efficiencyResult.textContent =
            retentionPercentage.toFixed(2) +
            "%";


        timeResult.textContent =
            parameters.filtrationTime +
            " s";


        resultCard.style.display =
            "block";

        chartCard.style.display =
            "block";


        drawChart(
            graphData
        );


        saveBtn.disabled = false;

        resetBtn.disabled = false;


        setStatus(

            "Filtration completed. Review the calculated values and graph, then save the result.",

            "success"
        );


        resultCard.scrollIntoView({

            behavior: "smooth",

            block: "nearest"
        });
    }


    /* =====================================================
       SAVE SIMULATION RESULT
    ===================================================== */

    async function saveSimulationResult() {

        if (
            !simulationData ||
            !simulationCompleted
        ) {

            setStatus(

                "Run the filtration simulation before saving the result.",

                "warning"
            );

            return;
        }


        saveBtn.disabled = true;

        savedMessage.style.display =
            "none";


        const formData =
            new FormData();


        formData.append(

            "input_data",

            JSON.stringify(
                simulationData.input_data
            )
        );


        formData.append(

            "result_data",

            JSON.stringify(
                simulationData.result_data
            )
        );


        try {

            const response =
                await fetch(

                    "../practical/save_simulation5_result.php",

                    {

                        method: "POST",

                        body: formData
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(

                    data.message ||
                    "Could not save the simulation result."
                );
            }


            savedMessage.style.display =
                "block";


            setStatus(

                "Simulation result and filtration graph data were saved successfully.",

                "success"
            );


        } catch (error) {

            console.error(

                "Practical 5 save error:",

                error
            );


            setStatus(

                error.message ||
                "Unable to save the simulation result.",

                "warning"
            );


            saveBtn.disabled = false;
        }
    }


    /* =====================================================
       RESET SIMULATION
    ===================================================== */

    function resetSimulation() {

        if (filtrationTimer) {

            clearInterval(
                filtrationTimer
            );

            filtrationTimer =
                null;
        }


        samplePlaced = false;

        filterInstalled = false;

        simulationRunning = false;

        simulationCompleted = false;

        simulationData = null;


        if (filtrationChart) {

            filtrationChart.destroy();

            filtrationChart = null;
        }


        if (sampleBeaker) {

            sampleBeaker.style.transform =
                "";

            sampleBeaker.style.cursor =
                "grab";

            sampleBeaker.classList.remove(
                "dragging"
            );
        }


        if (filterArea) {

            filterArea.classList.remove(
                "ready"
            );
        }


        if (filterPaper) {

            filterPaper.style.background =
                "#f2ead7";

            filterPaper.style.borderColor =
                "#c9baa0";

            filterPaper.style.boxShadow =
                "";
        }


        if (retainedSolids) {

            retainedSolids.style.opacity =
                "0";
        }


        if (receiverLiquid) {

            receiverLiquid.style.height =
                "0";
        }


        if (filtrateDrop) {

            filtrateDrop.classList.remove(
                "animate"
            );

            filtrateDrop.style.opacity =
                "0";
        }


        resultCard.style.display =
            "none";

        chartCard.style.display =
            "none";

        savedMessage.style.display =
            "none";


        installFilterBtn.disabled =
            false;

        startBtn.disabled =
            true;

        saveBtn.disabled =
            true;

        resetBtn.disabled =
            false;


        setStatus(

            "Drag the sample beaker onto the filtration funnel to begin setting up the experiment."
        );
    }


    /* =====================================================
       SAMPLE BEAKER EVENTS
    ===================================================== */

    if (sampleBeaker) {

        sampleBeaker.addEventListener(

            "dragstart",

            handleDragStart
        );


        sampleBeaker.addEventListener(

            "dragend",

            handleDragEnd
        );


        sampleBeaker.addEventListener(

            "click",

            function () {

                if (!samplePlaced) {

                    placeSample();
                }
            }
        );
    }


    /* =====================================================
       FILTER AREA EVENTS
    ===================================================== */

    if (filterArea) {

        filterArea.addEventListener(

            "dragover",

            function (event) {

                event.preventDefault();
            }
        );


        filterArea.addEventListener(

            "drop",

            handleDrop
        );
    }


    /* =====================================================
       BUTTON EVENTS
    ===================================================== */

    if (installFilterBtn) {

        installFilterBtn.addEventListener(

            "click",

            installFilter
        );
    }


    if (startBtn) {

        startBtn.addEventListener(

            "click",

            startFiltration
        );
    }


    if (resetBtn) {

        resetBtn.addEventListener(

            "click",

            resetSimulation
        );
    }


    if (saveBtn) {

        saveBtn.addEventListener(

            "click",

            saveSimulationResult
        );
    }

});