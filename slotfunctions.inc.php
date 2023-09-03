<?php

function findStaff($role_id, $date_string, $week_no, $daytype) {
    $willsleep = false;

    global $con;
    $con->query("TRUNCATE TABLE `kopihub`.`tmp_findstaff`");
    $role = getRoleById($role_id);
    $role_name = $role["name"];

    //find staff that suitable with role and not worked on the day
    $query = "SELECT id FROM staff "
            . "WHERE "
            . " id IN (SELECT staff_id FROM staff_roles WHERE role_id = '$role_id') "
            . "AND"
            . " id NOT IN (SELECT staff_id FROM slot WHERE `workdate` = '$date_string') "
            . "AND id < 1000";

    $res = $con->query($query);
    while ($foundstaffid = $res->fetch_assoc()) {
        $staff_id = $foundstaffid["id"];
        $staff = getStaffById($staff_id);
        $staff_name = $staff["name"];
        $staff_roles = implode(", ", $staff["roles"]);
        $role_count = $staff["role_count"];
        $workday_count = $staff["workday_count"];

        $slotcount = getStaffWorkStatsByWeekNo($staff_id, $week_no);
        $fd_count = $slotcount["fullday"];
        $hd_count = $slotcount["halfday"];
        $hdmorn_count = $slotcount["halfday-morning"];
        $hdafter_count = $slotcount["halfday-afternoon"];
        $weekend_count = $slotcount["weekend"];
        $leave20_count = $slotcount["weekend-leave20"];
        $leave21_count = $slotcount["weekend-leave21"];

        $query = "INSERT INTO `tmp_findstaff`"
                . "(`id`, `role_id`, `role_name`, `workdate`, `weekno`, `staff_id`, `staff_name`, `staff_roles`, `role_count`, `workday_count`, `fd_count`, `hd_count`, `hdmorn_count`, `hdafter_count`, `weekend_count`,`leave20_count`,`leave21_count`) "
                . "VALUES "
                . "(null,'$role_id','$role_name','$date_string','$week_no','$staff_id','$staff_name','$staff_roles','$role_count','$workday_count','$fd_count','$hd_count','$hdmorn_count','$hdafter_count','$weekend_count','$leave20_count','$leave21_count')";

        $con->query($query);

        if ($staff_name == "ลา" && $daytype == "weekend") {
            $willsleep = true;
        }
    }

    //delete non min role_count
    if ($daytype == "weekday") {
        $res = $con->query("SELECT MIN(role_count) FROM tmp_findstaff");
        if ($res->num_rows > 0) {
            $data = $res->fetch_array();
            $min_role_count = $data[0];
            $con->query("DELETE FROM tmp_findstaff WHERE role_count != '$min_role_count'");
        }
    }

    if ($daytype == "weekday") {
        //delete non min workday_count
        $res = $con->query("SELECT MIN(workday_count) FROM tmp_findstaff");
        if ($res->num_rows > 0) {
            $data = $res->fetch_array();
            $min = $data[0];
            $con->query("DELETE FROM tmp_findstaff WHERE workday_count != '$min'");
        }
    }

    $res = $con->query("SELECT * FROM tmp_findstaff");
    if ($res->num_rows == 0) {

        $query = "INSERT INTO `tmp_findstaff`"
                . "(`id`, `role_id`, `role_name`, `workdate`, `weekno`, `staff_id`, `staff_name`, `staff_roles`, `role_count`, `workday_count`, `fd_count`, `hd_count`, `hdmorn_count`, `hdafter_count`, `weekend_count`,`leave20_count`,`leave21_count`) "
                . "VALUES "
                . "(null,'$role_id','$role_name','$date_string','$week_no','1001','ไม่มีพนักงาน','','99','0','0','3','0','0','0','0','0')";
        $con->query($query);
        return 1;
    }

    if ($willsleep) {
        //sleep(10);
    }
    return $res->num_rows;
}

function getFirstStaffFromFindStaff($daytype) {
    global $con;

    if ($daytype == "weekend") {

        //delete non min weekend_count
        $res = $con->query("SELECT MIN(weekend_count) FROM tmp_findstaff");
        if ($res->num_rows > 0) {
            $data = $res->fetch_array();
            $min = $data[0];
            $con->query("DELETE FROM tmp_findstaff WHERE weekend_count != '$min'");
        }

        //for weekend
        $res = $con->query("SELECT * FROM tmp_findstaff ORDER BY RAND()");
        $data = $res->fetch_assoc();
        $worktype = "fullday";

        $shift = "";

        //try to balance shift for the staff
        if ($data["leave20_count"] > $data["leave21_count"]) {
            $shift = "leave21";
        } else if ($data["leave20_count"] < $data["leave21_count"]) {
            $shift = "leave20";
        } else {
            $random = array("leave20", "leave21");
            shuffle($random);
            $shift = $random[0];
        }

        //sleep(1);
    } else {
        //for weekday
        //just pick 1 staff from the prepared list randomly
        $res = $con->query("SELECT * FROM tmp_findstaff ORDER BY RAND()");
        $data = $res->fetch_assoc();

        //expect that they already work fullday, should be half day type
        $worktype = "halfday";
        if ($data["fd_count"] == 0 && $data["hd_count"] < 3) {
            //random for worktype if there didn't work fullday and their halfday not reached limit
            $random = array("fullday", "halfday");
            shuffle($random);
            $worktype = $random[0];
        } else if ($data["fd_count"] == 0) {
            //they already work half day till limits
            $worktype = "fullday";
        }

        if ($data["hd_count"] >= 3 & $data["fd_count"] >= 1) {
            //error this should not happened
            echo "ERROR on getFirstStaffFromFindStaff() due to hd_count >= 3 and fd_count >= 1";
            print_r($data);
            die();
        }

        $shift = "";
        //try to balance shift for the staff
        if ($worktype == "halfday") {
            if ($data["hdmorn_count"] > $data["hdafter_count"]) {
                $shift = "afternoon";
            } else if ($data["hdmorn_count"] < $data["hdafter_count"]) {
                $shift = "morning";
            } else {
                $random = array("morning", "afternoon");
                shuffle($random);
                $shift = $random[0];
            }
        }
    }

    return array(
        "staff_id" => $data["staff_id"],
        "staff_name" => $data["staff_name"],
        "worktype" => $worktype,
        "shift" => $shift
    );
}

function getSecondStaffFromFindStaff($shift) {
    global $con;
    //remove non min hd_count
    $res = $con->query("SELECT MIN(hd_count) FROM tmp_findstaff");
    if ($res->num_rows > 0) {
        $data = $res->fetch_array();
        $min = $data[0];
        $con->query("DELETE FROM tmp_findstaff WHERE hd_count != '$min'");
    }

    // pick 1 staff from the prepared list 
    $res = $con->query("SELECT * FROM tmp_findstaff ORDER BY RAND()");
    $data = $res->fetch_assoc();

    if ($data["hd_count"] >= 3 & $data["fd_count"] >= 1) {
        //error this should not happened
        echo "ERROR on getSecondStaffFromFindStaff($shift) due to hd_count >= 3 and fd_count >= 1";
        print_r($data);
        die();
    }

    return array(
        "staff_id" => $data["staff_id"],
        "staff_name" => $data["staff_name"]
    );
}
