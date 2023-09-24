<?php
include "./config.inc.php";
include "./dbcon.inc.php";
?>
<html>
    <head>
        <title>Add</title>
    </head>
    <body>
        <a href="add"><button>Add</button></a>
        <br>
        <?php
        $res = $con->query("SELECT * FROM staff");

        while ($data = $res->fetch_assoc()) {
            //echo "SELECT * FROM staff_roles WHERE staff_id = '" . $data["id"] . "'";
            $res2 = $con->query("SELECT * FROM staff_roles WHERE staff_id = '" . $data["id"] . "'");
            //echo $res2->num_rows;
            echo "<b>" . $data["name"] . "</b>&nbsp;&nbsp;&nbsp;";
            while ($data2 = $res2->fetch_assoc()) {
                //print_r($data2);
                $res3 = $con->query("SELECT * FROM roles WHERE id = '" . $data2["role_id"] . "'");
                $res3res = $res3->fetch_assoc();
                echo $res3res["name"] . ", ";
            }
            ?>
            <br>
        <?php }
        ?>
    </body>
</html>

