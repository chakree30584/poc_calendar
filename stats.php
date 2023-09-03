<?php
include "./config.inc.php";
include "./dbcon.inc.php";
include "./functions.inc.php";
?>

<html>
    <head>
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
        <table border="1">
            <tr>
                <td>Name</td>
                <td>Role</td>
                <td>TotalWorkDay</td>
                <td>Weekend-leave20</td>
                <td>Weekend-leave21</td>
                <td>FullDay</td>
                <td>HalfDay-Morning</td>
                <td>HalfDay-Afternoon</td>
                <td>Total HR</td>
            </tr>
            <?php
            $staffres = $con->query("SELECT * FROM staff WHERE id < 1000");
            while ($staff = $staffres->fetch_assoc()) {
                $id = $staff["id"];
                $staff = getStaffById($id);

                $res = $con->query("SELECT * FROM slot WHERE staff_id = '$id'");
                $count = $res->num_rows;
                $workday_count = $count;

                $slotcount = array(
                    "fullday" => 0,
                    "halfday" => 0,
                    "halfday-morning" => 0,
                    "halfday-afternoon" => 0
                );
                $res = $con->query("SELECT * FROM slot WHERE staff_id = '$id' AND daytype = 'weekday'");
                while ($data = $res->fetch_assoc()) {
                    if ($data["worktype"] == "fullday") {
                        $slotcount["fullday"]++;
                    } else if ($data["worktype"] == "halfday") {
                        $slotcount["halfday"]++;
                        if ($data["shift"] == "morning") {
                            $slotcount["halfday-morning"]++;
                        } else if ($data["shift"] == "afternoon") {
                            $slotcount["halfday-afternoon"]++;
                        }
                    }
                }

                $res = $con->query("SELECT * FROM slot WHERE staff_id = '$id' AND daytype = 'weekend'");
                $weekend = $res->num_rows;
                $slotcount["weekend-leave20"] = 0;
                $slotcount["weekend-leave21"] = 0;
                while ($data = $res->fetch_assoc()) {
                    if ($data["shift"] == "leave20") {
                        $slotcount["weekend-leave20"]++;
                    } else if ($data["shift"] == "leave21") {
                        $slotcount["weekend-leave21"]++;
                    }
                }
                $leave20hr = $slotcount["weekend-leave20"] * 12;
                $leave21hr = $slotcount["weekend-leave21"] * 13;
                $fulldayhr = $slotcount["fullday"] * 13;
                $mornhr = $slotcount["halfday-morning"] * 7.5;
                $afterhr = $slotcount["halfday-afternoon"] * 5.5;
                ?>
                <tr>
                    <td><?php echo $staff["name"]; ?></td>
                    <td><?php echo implode(", ", $staff["roles"]); ?></td>
                    <td><?php echo $workday_count; ?></td>
                    <td><?php echo $slotcount["weekend-leave20"]; ?> = <?php echo $leave20hr; ?> ชม.</td>
                    <td><?php echo $slotcount["weekend-leave21"]; ?> = <?php echo $leave21hr; ?> ชม.</td>
                    <td><?php echo $slotcount["fullday"]; ?> = <?php echo $fulldayhr; ?> ชม.</td>
                    <td><?php echo $slotcount["halfday-morning"]; ?> = <?php echo $mornhr; ?> ชม.</td>
                    <td><?php echo $slotcount["halfday-afternoon"]; ?> = <?php echo $afterhr; ?> ชม.</td>
                    <td><?php echo $leave20hr+$leave21hr+$fulldayhr+$mornhr+$afterhr; ?> ชม.</td>
                </tr>
                <?php
            }
            ?>
        </table>
        <br><br>
    </body>
</html>