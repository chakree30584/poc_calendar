<?php

function getAllRoles() {
    global $con;
    $roles = array();
    $res = $con->query("SELECT * FROM roles ORDER BY critical_order ASC");
    while ($data = $res->fetch_assoc()) {
        array_push($roles, $data);
    }
    return $roles;
}

function getRoleById($id) {
    global $con;
    $res = $con->query("SELECT * FROM roles WHERE id = '$id'");
    return $res->fetch_assoc();
}

function getStaffById($id) {
    global $con;

    //get workday count for that staff
    $res = $con->query("SELECT * FROM staff WHERE id = '$id'");
    $data = $res->fetch_assoc();

    $res = $con->query("SELECT * FROM slot WHERE staff_id = '$id'");
    $count = $res->num_rows;
    $data["workday_count"] = $count;

    $res = $con->query("SELECT * FROM staff_roles WHERE staff_id = '$id'");
    $count = $res->num_rows;
    $data["role_count"] = $count;

    $data["roles"] = array();
    while ($role = $res->fetch_assoc()) {
        $roledata = getRoleById($role["role_id"]);
        array_push($data["roles"], $roledata["name"]);
    }

    return $data;
}

function getStaffWorkStatsByWeekNo($id, $week_no) {
    global $con;
    $slotcount = array(
        "fullday" => 0,
        "halfday" => 0,
        "halfday-morning" => 0,
        "halfday-afternoon" => 0
    );
    $res = $con->query("SELECT * FROM slot WHERE staff_id = '$id' AND `weekno` = '$week_no' AND daytype = 'weekday'");
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
    $slotcount["weekend"] = $res->num_rows;
    $slotcount["weekend-leave20"] = 0;
    $slotcount["weekend-leave21"] = 0;
    while ($data = $res->fetch_assoc()) {
        if ($data["shift"] == "leave20") {
            $slotcount["weekend-leave20"]++;
        } else if ($data["shift"] == "leave21") {
            $slotcount["weekend-leave21"]++;
        }
    }

    return $slotcount;
}

function getStaffCountByRoleandWorkDay($role_id, $date_string) {
    global $con;
    $slotcount = array(
        "fullday" => 0,
        "halfday-morning" => 0,
        "halfday-afternoon" => 0
    );
    $res = $con->query("SELECT * FROM slot WHERE role_id = '$role_id' AND `workdate` = '$date_string'");
    while ($data = $res->fetch_assoc()) {
        if ($data["worktype"] == "fullday") {
            $slotcount["fullday"]++;
        } else if ($data["worktype"] == "halfday") {
            if ($data["shift"] == "morning") {
                $slotcount["halfday-morning"]++;
            } else if ($data["shift"] == "afternoon") {
                $slotcount["halfday-afternoon"]++;
            }
        }
    }
    return $slotcount["fullday"] + (($slotcount["halfday-morning"] + $slotcount["halfday-afternoon"]) / 2);
}
