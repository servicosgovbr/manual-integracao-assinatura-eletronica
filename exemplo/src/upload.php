<?php
if(!isset($_POST["submit"])) {
    header("Location: index.php");
    exit;
}

session_start();
$data = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);

$_SESSION['hash'] = hash('sha256', $data, true);

header("Location: assinar.php");

?>