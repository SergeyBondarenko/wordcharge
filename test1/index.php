<?php
session_start();
//echo $_SESSION["myusername"];
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
    <p>You can learn words from a news or books.</p>
    <p>You can make a custom dictionary:</p>
    
   <form action="newdict.php" method="post">
<!--    <form action="pbar.php" method="post">-->
        <div id="wrapper-langSelect">
            Select a language:
            <select id="langId" name="langId">
                <option value="es-en">Spanish</option>
                <option value="fr-en">French</option>
                <option value="de-en">German</option>
                <option value="it-en">Italian</option>
                <option value="ru-en">Russian</option>
                <option value="uk-en">Ukrainian</option>
                <option value="en-ru" selected>English-Russian</option>
            </select>
        </div>
        <div id="wrapper-textArea">
            <textarea rows="9" cols="70" placeholder="Copy your text in this text area ..." 
                    name="textArea" id="textArea"></textarea>
        </div>
        <div id="wrapper-makeDict">
            <!--<a href="#" id="makeDict">Make Dict</a>-->
            <input type="submit" value="Make Dict">
        </div>
    </form>

    <?php include("php/footer.php"); ?>
  </div>

</body>
</html>
