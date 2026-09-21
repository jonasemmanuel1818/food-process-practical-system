document.addEventListener("DOMContentLoaded", function () {

    const rpmInput = document.getElementById("rpm");
    const radiusInput = document.getElementById("radius");
    const timeInput = document.getElementById("centrifugeTime");

    const rcfResult = document.getElementById("rcfResult");
    const separationResult = document.getElementById("separationResult");

    const centrifugeInterpretation =
        document.getElementById("centrifugeInterpretation");

    const rpmTable = document.getElementById("rpmTable");
    const radiusTable = document.getElementById("radiusTable");
    const timeTable = document.getElementById("timeTable");
    const rcfTable = document.getElementById("rcfTable");
    const separationTable =
        document.getElementById("separationTable");

    const sampleMassInput =
        document.getElementById("sampleMass");

    const sieveMassInputs =
        document.querySelectorAll(".sieve-mass");

    const totalRetained =
        document.getElementById("totalRetained");

    const massDifference =
        document.getElementById("massDifference");

    const massBalance =
        document.getElementById("massBalance");

    const sieveTableBody =
        document.getElementById("sieveTableBody");

    const simulationStatus =
        document.getElementById("simulationStatus");

    const simulationConclusion =
        document.getElementById("simulationConclusion");

    const runSimulation =
        document.getElementById("runSimulation");

    const resetSimulation =
        document.getElementById("resetSimulation");

    const saveResults =
        document.getElementById("saveResults");

    const printResults =
        document.getElementById("printResults");

    const saveResultsBottom =
        document.getElementById("saveResultsBottom");

    const printResultsBottom =
        document.getElementById("printResultsBottom");

    let centrifugeChart = null;
    let sieveChart = null;


    /* =========================================================
       Calculate RCF
    ========================================================= */

    function calculateRCF() {

        const rpm =
            parseFloat(rpmInput.value) || 0;

        const radius =
            parseFloat(radiusInput.value) || 0;

        return 1.118e-5 *
            radius *
            Math.pow(rpm, 2);
    }


    /* =========================================================
       Calculate Separation Index
    ========================================================= */

    function calculateSeparationIndex(rcf, time) {

        if (rcf <= 0 || time <= 0) {
            return 0;
        }

        let index =
            (rcf / (rcf + 1000)) *
            (time / (time + 10)) *
            100;

        if (index > 100) {
            index = 100;
        }

        return index;
    }


    /* =========================================================
       Centrifugation Interpretation
    ========================================================= */

    function getInterpretation(index) {

        if (index < 20) {

            return "Low separation tendency. Increasing the centrifugal force or processing time may improve separation.";

        } else if (index < 50) {

            return "Moderate separation tendency. The selected conditions may provide noticeable separation.";

        } else if (index < 80) {

            return "Good separation tendency. The selected conditions provide relatively strong separation.";

        } else {

            return "High separation tendency. The selected conditions provide strong centrifugal separation.";

        }
    }


    /* =========================================================
       Update Centrifugation
    ========================================================= */

    function updateCentrifugation() {

        const rpm =
            parseFloat(rpmInput.value) || 0;

        const radius =
            parseFloat(radiusInput.value) || 0;

        const time =
            parseFloat(timeInput.value) || 0;

        const rcf =
            calculateRCF();

        const separationIndex =
            calculateSeparationIndex(
                rcf,
                time
            );


        rcfResult.textContent =
            rcf.toFixed(2);

        separationResult.textContent =
            separationIndex.toFixed(2) + "%";


        rpmTable.textContent =
            rpm.toFixed(0);

        radiusTable.textContent =
            radius.toFixed(2);

        timeTable.textContent =
            time.toFixed(0);

        rcfTable.textContent =
            rcf.toFixed(2);

        separationTable.textContent =
            separationIndex.toFixed(2) + "%";


        centrifugeInterpretation.textContent =
            getInterpretation(
                separationIndex
            );


        updateCentrifugeChart(
            rpm,
            rcf
        );


        return {

            rpm: rpm,

            radius: radius,

            time: time,

            rcf: rcf,

            separationIndex:
                separationIndex

        };
    }


    /* =========================================================
       Centrifugation Chart
    ========================================================= */

    function updateCentrifugeChart(rpm, rcf) {

        const ctx =
            document.getElementById(
                "centrifugeChart"
            );

        if (!ctx) {
            return;
        }


        if (centrifugeChart) {

            centrifugeChart.destroy();

        }


        const rpmValues = [];
        const rcfValues = [];

        const steps = 6;


        for (
            let i = 1;
            i <= steps;
            i++
        ) {

            const value =
                (rpm / steps) * i;


            rpmValues.push(
                Math.round(value)
            );


            rcfValues.push(

                1.118e-5 *

                (parseFloat(
                    radiusInput.value
                ) || 0) *

                Math.pow(
                    value,
                    2
                )

            );

        }


        centrifugeChart =
            new Chart(
                ctx,
                {

                    type: "line",

                    data: {

                        labels:
                            rpmValues,

                        datasets: [

                            {

                                label: "RCF",

                                data:
                                    rcfValues,

                                tension: 0.3,

                                fill: false

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        scales: {

                            x: {

                                title: {

                                    display: true,

                                    text: "RPM"

                                }

                            },

                            y: {

                                title: {

                                    display: true,

                                    text: "Relative Centrifugal Force"

                                },

                                beginAtZero: true

                            }

                        }

                    }

                }
            );
    }


    /* =========================================================
       Sieve Analysis
    ========================================================= */

    function calculateSieveAnalysis() {

        const sampleMass =
            parseFloat(
                sampleMassInput.value
            ) || 0;

        let total = 0;

        const results = [];


        sieveMassInputs.forEach(
            function (input) {

                const sieve =
                    input.dataset.sieve;

                const mass =
                    parseFloat(
                        input.value
                    ) || 0;


                total += mass;


                results.push({

                    sieve: sieve,

                    mass: mass,

                    percentageRetained: 0,

                    cumulativeRetained: 0,

                    percentagePassing: 0

                });

            }
        );


        let cumulative = 0;


        results.forEach(
            function (item) {

                if (sampleMass > 0) {

                    item.percentageRetained =
                        (
                            item.mass /
                            sampleMass
                        ) * 100;

                } else {

                    item.percentageRetained =
                        0;

                }


                cumulative +=
                    item.percentageRetained;


                item.cumulativeRetained =
                    cumulative;


                item.percentagePassing =
                    Math.max(
                        0,
                        100 - cumulative
                    );

            }
        );


        const difference =
            sampleMass - total;


        let balance = 0;


        if (sampleMass > 0) {

            balance =
                (
                    total /
                    sampleMass
                ) * 100;

        }


        totalRetained.textContent =
            total.toFixed(2) + " g";

        massDifference.textContent =
            difference.toFixed(2) + " g";

        massBalance.textContent =
            balance.toFixed(2) + "%";


        updateSieveTable(
            results
        );

        updateSieveChart(
            results
        );


        return {

            sampleMass:
                sampleMass,

            totalRetained:
                total,

            massDifference:
                difference,

            massBalance:
                balance,

            results:
                results

        };
    }


    /* =========================================================
       Update Sieve Table
    ========================================================= */

    function updateSieveTable(results) {

        sieveTableBody.innerHTML = "";


        results.forEach(
            function (item) {

                const row =
                    document.createElement(
                        "tr"
                    );


                row.innerHTML = `

                    <td>
                        ${item.sieve}
                    </td>

                    <td>
                        ${item.mass.toFixed(2)}
                    </td>

                    <td>
                        ${item.percentageRetained.toFixed(2)}%
                    </td>

                    <td>
                        ${item.cumulativeRetained.toFixed(2)}%
                    </td>

                    <td>
                        ${item.percentagePassing.toFixed(2)}%
                    </td>

                `;


                sieveTableBody.appendChild(
                    row
                );

            }
        );
    }


    /* =========================================================
       Sieve Chart
    ========================================================= */

    function updateSieveChart(results) {

        const ctx =
            document.getElementById(
                "sieveChart"
            );


        if (!ctx) {
            return;
        }


        if (sieveChart) {

            sieveChart.destroy();

        }


        const labels =
            results.map(
                function (item) {

                    return item.sieve;

                }
            );


        const percentages =
            results.map(
                function (item) {

                    return item.percentageRetained;

                }
            );


        sieveChart =
            new Chart(
                ctx,
                {

                    type: "bar",

                    data: {

                        labels:
                            labels,

                        datasets: [

                            {

                                label:
                                    "% Retained",

                                data:
                                    percentages

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                title: {

                                    display: true,

                                    text:
                                        "Percentage Retained (%)"

                                }

                            },

                            x: {

                                title: {

                                    display: true,

                                    text:
                                        "Sieve"

                                }

                            }

                        }

                    }

                }
            );
    }


    /* =========================================================
       Run Simulation
    ========================================================= */

    function runFullSimulation() {

        const centrifugation =
            updateCentrifugation();

        const sieve =
            calculateSieveAnalysis();


        simulationStatus.textContent =
            "Simulation completed successfully.";


        simulationConclusion.innerHTML = `

            <strong>
                Conclusion:
            </strong>

            The simulation produced a relative centrifugal force of

            <strong>
                ${centrifugation.rcf.toFixed(2)}
            </strong>

            with a separation index of

            <strong>
                ${centrifugation.separationIndex.toFixed(2)}%
            </strong>.

            The sieve analysis produced a total retained mass of

            <strong>
                ${sieve.totalRetained.toFixed(2)} g
            </strong>

            from a sample mass of

            <strong>
                ${sieve.sampleMass.toFixed(2)} g
            </strong>.

            The calculated percentage retained,
            cumulative percentage retained and
            percentage passing values are shown
            in the results table.

        `;
    }


    /* =========================================================
       Reset Simulation
    ========================================================= */

    function resetAll() {

        rpmInput.value = 3000;

        radiusInput.value = 10;

        timeInput.value = 10;

        sampleMassInput.value = 100;


        const defaultMasses = [

            10,

            20,

            25,

            20,

            25

        ];


        sieveMassInputs.forEach(
            function (input, index) {

                input.value =
                    defaultMasses[index];

            }
        );


        rcfResult.textContent =
            "0";

        separationResult.textContent =
            "0%";


        rpmTable.textContent =
            "-";

        radiusTable.textContent =
            "-";

        timeTable.textContent =
            "-";

        rcfTable.textContent =
            "-";

        separationTable.textContent =
            "-";


        totalRetained.textContent =
            "0 g";

        massDifference.textContent =
            "0 g";

        massBalance.textContent =
            "0%";


        sieveTableBody.innerHTML =
            "";


        centrifugeInterpretation.textContent =
            "Adjust the operating conditions and run the simulation.";


        simulationStatus.textContent =
            "Ready to run.";


        simulationConclusion.textContent =
            "Run the simulation to generate the conclusion.";


        if (centrifugeChart) {

            centrifugeChart.destroy();

            centrifugeChart = null;

        }


        if (sieveChart) {

            sieveChart.destroy();

            sieveChart = null;

        }

    }


    /* =========================================================
       SAVE RESULTS TO MYSQL
    ========================================================= */

    async function saveSimulationResults() {

        const centrifugation =
            updateCentrifugation();

        const sieve =
            calculateSieveAnalysis();


        const results = {

            practical:
                "Practical 2 - Physical Separation",

            date:
                new Date().toLocaleString(),


            centrifugation: {

                rpm:
                    centrifugation.rpm,

                radius:
                    centrifugation.radius,

                time:
                    centrifugation.time,

                rcf:
                    centrifugation.rcf,

                separationIndex:
                    centrifugation.separationIndex

            },


            sieveAnalysis: {

                sampleMass:
                    sieve.sampleMass,

                totalRetained:
                    sieve.totalRetained,

                massDifference:
                    sieve.massDifference,

                massBalance:
                    sieve.massBalance,

                results:
                    sieve.results

            }

        };


        /*
         * Keep browser backup
         */

        localStorage.setItem(

            "practical2_simulation_results",

            JSON.stringify(results)

        );


        /*
         * Send results to PHP/MySQL
         */

        const formData =
            new FormData();


        formData.append(

            "input_data",

            JSON.stringify({

                rpm:
                    centrifugation.rpm,

                radius:
                    centrifugation.radius,

                time:
                    centrifugation.time,

                sampleMass:
                    sieve.sampleMass,

                sieveMasses:

                    sieve.results.map(
                        function (item) {

                            return {

                                sieve:
                                    item.sieve,

                                mass:
                                    item.mass

                            };

                        }
                    )

            })

        );


        /*
         * Store calculated results AND
         * chart data.
         *
         * This chartData section is the
         * important fix for Admin ->
         * Simulation Results.
         */

        formData.append(

            "result_data",

            JSON.stringify({

                rcf:
                    centrifugation.rcf,

                separationIndex:
                    centrifugation.separationIndex,

                totalRetained:
                    sieve.totalRetained,

                massDifference:
                    sieve.massDifference,

                massBalance:
                    sieve.massBalance,

                sieveResults:
                    sieve.results,


                /*
                 * =================================================
                 * CHART DATA
                 * =================================================
                 */

                chartData: {

                    /*
                     * ---------------------------------------------
                     * Centrifugation Chart
                     * ---------------------------------------------
                     */

                    centrifugation: {

                        labels:

                            centrifugeChart

                                ? centrifugeChart.data.labels

                                : [],


                        values:

                            centrifugeChart

                                ? centrifugeChart
                                    .data
                                    .datasets[0]
                                    .data

                                : []

                    },


                    /*
                     * ---------------------------------------------
                     * Sieve Analysis Chart
                     * ---------------------------------------------
                     */

                    sieve: {

                        labels:

                            sieve.results.map(
                                function (item) {

                                    return item.sieve;

                                }
                            ),


                        values:

                            sieve.results.map(
                                function (item) {

                                    return item
                                        .percentageRetained;

                                }
                            )

                    }

                }

            })

        );


        try {

            simulationStatus.textContent =
                "Saving results...";


            const response =
                await fetch(

                    "../practical/save_simulation2_result.php",

                    {

                        method: "POST",

                        body: formData

                    }

                );


            const data =
                await response.json();


            if (data.success) {

                simulationStatus.textContent =
                    "Simulation results saved successfully to the database.";


                simulationConclusion.classList.remove(
                    "alert-secondary"
                );


                simulationConclusion.classList.add(
                    "alert-success"
                );


            } else {

                simulationStatus.textContent =

                    data.message ||

                    "Could not save simulation results.";

            }


        } catch (error) {

            console.error(

                "Save error:",

                error

            );


            simulationStatus.textContent =
                "Unable to connect to the database.";

        }

    }


    /* =========================================================
       Print Results
    ========================================================= */

    function printSimulationResults() {

        window.print();

    }


    /* =========================================================
       Event Listeners
    ========================================================= */

    if (runSimulation) {

        runSimulation.addEventListener(

            "click",

            runFullSimulation

        );

    }


    if (resetSimulation) {

        resetSimulation.addEventListener(

            "click",

            resetAll

        );

    }


    if (saveResults) {

        saveResults.addEventListener(

            "click",

            saveSimulationResults

        );

    }


    if (printResults) {

        printResults.addEventListener(

            "click",

            printSimulationResults

        );

    }


    /*
     * These are optional bottom buttons.
     * This prevents errors if they don't exist.
     */

    if (saveResultsBottom) {

        saveResultsBottom.addEventListener(

            "click",

            saveSimulationResults

        );

    }


    if (printResultsBottom) {

        printResultsBottom.addEventListener(

            "click",

            printSimulationResults

        );

    }


    /* =========================================================
       Initial State
    ========================================================= */

    updateCentrifugation();

    calculateSieveAnalysis();

});