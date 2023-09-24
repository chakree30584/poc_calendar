<?php
include "./config.inc.php";
include "./dbcon.inc.php";
print_r($_POST);
$con->query("INSERT INTO `staff`(`id`, `name`) VALUES (null,'" . $_POST["name"] . "')");

$id = $con->insert_id;
foreach ($_POST["roles"] as $key => $value) {
    echo "\nINSERT INTO `staff_roles`(`id`, `staff_id`, `role_id`) VALUES (null,'$id','$value');";
    $con->query("INSERT INTO `staff_roles`(`id`, `staff_id`, `role_id`) VALUES (null,'$id','$value')");
}
?>
<script>
    document.location = "./add_list";
</script>