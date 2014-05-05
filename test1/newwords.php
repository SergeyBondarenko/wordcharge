<?php
session_start();
?>
<!DOCTYPE html>

<html>
<head>
  <title>WordCharge</title>
  <meta charset="utf-8">
  <link href="css/site.css" rel="stylesheet">
</head>
<body>

  <?php include("php/header.php"); ?>
  <div id="wrapper-main">
    <div id="wrapper-login">
      <?php include_once("php/wrapper-login.php");?>
    </div>

    <h2>WordCharge</h2>
    <h3>New Words</h3>
    <?php 
        include("php/vars.php");
        include("php/latestdict.php"); 
    ?>
    <?php include("php/footer.php"); ?>
  </div>

</body>
</html>
