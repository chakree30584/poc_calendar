<?php
include "./include/config.inc.php";
include "./include/dbcon.inc.php";
include "./include/functions.inc.php";
//get all roles
$roles = getAllRoles();
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>title</title>
        <style>
            .drawline td {
                border: 1px solid;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }
        </style>
    </head>
    <body>
        <?php
        $dt = DateTime::createFromFormat('Y-m-d', '2023-09-01');

        while (true) {
            $date_string = $dt->format('Y-m-d');
            $dayofweek = $dt->format('l');
            ?>
            <table border="1" style="width:600px;">
                <tr>
                    <td rowspan="16"><?php echo $dayofweek . ", " . $date_string; ?></td>
                </tr>
                <?php
                foreach ($roles as $key => $role) {
                    ?>
                    <tr>
                        <td>
                            <b><?php echo $role["name"]; ?></b>
                            <br>
                            MIN: <?php echo $role["required_min"]; ?><br>
                            MAX: <?php echo $role["required_max"]; ?><br>
                            Priority: <?php echo $role["critical_order"]; ?>
                        </td>
                        <td>
                            <table style="width:100%; text-align: center;">
                                <?php
                                $res = $con->query("SELECT * FROM slot WHERE workdate = '$date_string' AND role_id = '" . $role["id"] . "' "
                                        . "AND worktype = 'fullday'");
                                while ($slot = $res->fetch_assoc()) {
                                    $staff = getStaffById($slot["staff_id"]);
                                    ?>
                                    <tr    class="drawline">
                                        <td colspan="9999" style="background-color:<?php echo ($staff["id"] == 1001 ? "red" : "white"); ?>">
                                            <?php echo $staff["name"]; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <?php
                                $resm = $con->query("SELECT * FROM slot WHERE workdate = '$date_string' AND role_id = '" . $role["id"] . "' "
                                        . "AND worktype = 'halfday' AND shift = 'morning'");

                                $resa = $con->query("SELECT * FROM slot WHERE workdate = '$date_string' AND role_id = '" . $role["id"] . "' "
                                        . "AND worktype = 'halfday' AND shift = 'afternoon'");

                                if ($resm->num_rows != 0) {
                                    ?>
                                    <tr>
                                        <td style="width:100px;">
                                            <table style="width:100%; text-align: center;"  class="drawline">
                                                <?php
                                                while ($slot = $resm->fetch_assoc()) {
                                                    $staff = getStaffById($slot["staff_id"]);
                                                    ?>
                                                    <tr>
                                                        <td style="background-color:<?php echo ($staff["id"] == 1001 ? "red" : "white"); ?>">
                                                            <?php echo $staff["name"]; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </table>
                                        </td>
                                        <td style="width:100px;">
                                            <table style="width:100%; text-align: center;"  class="drawline">
                                                <?php
                                                while ($slot = $resa->fetch_assoc()) {
                                                    $staff = getStaffById($slot["staff_id"]);
                                                    ?>
                                                    <tr>
                                                        <td style="background-color:<?php echo ($staff["id"] == 1001 ? "red" : "white"); ?>">
                                                            <?php echo $staff["name"]; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </table>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <br>
            <br>
            <?php
            $dt->add(new DateInterval('P1D'));
            echo "\n";

            //if last day of month stop running
            if ($dt->format('m') != 9) {
                break;
            }
        }
        ?>
    </body>
</html>