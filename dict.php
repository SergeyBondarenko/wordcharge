<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WordCharge is a service for learning foreign languages. Learn new wordsi.">
    <meta name="author" content="Sergey Bondarenko">
    <link rel="icon" href="img/favicon.png" type="image/png">

    <title><?php echo $langArray["projectName"]; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/navbar-static-top.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
      <?php
        include_once("php/vars.php");
        include_once("php/functions.php");
        include_once("php/setsitelanguage-1.php"); 
        include_once("nphp/classes/wrdchUser.php");
        include_once("nphp/classes/wrdchWords.php");
 
        $textArea = $_POST['textArea'];
        $theSessionUser = sanitize_input($_SESSION["myusername"]);
        $langId = sanitize_input($_POST['langId']);
        $dictCaller = sanitize_input($_POST['dictCaller']); // customdict, newsdict, booksdict
        $myLang = sanitize_input($_GET['myLang']);
      
        
      ?>

	  <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="js/jquery.min.js"></script>
	  <script src="js/jquery-ui.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="js/ie10-viewport-bug-workaround.js"></script>

    <!--jQuery scripts-->
    <script>
      var langId = <?php echo json_encode($langId); ?>;
      var myLang = <?php echo json_encode($myLang); ?>;
      var theSessionUser = <?php echo json_encode($theSessionUser); ?>;
    </script>
    <script src="js/makeWordsKnown.js"></script>
</head>
<body>

	<?php include_once("navbar.php"); ?>

    <div class="container">

    	<div class="jumbotron">

        <!-- Progress bar holder -->
        <div class="progress">
          <div class="progress-bar progress-bar-custom" id="progress" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 100%"> </div>
        </div>

        <!-- Progress information -->
        <div id="information" style="width"></div>
        
        <p id="wordSaveStatus"><?php echo $langArray["textWordSaveStatus"]; ?></p>
        
        <?php
            header('Content-Type: text/html; charset=utf-8');

           
            
            // Take the loged user name as a tables name           
            $UserNW=$theSessionUser."_NW"; //New words
            $UserKNW=$theSessionUser."_KNW"; //Known words
            $UserNW = preg_replace('/[^a-zA-Z0-9_]/', '_', $UserNW);
            $UserKNW = preg_replace('/[^a-zA-Z0-9_]/', '_', $UserKNW);
 
            $myUser = new wrdchUser($theSessionUser);
            //echo $myUser->classname . "<br>";
            //echo $myUser->username . "<br>";
            
            $myWords = new wrdchWords;
            
            
            if ($dictCaller == 'booksdict' or $dictCaller == 'newsdict') {
                $myUrl = $_POST["myUrl"];
                $numWords = sanitize_input($_POST["numWords"]);
                $myUrl = get_redirected_url($myUrl);
                $content = remote_get_contents($myUrl);
                $text = strip_html_js_tags($content);
            }
            
            /*if ($dictCaller == 'booksdict'){
                $words = split_text_into_words($text,$numWords);
            } else if ($dictCaller == 'newsdict') {
                
                $dirtyWords = explode(" ", $content);
                $words = array_filter($dirtyWords, "return_only_words");
                
                if(isset($numWords) && $numWords < array_count_values($words)){
                    $words = array_slice($words, 0, $numWords);
                }
                
                //Delete all words which len==1
                foreach ($words as $key=>$word)
                {
                  if (strlen($words[$key]) < 2){
                    unset($words[$key]);
                  }
                }
                
                $words = array_map('strtolower', $words);
                // Delete all dublicate words in the array and sort in descending order
                $words = array_count_values($words);
                arsort($words);
            } 
            else {*/
            if ($dictCaller == 'booksdict' or $dictCaller == 'newsdict'){
                $words = wrdchWords::splitintowords($text,$numWords);
            } else {
                // 1.=====
                // Get data from html form textrArea field, remove all special characters
                // and make an array ($words), convert all words to lowercase 
                // Delete all dublicate words in the array and sort in descending order
                $words = wrdchWords::splitintowords($textArea,str_word_count($textArea)); 
                //$words = split_text_into_words($textArea);
            }
            
            
            
            $totalWords = count($words);
            
            // Select only new words
            $words = look_for_the_new_words($words,$langId,$MysqlUser,$MysqlUPass,$MysqlDB,$UserKNW);
            
            // Count words stat
            $totalNew = count($words);
            
            
            
            $youKnow = $totalWords - $totalNew;
            $yPercent = ($youKnow * 100)/$totalWords;
            echo "<div id=\"wordsStat\">".$langArray["textNewdictTotal"].": ".$totalWords."; ".$langArray["textNewdictNew"].": ".$totalNew."; ".$langArray["textNewdictYouknow"].": ".$youKnow." (".round($yPercent,2)."%);"."</div>";
            
            // 2.=====
            // Set MySQL connection and fill the database with new words
            $con=mysqli_connect("localhost",$MysqlUser,$MysqlUPass,$MysqlDB);
            
            // Check connection
            if (mysqli_connect_errno()) {
              echo "Failed to connect to MySQL: " . mysqli_connect_error();
            }
           
            //Purge _NW table  
            $sqlPurgeNW = mysqli_query($con, "TRUNCATE TABLE $UserNW");
            if(!$sqlPurgeNW){
              die('dict.php - Error after NW table purging: ' . mysqli_error($con)); 
            }
            //!!!Check before implementing. This code purges _KNW table!!!
            /*if ($UserKNW == '_KNW'){
                $sqlEmptyDel = mysqli_query($con, "TRUNCATE TABLE $UserKNW");
                if(!$sqlEmptyDel){
                  die('dict.php - Error after KNW table purging: ' . mysqli_error($con)); 
                }
            }*/
            
            $sqlCreate = "CREATE TABLE IF NOT EXISTS $UserNW( ".
                   "id INT(20) NOT NULL AUTO_INCREMENT, ".
                   "lang VARCHAR(10) NOT NULL, ".
                   "freq BIGINT(20) NOT NULL, ".
                   "word VARCHAR(40) NOT NULL, ".
                   "text VARCHAR(255) NOT NULL, ".
                   "PRIMARY KEY ( id )) ".
                   "ENGINE=InnoDB DEFAULT CHARSET=utf8";
            if (!mysqli_query($con,$sqlCreate)) {
              die('Error after Create: ' . mysqli_error($con));
            }
            mysqli_free_result($sqlCreate);
 
            // Mysql query to delete old data from the new words table
            $sqlDelete = "TRUNCATE TABLE $UserNW";
            if (!mysqli_query($con,$sqlDelete)) {
              die('Error after Delete: ' . mysqli_error($con));
            }
            mysqli_free_result($sqlDelete);
            
            // change character set to utf8
            if (!mysqli_set_charset($con, "utf8")) {
                printf("Error loading character set utf8: %s\n", mysqli_error($con));
            }
            
            
            
             //$totalNew = maximum number of new words found
            // Loop through the words in $words array and run the Progress Bar
            //$timerStart = microtime(true);
            for($i = 0; $i <= $totalNew; $i++){

                // Progress Bar: Calculate the percentation
                $percent = intval($i/$totalNew * 100)."%";
                
                // Progress Bar: Javascript for updating the progress bar and information
                echo '<script language="javascript">
                document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#428bca;\">&nbsp;</div>";
                document.getElementById("information").innerHTML="'.$i.' '.$langArray["textNewdictProcessBar"].'";
                </script>';
            
                // Progress Bar: This is for the buffer achieve the minimum size in order to flush data
                echo str_repeat(' ',1024*64);
                
            
                // Progress Bar: Send output to browser immediately
                flush();
                
            
                // Get keys and values from $words and separate them
                $onlyWords = array_keys($words);
                $onlyFreq = array_values($words);
                
                // Insert word and its frequency into database
                $sqlInsert = mysqli_query($con, "INSERT INTO $UserNW (lang,freq,word) VALUES ('$langId','$onlyFreq[$i]', '$onlyWords[$i]')");
                if(!$sqlInsert){
                  die('newdict.php - Error after Insert: ' . mysqli_error($con)); 
                }
                /* free result set */
                mysqli_free_result($sqlInsert);

                // 3.=====
                // Translate every word in the $onlyWords[] array
                // Get word translation from Yandex Translate API
                // Get translation from Yandex Dict API
                // Merge Translate and Dict arrays into third array 
                // and delete all dublicate values in the third array 
                //Implode the merged third array into string of values separated by coma
                $strDict = get_yandex_api_translation_dictionary($onlyWords[$i], $langId, $trnsl_api, $trnsl_key, $dict_api, $dict_key, $google_trnsl_key, $google_trnsl_api);
                
                // Sql query to update translation for the word
                $sqlUpdate = mysqli_query($con, "UPDATE $UserNW SET text='$strDict' WHERE word='$onlyWords[$i]' AND freq='$onlyFreq[$i]' AND lang='$langId'");
                if(!$sqlUpdate){
                  die('newdict.php - Error after Update: ' . mysqli_error($con)); 
                }
                /* free result set */
                mysqli_free_result($sqlUpdate);

            }
            //$elapsed = microtime(true) - $timerStart;
            //echo "<br> Loop timer: ".$elapsed."<br>";
            
            //Delete empty rows
            $sqlEmptyDel = mysqli_query($con, "DELETE FROM $UserNW WHERE id=11 ORDER BY id DESC LIMIT 1");
            if(!$sqlEmptyDel){
              die('dict.php - Error after empty element delete: ' . mysqli_error($con)); 
            }
            
            // Progress Bar: Tell user that the process is completed
            echo '<script language="javascript">document.getElementById("information").innerHTML="'.$langArray["textNewdictProcessBarComplete"].'"</script>'.'<br>';
            
            // 4.=====
            // Display words

            // Mysql query to display the table content 
            //$UserNW = mysqli_real_escape_string($con, $UserNW);
            $sqlSelect = mysqli_query($con,"SELECT * FROM $UserNW");
            if(!$sqlSelect){
              die('newdict.php - Error after Select: ' . mysqli_error($con)); 
            }
            
            // Display user dictinary in form of the html table
            echo "<br>";
            echo $langArray["textNewdictDict"].": " . $langId . "<br>";
            echo "<table class='table table-striped table-hover'>
            <tr>
            <th>".$langArray["textTableIknow"]."</th>
            <th>".$langArray["textTableFreq"]."</th>
            <th>".$langArray["textTableWord"]."</th>
            <th>".$langArray["textTableText"]."</th>
            </tr>";
            
            while($row = mysqli_fetch_array($sqlSelect)) {
                if($row['freq'] != 0){
                  echo "<tr class='active'>";
                  echo "<td>" . "<span class=\"iKnowTheWord\"><a href=\"\">".$langArray["textTableYes"]."</a></span>" . "</td>";
                  echo "<td>" . "<span class=\"tdFreq\">" . $row['freq'] . "</span>" . "</td>";
                  //echo "<td>" . $row['word'] . "</td>";
                  echo "<td>" . "<span class=\"tdWord\">" . $row['word'] . "</span>" . "</td>";
                  echo "<td>" . "<span class=\"tdText\">" . $row['text'] . "</span>" . "</td>";
                  echo "</tr>";
                }
            }
            
            echo "</table><br>";
           

            /* free result set */
            mysqli_free_result($sqlSelect);
            
            // Close MySQL connection
            mysqli_close($con);
            
        
            
        ?>

    	</div> <!--jumbotron-->

   <!--Footer-->
		<?php include_once("php/footer-1.php");?>
   <!--END of Footer-->

    </div> <!--container-->
    
</body>
</HTML>
