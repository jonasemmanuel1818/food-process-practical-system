/* =========================================================
   PRACTICAL 5 — FILTRATION AND SEPARATION
   Interactive Virtual Laboratory
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const sampleBeaker =
        document.getElementById("sampleBeaker");

    const filterArea =
        document.getElementById("filterArea");

    const filterPaper =
        document.querySelector(".filter-paper");

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
       STATE
    ===================================================== */

    let samplePlaced = false;
    let filterInstalled = false;
    let simulationRunning = false;
    let simulationCompleted = false;

    let filtrationChart = null;
    let filtrationTimer = null;

    let simulationData = null;


    /* =====================================================
       ADD VISUAL STYLES FOR SAMPLE TRANSFER
    ===================================================== */

    const visualStyle = document.createElement("style");

    visualStyle.textContent = `

        .sample-beaker {

            transition:
                transform 0.7s cubic-bezier(.2,.8,.2,1),
                opacity 0.5s ease,
                filter 0.4s ease;

            will-change: transform, opacity;
        }


        .sample-beaker.sample-moving {

            z-index: 50;

            pointer-events: none;

            filter:
                drop-shadow(
                    0 12px 18px
                    rgba(31,95,117,.20)
                );

        }


        .sample-beaker.sample-delivered {

            opacity: 0;

            transform:
                scale(.25)
                translateY(-15px);

        }


        .sample-mixture-in-funnel {

            position: absolute;

            width: 54px;

            height: 25px;

            left: 49px;

            top: 28px;

            opacity: 0;

            transform:
                scale(.5);

            transition:
                opacity .45s ease,
                transform .45s ease;

            pointer-events: none;

            z-index: 8;

        }


        .sample-mixture-in-funnel.show {

            opacity: 1;

            transform:
                scale(1);

        }


        .sample-mixture-in-funnel span {

            position: absolute;

            width: 5px;

            height: 5px;

            border-radius: 50%;

            background: #806f45;

        }


        .sample-mixture-in-funnel span:nth-child(1) {

            left: 5px;
            top: 10px;

        }


        .sample-mixture-in-funnel span:nth-child(2) {

            left: 16px;
            top: 6px;

        }


        .sample-mixture-in-funnel span:nth-child(3) {

            left: 27px;
            top: 13px;

        }


        .sample-mixture-in-funnel span:nth-child(4) {

            left: 39px;
            top: 7px;

        }


        .sample-mixture-in-funnel span:nth-child(5) {

            left: 47px;
            top: 15px;

        }


        .sample-liquid-pour {

            position: absolute;

            width: 7px;

            height: 24px;

            border-radius: 5px;

            background: #d9c79b;

            opacity: 0;

            z-index: 20;

            pointer-events: none;

        }


        .sample-liquid-pour.show {

            animation:
                pourIntoFunnel
                .7s ease forwards;

        }


        @keyframes pourIntoFunnel {

            0% {

                opacity: 0;

                transform:
                    translateY(-10px)
                    scaleY(.5);

            }

            30% {

                opacity: 1;

            }

            100% {

                opacity: 0;

                transform:
                    translateY(28px)
                    scaleY(1);

            }

        }


        .filter-paper.receiving-sample {

            animation:
                receiveSample
                .6s ease;

        }


        @keyframes receiveSample {

            0% {

                transform:
                    scale(.9);

            }

            50% {

                transform:
                    scale(1.05);

            }

            100% {

                transform:
                    scale(1);

            }

        }

    `;

    document.head.appendChild(visualStyle);


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

                statusBox.classList.add(
                    "success"
                );
            }

            if (type === "warning") {

                statusBox.classList.add(
                    "warning"
                );
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
       PARAMETERS
    ===================================================== */

    function getParameters() {

        const initialMass =
            parseFloat(
                initialMassInput.value
            );

        const retainedMass =
            parseFloat(
                retainedMassInput.value
            );

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


        if (
            retainedMass > initialMass
        ) {

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
       GRAPH DATA
    ===================================================== */

    function buildGraphData(
        initialMass,
        retainedMass,
        filtrationTime
    ) {

        const filtrateMass =
            initialMass -
            retainedMass;

        const labels = [];

        const filtrateValues = [];

        const retainedValues = [];


        for (
            let second = 0;
            second <= filtrationTime;
            second++
        ) {

            const progress =
                second /
                filtrationTime;


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
       DRAW GRAPH
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


        filtrationChart =
            new Chart(
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
       CREATE MIXTURE VISUAL INSIDE FUNNEL
    ===================================================== */

    function createFunnelMixture() {

        if (!filterArea) {
            return;
        }


        let mixture =
            filterArea.querySelector(
                ".sample-mixture-in-funnel"
            );


        if (mixture) {
            return mixture;
        }


        mixture =
            document.createElement("div");

        mixture.className =
            "sample-mixture-in-funnel";


        for (
            let i = 0;
            i < 5;
            i++
        ) {

            const particle =
                document.createElement("span");

            mixture.appendChild(
                particle
            );
        }


        const funnelTop =
            filterArea.querySelector(
                ".funnel-top"
            );


        if (funnelTop) {

            funnelTop.appendChild(
                mixture
            );

        } else {

            filterArea.appendChild(
                mixture
            );
        }


        return mixture;
    }


    /* =====================================================
       MOVE SAMPLE TO FUNNEL
    ===================================================== */

    function placeSample() {

        if (
            samplePlaced ||
            simulationRunning ||
            simulationCompleted
        ) {

            return;
        }


        if (!sampleBeaker || !filterArea) {
            return;
        }


        samplePlaced = true;


        setStatus(
            "Moving the sample mixture to the filtration funnel...",
            "normal"
        );


        /*
         * Get the real screen positions.
         * This avoids the old fixed translate(230px,125px)
         * which caused the beaker to stop in the wrong place.
         */

        const beakerRect =
            sampleBeaker.getBoundingClientRect();

        const funnelRect =
            filterArea.getBoundingClientRect();


        const beakerCenterX =
            beakerRect.left +
            beakerRect.width / 2;

        const beakerCenterY =
            beakerRect.top +
            beakerRect.height / 2;


        const funnelCenterX =
            funnelRect.left +
            funnelRect.width / 2;

        const funnelTargetY =
            funnelRect.top +
            65;


        const moveX =
            funnelCenterX -
            beakerCenterX;


        const moveY =
            funnelTargetY -
            beakerCenterY;


        sampleBeaker.classList.add(
            "sample-moving"
        );


        /*
         * Temporarily use the current position as the
         * transformation origin.
         */

        sampleBeaker.style.transform =
            `translate(${moveX}px, ${moveY}px) scale(.72)`;


        /*
         * After the beaker reaches the funnel,
         * hide it and show the mixture inside the funnel.
         */

        setTimeout(
            function () {

                sampleBeaker.classList.add(
                    "sample-delivered"
                );


                const mixture =
                    createFunnelMixture();


                if (mixture) {

                    setTimeout(
                        function () {

                            mixture.classList.add(
                                "show"
                            );

                            if (filterPaper) {

                                filterPaper.classList.add(
                                    "receiving-sample"
                                );

                                setTimeout(
                                    function () {

                                        filterPaper.classList.remove(
                                            "receiving-sample"
                                        );

                                    },
                                    700
                                );
                            }

                        },
                        120
                    );
                }


                if (filterArea) {

                    filterArea.classList.add(
                        "ready"
                    );
                }


                setStatus(
                    "Sample mixture placed on the funnel. Install the filter paper before starting filtration.",
                    "success"
                );

            },
            720
        );
    }


    /* =====================================================
       DRAG START
    ===================================================== */

    function handleDragStart(event) {

        if (
            simulationRunning ||
            simulationCompleted ||
            samplePlaced
        ) {

            event.preventDefault();

            return;
        }


        event.dataTransfer.effectAllowed =
            "move";

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
            simulationCompleted ||
            samplePlaced
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
       INSTALL FILTER
    ===================================================== */

    function installFilter() {

        if (!samplePlaced) {

            setStatus(
                "Place the sample mixture on the filtration funnel first.",
                "warning"
            );

            return;
        }


        if (filterInstalled) {
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

        installFilterBtn.disabled = true;


        setStatus(
            "Filter paper installed. The filtration process is ready to start.",
            "success"
        );
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
                "Place the sample mixture on the filtration funnel first.",
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

        installFilterBtn.disabled = true;

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
                            parameters.retainedMass.toFixed(3)
                        ),

                    filtrateMass:
                        Number(
                            filtrateMass.toFixed(3)
                        ),

                    retentionPercentage:
                        Number(
                            retentionPercentage.toFixed(3)
                        ),

                    separationPercentage:
                        Number(
                            separationPercentage.toFixed(3)
                        ),

                    filtrationTime:
                        parameters.filtrationTime
                },


                graphData: {

                    labels:
                        graphData.labels,

                    filtrateMass:
                        graphData.filtrateMass,

                    retainedMass:
                        graphData.retainedMass
                },


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


        if (retainedSolids) {

            retainedSolids.style.opacity =
                "1";
        }


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


            const responseText =
                await response.text();


            let data;


            try {

                data =
                    JSON.parse(
                        responseText
                    );

            } catch (jsonError) {

                console.error(
                    "Server response:",
                    responseText
                );

                throw new Error(
                    "The server returned an invalid response. Check save_simulation5_result.php."
                );
            }


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


            /*
             * Keep the button disabled after a successful save
             * so the same result is not accidentally saved twice.
             */

            saveBtn.disabled = true;


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

            sampleBeaker.classList.remove(
                "sample-moving",
                "sample-delivered",
                "dragging"
            );


            sampleBeaker.style.transform =
                "";

            sampleBeaker.style.opacity =
                "";

            sampleBeaker.style.cursor =
                "grab";
        }


        /*
         * Remove the visual sample mixture
         * from inside the funnel.
         */

        const mixture =
            filterArea
                ? filterArea.querySelector(
                    ".sample-mixture-in-funnel"
                )
                : null;


        if (mixture) {

            mixture.remove();
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

            filterPaper.classList.remove(
                "receiving-sample"
            );
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


        /*
         * Clicking the sample also places it.
         * This is useful if browser drag-and-drop
         * is difficult on some devices.
         */

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

                event.dataTransfer.dropEffect =
                    "move";
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