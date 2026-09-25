document.addEventListener("DOMContentLoaded", function () {

    const sampleBeaker =
        document.getElementById("sampleBeaker");

    const filterArea =
        document.getElementById("filterArea");

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

    const saveBtn =
        document.getElementById("saveBtn");

    const savedMessage =
        document.getElementById("savedMessage");


    const filtrateMassResult =
        document.getElementById("filtrateMassResult");

    const retainedMassResult =
        document.getElementById("retainedMassResult");

    const efficiencyResult =
        document.getElementById("efficiencyResult");

    const timeResult =
        document.getElementById("timeResult");


    let samplePlaced = false;
    let filterInstalled = false;
    let simulationRunning = false;
    let simulationCompleted = false;

    let filtrationChart = null;


    /*
    ============================================================
    DRAG SAMPLE
    ============================================================
    */

    sampleBeaker.addEventListener(
        "dragstart",
        function (event) {

            if (
                simulationRunning ||
                simulationCompleted
            ) {
                event.preventDefault();
                return;
            }

            sampleBeaker.classList.add(
                "dragging"
            );

            event.dataTransfer.setData(
                "text/plain",
                "sample"
            );
        }
    );


    sampleBeaker.addEventListener(
        "dragend",
        function () {

            sampleBeaker.classList.remove(
                "dragging"
            );
        }
    );


    /*
    ============================================================
    DROP SAMPLE
    ============================================================
    */

    filterArea.addEventListener(
        "dragover",
        function (event) {

            event.preventDefault();

            if (
                !simulationRunning &&
                !simulationCompleted
            ) {

                filterArea.classList.add(
                    "ready"
                );
            }
        }
    );


    filterArea.addEventListener(
        "dragleave",
        function () {

            filterArea.classList.remove(
                "ready"
            );
        }
    );


    filterArea.addEventListener(
        "drop",
        function (event) {

            event.preventDefault();

            filterArea.classList.remove(
                "ready"
            );

            const draggedItem =
                event.dataTransfer.getData(
                    "text/plain"
                );

            if (
                draggedItem !== "sample" ||
                simulationRunning ||
                simulationCompleted
            ) {
                return;
            }

            samplePlaced = true;

            sampleBeaker.style.opacity = "0.35";

            statusBox.className =
                "status-box success";

            statusBox.innerHTML =
                '<i class="bi bi-check-circle me-1"></i>' +
                'Sample placed on the filtration funnel. ' +
                'Install the filter paper before starting.';

            experimentStatus.textContent =
                "Sample Ready";

            if (filterInstalled) {
                startBtn.disabled = false;
            }
        }
    );


    /*
    ============================================================
    INSTALL FILTER
    ============================================================
    */

    installFilterBtn.addEventListener(
        "click",
        function () {

            if (
                simulationRunning ||
                simulationCompleted
            ) {
                return;
            }

            filterInstalled = true;

            filterArea.classList.add(
                "ready"
            );

            statusBox.className =
                "status-box success";

            if (samplePlaced) {

                statusBox.innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>' +
                    'Filter paper installed and sample positioned. ' +
                    'You can now start filtration.';

                startBtn.disabled = false;

            } else {

                statusBox.innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>' +
                    'Filter paper installed. ' +
                    'Drag the sample beaker onto the funnel.';
            }

            experimentStatus.textContent =
                "Setup Ready";

            installFilterBtn.disabled = true;
        }
    );


    /*
    ============================================================
    START FILTRATION
    ============================================================
    */

    startBtn.addEventListener(
        "click",
        function () {

            if (
                !samplePlaced ||
                !filterInstalled ||
                simulationRunning
            ) {
                return;
            }

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


            /*
            ----------------------------------------------------
            VALIDATION
            ----------------------------------------------------
            */

            if (
                !Number.isFinite(initialMass) ||
                initialMass <= 0
            ) {

                showStatus(
                    "Enter a valid initial mass.",
                    "warning"
                );

                return;
            }


            if (
                !Number.isFinite(retainedMass) ||
                retainedMass < 0 ||
                retainedMass > initialMass
            ) {

                showStatus(
                    "Retained mass must be between 0 and the initial mass.",
                    "warning"
                );

                return;
            }


            if (
                !Number.isFinite(filtrationTime) ||
                filtrationTime < 3 ||
                filtrationTime > 60
            ) {

                showStatus(
                    "Filtration time must be between 3 and 60 seconds.",
                    "warning"
                );

                return;
            }


            /*
            ----------------------------------------------------
            START
            ----------------------------------------------------
            */

            simulationRunning = true;

            startBtn.disabled = true;

            installFilterBtn.disabled = true;

            experimentStatus.textContent =
                "Filtrating";

            showStatus(
                "Filtration is in progress. Observe the separation process.",
                ""
            );


            filtrateDrop.classList.add(
                "animate"
            );


            /*
            ----------------------------------------------------
            CALCULATIONS
            ----------------------------------------------------
            */

            const filtrateMass =
                initialMass - retainedMass;

            const retentionPercentage =
                (
                    retainedMass /
                    initialMass
                ) * 100;


            /*
            ----------------------------------------------------
            ANIMATE LIQUID
            ----------------------------------------------------
            */

            receiverLiquid.style.height =
                "0%";


            let elapsed = 0;

            const intervalTime = 1000;


            const filtrationInterval =
                setInterval(
                    function () {

                        elapsed++;


                        const progress =
                            Math.min(
                                100,
                                (
                                    elapsed /
                                    filtrationTime
                                ) * 100
                            );


                        receiverLiquid.style.height =
                            (progress * 0.65) + "%";


                        if (
                            elapsed >= filtrationTime
                        ) {

                            clearInterval(
                                filtrationInterval
                            );

                            finishSimulation(
                                initialMass,
                                retainedMass,
                                filtrateMass,
                                retentionPercentage,
                                filtrationTime
                            );
                        }

                    },
                    intervalTime
                );
        }
    );


    /*
    ============================================================
    FINISH SIMULATION
    ============================================================
    */

    function finishSimulation(
        initialMass,
        retainedMass,
        filtrateMass,
        retentionPercentage,
        filtrationTime
    ) {

        simulationRunning = false;

        simulationCompleted = true;


        filtrateDrop.classList.remove(
            "animate"
        );


        retainedSolids.style.opacity =
            "1";


        receiverLiquid.style.height =
            "65%";


        experimentStatus.textContent =
            "Completed";


        statusBox.className =
            "status-box success";


        statusBox.innerHTML =
            '<i class="bi bi-check-circle me-1"></i>' +
            'Filtration completed successfully. ' +
            'Review the calculated results below.';


        filtrateMassResult.textContent =
            filtrateMass.toFixed(2);


        retainedMassResult.textContent =
            retainedMass.toFixed(2);


        efficiencyResult.textContent =
            retentionPercentage.toFixed(2) + "%";


        timeResult.textContent =
            filtrationTime + " s";


        resultCard.style.display =
            "block";


        chartCard.style.display =
            "block";


        createChart(
            filtrationTime,
            retainedMass,
            initialMass
        );
    }


    /*
    ============================================================
    CHART
    ============================================================
    */

    function createChart(
        filtrationTime,
        retainedMass,
        initialMass
    ) {

        const labels = [];

        const retainedValues = [];

        const steps =
            Math.max(
                2,
                Math.min(
                    filtrationTime,
                    10
                )
            );


        for (
            let i = 0;
            i <= steps;
            i++
        ) {

            const percentage =
                i / steps;

            labels.push(
                Math.round(
                    percentage *
                    filtrationTime
                )
            );

            retainedValues.push(
                retainedMass *
                percentage
            );
        }


        const canvas =
            document.getElementById(
                "filtrationChart"
            );


        if (filtrationChart) {

            filtrationChart.destroy();
        }


        filtrationChart =
            new Chart(
                canvas,
                {
                    type: "line",

                    data: {

                        labels: labels,

                        datasets: [
                            {
                                label:
                                    "Accumulated Retained Solids (g)",

                                data:
                                    retainedValues,

                                tension: 0.25,

                                borderWidth: 2,

                                pointRadius: 4
                            }
                        ]
                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        plugins: {

                            legend: {
                                display: true
                            }
                        },

                        scales: {

                            x: {
                                title: {
                                    display: true,
                                    text: "Time (seconds)"
                                }
                            },

                            y: {
                                beginAtZero: true,

                                title: {
                                    display: true,
                                    text: "Retained Solids (g)"
                                }
                            }
                        }
                    }
                }
            );
    }


    /*
    ============================================================
    SAVE RESULT
    ============================================================
    */

    saveBtn.addEventListener(
        "click",
        async function () {

            if (!simulationCompleted) {

                showStatus(
                    "Complete the simulation before saving.",
                    "warning"
                );

                return;
            }


            saveBtn.disabled = true;


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

            const filtrateMass =
                initialMass -
                retainedMass;

            const retentionPercentage =
                (
                    retainedMass /
                    initialMass
                ) * 100;


            const payload = {

                simulation_type:
                    "Filtration and Separation",

                input_data: {

                    initial_mass:
                        initialMass,

                    retained_mass:
                        retainedMass,

                    filtration_time:
                        filtrationTime
                },

                result_data: {

                    filtrate_mass:
                        filtrateMass,

                    retained_mass:
                        retainedMass,

                    retention_percentage:
                        retentionPercentage,

                    filtration_time:
                        filtrationTime
                }
            };


            try {

                const response =
                    await fetch(
                        "../practical/save_simulation5_result.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(
                                    payload
                                )
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    savedMessage.style.display =
                        "block";

                    saveBtn.innerHTML =
                        '<i class="bi bi-check-circle me-1"></i>' +
                        'Saved Successfully';

                    saveBtn.disabled = true;

                } else {

                    saveBtn.disabled = false;

                    showStatus(
                        data.message ||
                        "Could not save simulation result.",
                        "warning"
                    );
                }

            } catch (error) {

                saveBtn.disabled = false;

                showStatus(
                    "Could not connect to the server.",
                    "warning"
                );

                console.error(
                    error
                );
            }
        }
    );


    /*
    ============================================================
    RESET
    ============================================================
    */

    resetBtn.addEventListener(
        "click",
        function () {

            location.reload();

        }
    );


    /*
    ============================================================
    STATUS HELPER
    ============================================================
    */

    function showStatus(
        message,
        type
    ) {

        statusBox.className =
            "status-box";

        if (type) {

            statusBox.classList.add(
                type
            );
        }

        statusBox.innerHTML =
            message;
    }

});