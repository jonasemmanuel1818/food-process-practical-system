<?php

require_once "config.php";

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

$practical_id = isset($_GET["id"])
    ? intval($_GET["id"])
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Select Practical</title>

    <link rel="stylesheet"
          href="assets/style.css">

</head>

<body>

<header class="topbar">

    <div>

        <h2>
            Food Process Engineering
        </h2>

    </div>

    <div>

        <a href="dashboard.php"
           class="logout-btn">

            Dashboard

        </a>

    </div>

</header>


<main class="content">

<?php

if ($practical_id === 1) {

    include "practicals/practical1.php";
}

    elseif ($practical_id === 2) {

    include "practicals/practical2.php";
}

    elseif ($practical_id === 3) {

    include "practicals/practical3.php";

} 
    elseif ($practical_id === 4) {

    include "practicals/practical4.php";

} 
    else {

?>

    <div class="alert">

        Practical not found.

    </div>

<?php

}

?>

</main>

</body>

</html>