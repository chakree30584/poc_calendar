<?php

$con;
if ($config["db"]["server"] == "localhost") {
    $con = new mysqli($config["db"]["server"], $config["db"]["username"], $config["db"]["password"], $config["db"]["database"]);
} else {
    $con = new mysqli($config["db"]["server"], $config["db"]["username"], $config["db"]["password"], $config["db"]["database"], $config["db"]["port"]);
}
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo "Database Connection Failed !";
    die();
}
$con->set_charset($config["db"]["charset"]);
?>