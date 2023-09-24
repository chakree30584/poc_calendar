<?php
include "./include/config.inc.php";
include "./include/dbcon.inc.php";
include "./include/functions.inc.php";
include "./include/slotfunctions.inc.php";

$debugquery = false;

$year = "2023";
$month = "09";

//create dateTime Object at First Day of month
$dt = DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-01');

//clear slot table first
$con->query("TRUNCATE TABLE `kopihub`.`slot`");

$roles = getAllRoles();

while (true) {
    //if dt runs to next month will stop running
    if ($dt->format('m') != 9) {
        echo "\nprocessed ended";
        die();
    }

    $date_string = $dt->format('Y-m-d');
    $week_no = $dt->format('W');
    $dayofweek = $dt->format('l');
    $dayofweek_no = $dt->format('w');
    $daytype = "weekday";
    if ($dayofweek_no == 0 || $dayofweek_no == 6) {
        $daytype = "weekend";
    }
    echo "\n\n**************************************************\n";
    echo "$dayofweek, $date_string \n";
    echo "**************************************************\n";

    if ($dayofweek_no == 4) {
        echo "Skipped: DayofWeek is Thursday !\n";
        $dt->add(new DateInterval('P1D'));
        continue;
    }

    //inprogress skip sat,sun
    if ($dayofweek_no == 0) {
        //echo "Skipped: DayofWeek is Sunday !\n";
        //$dt->add(new DateInterval('P1D'));
        //continue;
    }
    if ($dayofweek_no == 6) {
        //echo "Skipped: DayofWeek is Saturday !\n";
        //$dt->add(new DateInterval('P1D'));
        //continue;
    }

    //MINIMUM run though each role and get staff suitable for each roles
    //this loop should run pass or fail die() since there should be minimum staff available
    while (true) {
        $loopkicker = true;
        foreach ($roles as $key => $role) {
            $role_id = $role["id"];
            $required_min = $role["required_min"];

            //get current staff count for the role this day
            $count = getStaffCountByRoleandWorkDay($role_id, $date_string);

            if ($count >= $required_min) {
                continue;
            }

            echo "$dayofweek, $date_string: [Find MIN $count/$required_min] " . $role["name"] . " -> ";

            $loopkicker = false;
            $num_staffs_found = findStaff($role_id, $date_string, $week_no, $daytype);
            if ($num_staffs_found == 0) {
                echo "*Error* no more staff available, exiting script\n";
                die();
            }
            $staff = getFirstStaffFromFindStaff($daytype);
            $staff_id = $staff["staff_id"];
            $worktype = $staff["worktype"];
            $shift = $staff["shift"];

            $insertquery = "INSERT INTO `slot`(`id`, `staff_id`, `role_id`, `worktype`, `shift`, `workdate`, `dayofweek`, `daytype`, `weekno`) "
                    . "VALUES (null,'$staff_id','$role_id','$worktype','$shift','$date_string','$dayofweek', '$daytype', '$week_no')";
            $con->query($insertquery);

            if ($con->error == "") {
                echo "OK (" . $staff["staff_name"] . ", " . $worktype . $shift . ")";
            }

            //usleep(50000);

            //incase of halfday we have to find another halfday staff
            if ($worktype == "halfday") {
                $newshift = "morning";
                if ($shift == "morning") {
                    $newshift = "afternoon";
                }
                echo "\t*finding $worktype$newshift*\n";

                //get current staff count for the role this day
                $count = getStaffCountByRoleandWorkDay($role_id, $date_string);
                echo "$dayofweek, $date_string: [Find MIN $count/$required_min] " . $role["name"] . " -> ";
                $num_staffs_found = findStaff($role_id, $date_string, $week_no, $daytype);
                if ($num_staffs_found == 0) {
                    echo "*Error* no more staff available, exiting script\n";
                    die();
                }
                $staff = getSecondStaffFromFindStaff($newshift);
                $staff_id = $staff["staff_id"];

                $insertquery = "INSERT INTO `slot`(`id`, `staff_id`, `role_id`, `worktype`, `shift`, `workdate`, `dayofweek`, `daytype`, `weekno`) "
                        . "VALUES (null,'$staff_id','$role_id','$worktype','$newshift','$date_string','$dayofweek', '$daytype', '$week_no')";
                $con->query($insertquery);

                if ($con->error == "") {
                    echo "OK (" . $staff["staff_name"] . ", " . $worktype . $shift . ")";
                }

                //usleep(100000);
            }

            //end role loop
            echo "\n";
        }
        echo "--------------------------------------------------------------------------\n";
        if ($loopkicker) {
            break;
        }
    }




    //MAXIMUM run though each role and get staff suitable for each roles
    //fill roles need by order only
    foreach ($roles as $key => $role) {

        $role_id = $role["id"];
        $required_max = $role["required_max"];

        //get current staff count for the role this day
        $count = getStaffCountByRoleandWorkDay($role_id, $date_string);

        if ($count >= $required_max) {
            continue;
        }

        while ($count < $required_max) {
            echo "$dayofweek, $date_string: [Find MAX $count/$required_max] " . $role["name"] . " -> ";

            $num_staffs_found = findStaff($role_id, $date_string, $week_no, $daytype);
            if ($num_staffs_found == 0) {
                echo "*Error* no more staff available, exiting script\n";
                die();
            }
            $staff = getFirstStaffFromFindStaff($daytype);
            $staff_id = $staff["staff_id"];
            $worktype = $staff["worktype"];
            $shift = $staff["shift"];

            $insertquery = "INSERT INTO `slot`(`id`, `staff_id`, `role_id`, `worktype`, `shift`, `workdate`, `dayofweek`, `daytype`, `weekno`) "
                    . "VALUES (null,'$staff_id','$role_id','$worktype','$shift','$date_string','$dayofweek', '$daytype', '$week_no')";
            $con->query($insertquery);

            if ($con->error == "") {
                echo "OK (" . $staff["staff_name"] . ", " . $worktype . $shift . ")";
            }

            //usleep(50000);

            //incase of halfday we have to find another halfday staff
            if ($worktype == "halfday") {
                $newshift = "morning";
                if ($shift == "morning") {
                    $newshift = "afternoon";
                }
                echo "\t*finding $worktype$newshift*\n";

                //get current staff count for the role this day
                $count = getStaffCountByRoleandWorkDay($role_id, $date_string);
                echo "$dayofweek, $date_string: [Find MAX $count/$required_min] " . $role["name"] . " -> ";
                $num_staffs_found = findStaff($role_id, $date_string, $week_no, $daytype);
                if ($num_staffs_found == 0) {
                    echo "*Error* no more staff available, exiting script\n";
                    die();
                }
                $staff = getSecondStaffFromFindStaff($newshift);
                $staff_id = $staff["staff_id"];

                $insertquery = "INSERT INTO `slot`(`id`, `staff_id`, `role_id`, `worktype`, `shift`, `workdate`, `dayofweek`, `daytype`, `weekno`) "
                        . "VALUES (null,'$staff_id','$role_id','$worktype','$newshift','$date_string','$dayofweek', '$daytype', '$week_no')";
                $con->query($insertquery);

                if ($con->error == "") {
                    echo "OK (" . $staff["staff_name"] . ", " . $worktype . $shift . ")";
                }

                //usleep(100000);
            }
            echo "\n";
            $count = getStaffCountByRoleandWorkDay($role_id, $date_string);
        }
        echo "--------------------------------------------------------------------------\n";
    }













//end day, increment date by 1 
    $dt->add(new DateInterval('P1D'));
}    