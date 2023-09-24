<?php
include "./config.inc.php";
include "./dbcon.inc.php";
?>
<html>
    <head>
        <title>Add</title>
    </head>
    <body>
        <form action="add_save.php" method="POST">
            Name: <input type="name" name="name">
            <br>
            <?php
            $res = $con->query("SELECT * FROM roles");

            while ($data = $res->fetch_assoc()) {
                ?>
                <input type="checkbox" name="roles[]" value="<?php echo $data["id"]; ?>"> <?php echo $data["name"]; ?> (<?php echo $data["category"]; ?>)
                <br>
            <?php }
            ?>
            <button type="submit">Submit</button>
        </form>
    </body>
</html>
