<?php

require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Laboratory Safety and Health Rules</title>

    <link rel="stylesheet"
          href="../assets/style.css">

</head>

<body>

<header class="topbar">

    <div>

        <h2>Food Process Engineering</h2>

        <span>
            Laboratory Resources
        </span>

    </div>

    <div class="user-area">

        <a href="../dashboard.php"
           class="logout-btn">

            Dashboard

        </a>

    </div>

</header>


<main class="content">

    <div class="info-section">

        <h1>
            LABORATORY SAFETY AND HEALTH RULES
        </h1>

        <p>
            This page provides the laboratory safety and
            health rules for students and laboratory users.
            You can return to this page whenever you need
            to review the rules.
        </p>

    </div>


    <div class="info-section">

        <h2>A. General</h2>

        <ol>

            <li>
                Conduct all assignments and requests from
                the lecturer and/or technician promptly
                according to the laboratory rules.
                Choosing shortcuts can be perilous.
            </li>

            <li>
                Open shoes, sandals, shorts, short skirts,
                ties and excessive clothing and long hair
                that is not tied up can be dangerous and
                cause accidents and are thus not allowed.
                Long hair must be tied up.
            </li>

            <li>
                Walking around the building barefoot is
                prohibited.
            </li>

            <li>
                Wearing protective clothing, clean white
                coats, safety glasses and equipment are
                compulsory when prescribed.
            </li>

            <li>
                Smoking, eating or drinking are not allowed
                in laboratories. Never store food in a
                refrigerator in which chemicals are stored.
            </li>

            <li>
                Do not sit or lie on laboratory work surfaces.
            </li>

            <li>
                Work intelligently and independently of
                other students as far as possible.
            </li>

            <li>
                Clean and neat laboratory practices are
                required as far as possible. Leave your
                work area and the rest of the laboratory
                clean and tidy.
            </li>

            <li>
                Bunsen burners must always be placed on
                asbestos slabs when used, to protect the
                work surfaces.
            </li>

            <li>
                Playing or horsing about in the laboratory
                is strictly prohibited.
            </li>

            <li>
                If something is unclear, please ask for
                more information.
            </li>

            <li>
                Handle apparatus in a professional manner
                and if you are not a hundred percent sure
                as to how the apparatus works, ask.
                Use balances carefully, clean after use
                and close the windows.
            </li>

            <li>
                Report all broken apparatus immediately to
                the supervisor or technician.
            </li>

            <li>
                Report any maintenance problems to the
                laboratory in charge or supervisor.
            </li>

            <li>
                Remember to fill out logbooks of apparatus.
            </li>

            <li>
                Do not ever slam or kick doors of incubators,
                ovens, freezers or fridges.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>B. Handling Chemicals</h2>

        <ol>

            <li>
                All chemical solutions MUST be marked with
                the type of chemical, concentration, date
                prepared and student's name.
            </li>

            <li>
                NEVER put chemicals in unmarked containers.
            </li>

            <li>
                Never sniff chemicals directly.
            </li>

            <li>
                Never suck up liquids with a pipette using
                the mouth. Always use a pipette filler.
            </li>

            <li>
                Contact or accidental inhalation of
                dangerous substances must be reported to
                the supervisor immediately.
            </li>

            <li>
                Immediately rinse splashes from the skin
                using plenty of cold water and follow the
                laboratory's appropriate procedure.
            </li>

            <li>
                Corrosive substances entering the eye should
                be thoroughly rinsed using the eye-wash
                equipment and medical assistance should
                be sought immediately.
            </li>

            <li>
                Immediately remove any clothing that has
                become contaminated with a corrosive
                substance.
            </li>

            <li>
                Seek medical advice in the event of an
                accident or if you feel unwell.
            </li>

            <li>
                Proper experimental planning is necessary,
                especially when working with flammable,
                poisonous or explosive substances.
            </li>

            <li>
                Do not experiment with chemicals.
                Tasting of chemicals is also prohibited.
            </li>

            <li>
                Fume cupboards must be used when vapours
                or gases may be present and their hazards
                are unknown.
            </li>

            <li>
                When bottles containing dangerous substances
                are opened, wear eye protection and gloves.
            </li>

            <li>
                Do not carry chemical bottles by the lids.
                Use a special basket to carry chemical
                bottles.
            </li>

            <li>
                Do not pour reagents back into bottles
                because this can cause contamination.
            </li>

            <li>
                Wipe any substances that have spilled
                immediately. Do not leave droplets on
                bottles and do not switch pipettes between
                bottles.
            </li>

            <li>
                Wash all glassware thoroughly using soap
                and water. If glassware does not come clean,
                ask the supervisor for help.
            </li>

            <li>
                All apparatus must be left clean and hands
                must be washed thoroughly if dangerous
                chemicals have been handled.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>C. Before Using Chemicals</h2>

        <p>
            Before using chemicals, first read the label
            and check for WARNINGS. Familiarize yourself
            with the warnings and take the necessary
            precautions. See the list of warning codes
            attached and look at the poster explaining
            the safety symbols. When ordering new chemicals,
            obtain the Material Safety Data Sheet (MSDS)
            from the supplier.
        </p>

    </div>


    <div class="info-section">

        <h2>D. Accidents and Injuries</h2>

        <ol>

            <li>Remain calm.</li>

            <li>
                If an acid or alkaline substance is spilled
                on the injured area, wash that area under
                the tap or emergency shower.
            </li>

            <li>
                If it is a cut or any other wound, call the
                supervisor and/or first aid person to help.
            </li>

            <li>
                Know where the nearest basin, emergency
                shower, eyewash bottle and first aid box
                are situated.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>E. Fire</h2>

        <ol>

            <li>Follow the rules for gas flames.</li>

            <li>
                Make sure that the gas burner taps are
                closed properly after use.
            </li>

            <li>
                Know where the closest fire extinguisher
                is and how to use it.
            </li>

            <li>
                If possible, close the main gas valve,
                close all windows and close the door when
                everyone in the laboratory is out.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>F. Emergency Conditions</h2>

        <ol>

            <li>
                Call the supervisor and emergency helpers.
            </li>

            <li>
                Know where the closest telephone is and
                phone the appropriate protection/emergency
                unit to report the incident.
            </li>

            <li>
                Give basic details so that the emergency
                services can be properly informed.
            </li>

            <li>
                Familiarize yourself with the emergency
                plan of the building, evacuation procedures
                and meeting place.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>G. Biological Safety</h2>

        <ol>

            <li>
                All biological materials have to be
                autoclaved. Red bins are available for
                appropriate biological waste.
            </li>

            <li>
                All tips must be autoclaved and must not
                be placed in regular waste bins.
                Special containers should be used for
                tips and sharps.
            </li>

            <li>
                All glassware used for sludge or to grow
                bacteria must be autoclaved before washing.
            </li>

            <li>
                When working with sludge, pathogenic
                bacteria or hazardous chemicals, white
                coats, gloves, masks and goggles are
                compulsory.
            </li>

            <li>
                Work in fume cupboards when working with
                sludge, effluent or hazardous chemicals.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>H. Broken Glass</h2>

        <ol>

            <li>
                <strong>Never</strong> put broken glass into
                the regular bin. Use the designated
                laboratory glass-disposal bins.
            </li>

            <li>
                Always clean glassware directly after use.
                Residue can harden and become difficult
                to remove.
            </li>

        </ol>

    </div>


    <div class="info-section">

        <h2>I. Cleaning of Laboratories</h2>

        <p>
            There are cleaners responsible for cleaning
            laboratory floors and garbage bins. Students
            are responsible for cleaning work surfaces
            and the rest of the laboratory. Students are
            responsible for cleaning everything they
            worked with.
        </p>

    </div>

</main>

</body>
</html>