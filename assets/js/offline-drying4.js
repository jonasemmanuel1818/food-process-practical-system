// assets/js/offline-drying4.js

(function () {
    "use strict";

    const SPRAY_STORAGE_KEY =
        "food_process_offline_practical_4_spray_simulation";

    const FREEZE_STORAGE_KEY =
        "food_process_offline_practical_4_freeze_simulation";

    let sprayChart = null;
    let freezeChart = null;

    function getNumber(id) {
        const element = document.getElementById(id);

        if (!element) {
            return NaN;
        }

        return parseFloat(element.value);
    }

    function showResult(elementId, html, isError = false) {
        const element =
            document.getElementById(elementId);

        if (!element) {
            return;
        }

        element.innerHTML = html;
        element.style.display = "block";

        if (isError) {
            element.classList.add("error");
        } else {
            element.classList.remove("error");
        }
    }

    function clearResult(elementId) {
        const element =
            document.getElementById(elementId);

        if (!element) {
            return;
        }

        element.innerHTML = "";
        element.style.display = "none";
        element.classList.remove("error");
    }

    function destroyChart(chart) {
        if (chart && typeof chart.destroy === "function") {
            chart.destroy();
        }

        return null;
    }

    /*
     * Draw a simple offline chart without requiring Chart.js.
     * This keeps Practical 4 usable even when completely offline.
     */
    function drawOfflineChart(canvasId, labels, values, title) {
        const canvas =
            document.getElementById(canvasId);

        if (!canvas) {
            return null;
        }

        const ctx = canvas.getContext("2d");

        if (!ctx) {
            return null;
        }

        const width = canvas.clientWidth || 700;
        const height = 300;

        const ratio =
            window.devicePixelRatio || 1;

        canvas.width = width * ratio;
        canvas.height = height * ratio;

        ctx.setTransform(
            ratio,
            0,
            0,
            ratio,
            0,
            0
        );

        ctx.clearRect(
            0,
            0,
            width,
            height
        );

        const paddingLeft = 55;
        const paddingRight = 20;
        const paddingTop = 40;
        const paddingBottom = 45;

        const chartWidth =
            width -
            paddingLeft -
            paddingRight;

        const chartHeight =
            height -
            paddingTop -
            paddingBottom;

        let maxValue =
            Math.max.apply(null, values);

        let minValue =
            Math.min.apply(null, values);

        if (!isFinite(maxValue)) {
            maxValue = 100;
        }

        if (!isFinite(minValue)) {
            minValue = 0;
        }

        if (maxValue === minValue) {
            maxValue += 10;
            minValue -= 10;
        }

        const range =
            maxValue - minValue;

        const yPadding =
            range * 0.1;

        maxValue += yPadding;
        minValue -= yPadding;

        if (minValue < 0) {
            minValue = 0;
        }

        /*
         * Title
         */
        ctx.fillStyle = "#17495a";
        ctx.font =
            "600 14px Arial";

        ctx.textAlign = "left";

        ctx.fillText(
            title,
            paddingLeft,
            20
        );

        /*
         * Grid lines and Y labels
         */
        ctx.font =
            "11px Arial";

        ctx.textAlign = "right";

        const gridLines = 5;

        for (let i = 0; i <= gridLines; i++) {
            const ratioY =
                i / gridLines;

            const y =
                paddingTop +
                chartHeight * ratioY;

            const value =
                maxValue -
                (maxValue - minValue) *
                ratioY;

            ctx.strokeStyle = "#e4e8ea";
            ctx.lineWidth = 1;

            ctx.beginPath();
            ctx.moveTo(
                paddingLeft,
                y
            );
            ctx.lineTo(
                width - paddingRight,
                y
            );
            ctx.stroke();

            ctx.fillStyle = "#68767d";

            ctx.fillText(
                value.toFixed(0),
                paddingLeft - 8,
                y + 4
            );
        }

        /*
         * Axes
         */
        ctx.strokeStyle = "#aeb9be";
        ctx.lineWidth = 1;

        ctx.beginPath();

        ctx.moveTo(
            paddingLeft,
            paddingTop
        );

        ctx.lineTo(
            paddingLeft,
            paddingTop + chartHeight
        );

        ctx.lineTo(
            width - paddingRight,
            paddingTop + chartHeight
        );

        ctx.stroke();

        /*
         * X labels
         */
        ctx.fillStyle = "#68767d";
        ctx.font =
            "10px Arial";

        ctx.textAlign = "center";

        const labelStep =
            Math.max(
                1,
                Math.ceil(labels.length / 8)
            );

        labels.forEach(function (label, index) {

            if (
                index % labelStep !== 0 &&
                index !== labels.length - 1
            ) {
                return;
            }

            const x =
                paddingLeft +
                (
                    index /
                    Math.max(
                        1,
                        labels.length - 1
                    )
                ) *
                chartWidth;

            ctx.fillText(
                String(label),
                x,
                height - 17
            );
        });

        /*
         * Line
         */
        ctx.strokeStyle = "#1f5f75";
        ctx.lineWidth = 2.5;

        ctx.beginPath();

        values.forEach(function (value, index) {

            const x =
                paddingLeft +
                (
                    index /
                    Math.max(
                        1,
                        values.length - 1
                    )
                ) *
                chartWidth;

            const y =
                paddingTop +
                (
                    (maxValue - value) /
                    (maxValue - minValue)
                ) *
                chartHeight;

            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });

        ctx.stroke();

        /*
         * Data points
         */
        ctx.fillStyle = "#17495a";

        values.forEach(function (value, index) {

            const x =
                paddingLeft +
                (
                    index /
                    Math.max(
                        1,
                        values.length - 1
                    )
                ) *
                chartWidth;

            const y =
                paddingTop +
                (
                    (maxValue - value) /
                    (maxValue - minValue)
                ) *
                chartHeight;

            ctx.beginPath();

            ctx.arc(
                x,
                y,
                2.5,
                0,
                Math.PI * 2
            );

            ctx.fill();
        });

        return {
            canvas: canvas,
            labels: labels,
            values: values
        };
    }

    /*
     * SPRAY DRYING
     *
     * Based on the same simulation behaviour as the
     * Practical 4 online simulation.
     */
    function calculateSpraySimulation(
        inletTemperature,
        feedFlow,
        initialMoisture,
        finalMoisture
    ) {
        const rate =
            0.07 *
            Math.max(
                0.6,
                Math.min(
                    1.6,
                    inletTemperature / 200
                )
            ) *
            Math.max(
                0.65,
                Math.min(
                    1.5,
                    10 / feedFlow
                )
            );

        const time = [];
        const moisture = [];

        for (let n = 0; n <= 24; n++) {

            time.push(n);

            let value =
                finalMoisture +
                (
                    initialMoisture -
                    finalMoisture
                ) *
                Math.exp(
                    -rate * n
                );

            if (n === 24) {
                value = finalMoisture;
            }

            moisture.push(
                Number(value.toFixed(4))
            );
        }

        return {
            simulation_type: "Spray Drying",

            input_data: {
                inlet_temperature_c:
                    inletTemperature,

                feed_flow_l_h:
                    feedFlow,

                initial_moisture_percent:
                    initialMoisture,

                final_moisture_percent:
                    finalMoisture
            },

            result_data: {
                simulated_final_moisture_percent:
                    finalMoisture,

                drying_curve_time_min:
                    time,

                drying_curve_moisture_percent:
                    moisture
            }
        };
    }

    function runSpraySimulation() {

        const inletTemperature =
            getNumber(
                "offlineSprayInletTemp"
            );

        const feedFlow =
            getNumber(
                "offlineSprayFeedFlow"
            );

        const initialMoisture =
            getNumber(
                "offlineSprayInitialMoisture"
            );

        const finalMoisture =
            getNumber(
                "offlineSprayFinalMoisture"
            );

        if (
            !isFinite(inletTemperature) ||
            inletTemperature < 100 ||
            inletTemperature > 300
        ) {
            showResult(
                "offlineSprayResults",
                "Enter an inlet temperature between 100°C and 300°C.",
                true
            );

            return;
        }

        if (
            !isFinite(feedFlow) ||
            feedFlow <= 0
        ) {
            showResult(
                "offlineSprayResults",
                "Feed flow rate must be greater than 0 L/h.",
                true
            );

            return;
        }

        if (
            !isFinite(initialMoisture) ||
            initialMoisture <= 0 ||
            initialMoisture >= 100
        ) {
            showResult(
                "offlineSprayResults",
                "Initial moisture must be between 0% and 100%.",
                true
            );

            return;
        }

        if (
            !isFinite(finalMoisture) ||
            finalMoisture <= 0 ||
            finalMoisture >= initialMoisture
        ) {
            showResult(
                "offlineSprayResults",
                "Final moisture must be greater than 0% and lower than initial moisture.",
                true
            );

            return;
        }

        const simulation =
            calculateSpraySimulation(
                inletTemperature,
                feedFlow,
                initialMoisture,
                finalMoisture
            );

        localStorage.setItem(
            SPRAY_STORAGE_KEY,
            JSON.stringify({
                ...simulation,
                saved_at:
                    new Date().toISOString()
            })
        );

        sprayChart =
            drawOfflineChart(
                "offlineSprayChart",
                simulation.result_data.drying_curve_time_min,
                simulation.result_data.drying_curve_moisture_percent,
                "Spray Drying Moisture Curve"
            );

        const reduction =
            initialMoisture -
            finalMoisture;

        showResult(
            "offlineSprayResults",
            `
                <strong>Spray Drying Simulation Result</strong>

                <div class="metric-grid">

                    <div class="metric">
                        <small>Initial Moisture</small>
                        <strong>${initialMoisture.toFixed(2)}%</strong>
                    </div>

                    <div class="metric">
                        <small>Final Moisture</small>
                        <strong>${finalMoisture.toFixed(2)}%</strong>
                    </div>

                    <div class="metric">
                        <small>Moisture Removed</small>
                        <strong>${reduction.toFixed(2)}%</strong>
                    </div>

                </div>

                <p style="margin-top:12px;margin-bottom:0;">
                    The simulated moisture decreases from
                    ${initialMoisture.toFixed(2)}% to
                    ${finalMoisture.toFixed(2)}%
                    over the simulation period.
                </p>
            `
        );

        window.dispatchEvent(
            new CustomEvent(
                "offlineP4SpraySimulationSaved",
                {
                    detail: simulation
                }
            )
        );
    }

    function clearSpraySimulation() {

        const confirmed =
            window.confirm(
                "Clear the saved Spray Drying simulation?"
            );

        if (!confirmed) {
            return;
        }

        localStorage.removeItem(
            SPRAY_STORAGE_KEY
        );

        sprayChart = null;

        const canvas =
            document.getElementById(
                "offlineSprayChart"
            );

        if (canvas) {
            const ctx =
                canvas.getContext("2d");

            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );
        }

        clearResult(
            "offlineSprayResults"
        );
    }

    /*
     * FREEZE DRYING
     *
     * Uses the same model as the online Practical 4
     * freeze-drying simulation.
     */
    function calculateFreezeSimulation(
        thickness,
        plateTemperature,
        pressure,
        initialMoisture
    ) {
        const finalMoisture = 5;

        const index =
            Math.max(
                0.6,
                thickness / 10
            ) *
            Math.max(
                0.65,
                30 / plateTemperature
            ) *
            Math.max(
                0.65,
                Math.sqrt(
                    pressure / 0.5
                )
            );

        const rate =
            0.085 /
            Math.max(
                0.65,
                index
            );

        const time = [];
        const moisture = [];

        for (let n = 0; n <= 29; n++) {

            const minutes =
                n * 10;

            time.push(minutes);

            let value =
                finalMoisture +
                (
                    initialMoisture -
                    finalMoisture
                ) *
                Math.exp(
                    -rate * n
                );

            if (n === 29) {
                value = finalMoisture;
            }

            moisture.push(
                Number(value.toFixed(4))
            );
        }

        const estimatedTime =
            Math.max(
                60,
                Math.round(
                    300 * index
                )
            );

        return {
            simulation_type: "Freeze Drying",

            input_data: {
                product_thickness_mm:
                    thickness,

                plate_temperature_c:
                    plateTemperature,

                chamber_pressure_mbar:
                    pressure,

                initial_moisture_percent:
                    initialMoisture,

                final_moisture_percent:
                    finalMoisture
            },

            result_data: {
                estimated_drying_time_min:
                    estimatedTime,

                final_moisture_percent:
                    finalMoisture,

                drying_curve_time_min:
                    time,

                drying_curve_moisture_percent:
                    moisture
            }
        };
    }

    function runFreezeSimulation() {

        const thickness =
            getNumber(
                "offlineFreezeThickness"
            );

        const plateTemperature =
            getNumber(
                "offlineFreezePlateTemp"
            );

        const pressure =
            getNumber(
                "offlineFreezePressure"
            );

        const initialMoisture =
            getNumber(
                "offlineFreezeMoisture"
            );

        if (
            !isFinite(thickness) ||
            thickness <= 0
        ) {
            showResult(
                "offlineFreezeResults",
                "Product thickness must be greater than 0 mm.",
                true
            );

            return;
        }

        if (
            !isFinite(plateTemperature) ||
            plateTemperature < 20 ||
            plateTemperature > 40
        ) {
            showResult(
                "offlineFreezeResults",
                "Heating-plate temperature must be between 20°C and 40°C.",
                true
            );

            return;
        }

        if (
            !isFinite(pressure) ||
            pressure < 0.01 ||
            pressure > 10
        ) {
            showResult(
                "offlineFreezeResults",
                "Chamber pressure must be between 0.01 and 10 mbar.",
                true
            );

            return;
        }

        if (
            !isFinite(initialMoisture) ||
            initialMoisture < 6 ||
            initialMoisture > 99
        ) {
            showResult(
                "offlineFreezeResults",
                "Initial moisture must be between 6% and 99%.",
                true
            );

            return;
        }

        const simulation =
            calculateFreezeSimulation(
                thickness,
                plateTemperature,
                pressure,
                initialMoisture
            );

        localStorage.setItem(
            FREEZE_STORAGE_KEY,
            JSON.stringify({
                ...simulation,
                saved_at:
                    new Date().toISOString()
            })
        );

        freezeChart =
            drawOfflineChart(
                "offlineFreezeChart",
                simulation.result_data.drying_curve_time_min,
                simulation.result_data.drying_curve_moisture_percent,
                "Freeze Drying Moisture Curve"
            );

        const estimatedTime =
            simulation.result_data
                .estimated_drying_time_min;

        showResult(
            "offlineFreezeResults",
            `
                <strong>Freeze Drying Simulation Result</strong>

                <div class="metric-grid">

                    <div class="metric">
                        <small>Estimated Drying Time</small>
                        <strong>${estimatedTime} min</strong>
                    </div>

                    <div class="metric">
                        <small>Initial Moisture</small>
                        <strong>${initialMoisture.toFixed(2)}%</strong>
                    </div>

                    <div class="metric">
                        <small>Final Moisture</small>
                        <strong>5.00%</strong>
                    </div>

                </div>

                <p style="margin-top:12px;margin-bottom:0;">
                    The estimated drying time is affected by
                    product thickness, heating-plate temperature
                    and chamber pressure.
                </p>
            `
        );

        window.dispatchEvent(
            new CustomEvent(
                "offlineP4FreezeSimulationSaved",
                {
                    detail: simulation
                }
            )
        );
    }

    function clearFreezeSimulation() {

        const confirmed =
            window.confirm(
                "Clear the saved Freeze Drying simulation?"
            );

        if (!confirmed) {
            return;
        }

        localStorage.removeItem(
            FREEZE_STORAGE_KEY
        );

        freezeChart = null;

        const canvas =
            document.getElementById(
                "offlineFreezeChart"
            );

        if (canvas) {
            const ctx =
                canvas.getContext("2d");

            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );
        }

        clearResult(
            "offlineFreezeResults"
        );
    }

    /*
     * Restore the last saved spray simulation.
     */
    function restoreSpraySimulation() {

        try {
            const saved =
                localStorage.getItem(
                    SPRAY_STORAGE_KEY
                );

            if (!saved) {
                return;
            }

            const simulation =
                JSON.parse(saved);

            if (
                !simulation.input_data ||
                !simulation.result_data
            ) {
                return;
            }

            const input =
                simulation.input_data;

            const result =
                simulation.result_data;

            const inlet =
                document.getElementById(
                    "offlineSprayInletTemp"
                );

            const flow =
                document.getElementById(
                    "offlineSprayFeedFlow"
                );

            const initial =
                document.getElementById(
                    "offlineSprayInitialMoisture"
                );

            const final =
                document.getElementById(
                    "offlineSprayFinalMoisture"
                );

            if (inlet) {
                inlet.value =
                    input.inlet_temperature_c;
            }

            if (flow) {
                flow.value =
                    input.feed_flow_l_h;
            }

            if (initial) {
                initial.value =
                    input.initial_moisture_percent;
            }

            if (final) {
                final.value =
                    input.final_moisture_percent;
            }

            sprayChart =
                drawOfflineChart(
                    "offlineSprayChart",
                    result.drying_curve_time_min,
                    result.drying_curve_moisture_percent,
                    "Spray Drying Moisture Curve"
                );

            showResult(
                "offlineSprayResults",
                `
                    <strong>Saved Spray Drying Simulation</strong>

                    <p style="margin-bottom:0;margin-top:8px;">
                        A previous simulation has been restored
                        from this device.
                    </p>
                `
            );

        } catch (error) {
            console.error(
                "Unable to restore Spray Drying simulation:",
                error
            );
        }
    }

    /*
     * Restore the last saved freeze simulation.
     */
    function restoreFreezeSimulation() {

        try {
            const saved =
                localStorage.getItem(
                    FREEZE_STORAGE_KEY
                );

            if (!saved) {
                return;
            }

            const simulation =
                JSON.parse(saved);

            if (
                !simulation.input_data ||
                !simulation.result_data
            ) {
                return;
            }

            const input =
                simulation.input_data;

            const result =
                simulation.result_data;

            const thickness =
                document.getElementById(
                    "offlineFreezeThickness"
                );

            const plate =
                document.getElementById(
                    "offlineFreezePlateTemp"
                );

            const pressure =
                document.getElementById(
                    "offlineFreezePressure"
                );

            const moisture =
                document.getElementById(
                    "offlineFreezeMoisture"
                );

            if (thickness) {
                thickness.value =
                    input.product_thickness_mm;
            }

            if (plate) {
                plate.value =
                    input.plate_temperature_c;
            }

            if (pressure) {
                pressure.value =
                    input.chamber_pressure_mbar;
            }

            if (moisture) {
                moisture.value =
                    input.initial_moisture_percent;
            }

            freezeChart =
                drawOfflineChart(
                    "offlineFreezeChart",
                    result.drying_curve_time_min,
                    result.drying_curve_moisture_percent,
                    "Freeze Drying Moisture Curve"
                );

            showResult(
                "offlineFreezeResults",
                `
                    <strong>Saved Freeze Drying Simulation</strong>

                    <p style="margin-bottom:0;margin-top:8px;">
                        A previous simulation has been restored
                        from this device.
                    </p>
                `
            );

        } catch (error) {
            console.error(
                "Unable to restore Freeze Drying simulation:",
                error
            );
        }
    }

    const sprayRunButton =
        document.getElementById(
            "runOfflineSpray"
        );

    const sprayClearButton =
        document.getElementById(
            "clearOfflineSpray"
        );

    const freezeRunButton =
        document.getElementById(
            "runOfflineFreeze"
        );

    const freezeClearButton =
        document.getElementById(
            "clearOfflineFreeze"
        );

    if (sprayRunButton) {
        sprayRunButton.addEventListener(
            "click",
            runSpraySimulation
        );
    }

    if (sprayClearButton) {
        sprayClearButton.addEventListener(
            "click",
            clearSpraySimulation
        );
    }

    if (freezeRunButton) {
        freezeRunButton.addEventListener(
            "click",
            runFreezeSimulation
        );
    }

    if (freezeClearButton) {
        freezeClearButton.addEventListener(
            "click",
            clearFreezeSimulation
        );
    }

    /*
     * Redraw charts when the browser window changes size.
     */
    window.addEventListener(
        "resize",
        function () {

            try {
                const spraySaved =
                    localStorage.getItem(
                        SPRAY_STORAGE_KEY
                    );

                if (spraySaved) {
                    const simulation =
                        JSON.parse(spraySaved);

                    if (
                        simulation.result_data
                    ) {
                        sprayChart =
                            drawOfflineChart(
                                "offlineSprayChart",
                                simulation.result_data
                                    .drying_curve_time_min,
                                simulation.result_data
                                    .drying_curve_moisture_percent,
                                "Spray Drying Moisture Curve"
                            );
                    }
                }

                const freezeSaved =
                    localStorage.getItem(
                        FREEZE_STORAGE_KEY
                    );

                if (freezeSaved) {
                    const simulation =
                        JSON.parse(freezeSaved);

                    if (
                        simulation.result_data
                    ) {
                        freezeChart =
                            drawOfflineChart(
                                "offlineFreezeChart",
                                simulation.result_data
                                    .drying_curve_time_min,
                                simulation.result_data
                                    .drying_curve_moisture_percent,
                                "Freeze Drying Moisture Curve"
                            );
                    }
                }

            } catch (error) {
                console.error(
                    "Unable to redraw Practical 4 charts:",
                    error
                );
            }
        }
    );

    /*
     * Public API used by offline-sync4.js.
     */
    window.offlinePractical4Drying = {

        sprayStorageKey:
            SPRAY_STORAGE_KEY,

        freezeStorageKey:
            FREEZE_STORAGE_KEY,

        getSpraySimulation: function () {

            try {
                const saved =
                    localStorage.getItem(
                        SPRAY_STORAGE_KEY
                    );

                return saved
                    ? JSON.parse(saved)
                    : null;

            } catch (error) {
                console.error(error);
                return null;
            }
        },

        getFreezeSimulation: function () {

            try {
                const saved =
                    localStorage.getItem(
                        FREEZE_STORAGE_KEY
                    );

                return saved
                    ? JSON.parse(saved)
                    : null;

            } catch (error) {
                console.error(error);
                return null;
            }
        },

        runSpraySimulation:
            runSpraySimulation,

        runFreezeSimulation:
            runFreezeSimulation,

        clearSpraySimulation:
            clearSpraySimulation,

        clearFreezeSimulation:
            clearFreezeSimulation
    };

    /*
     * Restore saved simulations when the page loads.
     */
    restoreSpraySimulation();
    restoreFreezeSimulation();

})();