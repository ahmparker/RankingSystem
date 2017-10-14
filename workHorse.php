<?php
    
    //$servername = $_SERVER['REMOTE_ADDR']; //servername is the IP address of this c9 environment

    
    include('simple_html_dom.php');
    runMe();
    function runMe()
    {
        
    $servername = "localhost";
    $username = "adamparker";
    $password = "";
    $database = "testRanks";
    $dbport = 3306;
    
    $db = new mysqli($servername, $username, $password, $database, $dbport); //connect to our sql database
    if ($db->connect_error) //test connection to database
    {
     die("Connection failed: " . $db->connect_error);
    } 
        $dps = array(800,677,589,538,501,470,444,422,401,383,366,351,336,322,309,296,284,273,262,251,240,230,220,211,202,193,184,175,166,158,149,141,133,125,117,110,102,95,87,80,72,65,57,50,43,36,29,21,14,7,0) ;//array version of the FIDE table of conversion from decimal score (which we denote as the index position/100) to rating differences (a rating difference is what's contained at each index) 
        $myDate=getdate();
        $unixDate = $myDate[0]; //gives us the date in UNIX form, which smash.gg uses  
        $yesterday = $unixDate - (24*60*60); //sets it to yesterday
        $page = 1;
        $totalPages = 1;
        $slugArray;
        $slugCount = 0;
        for($page = 1; $page <= $totalPages; $page++)
        {
            //to add: sets should impact players' elo in the order they happen (ie winners round 1 set should be inputted before winners round 3, losers round 1, etc.). the json data you're gonna copy from getsets.php has node called "updatedAtMicro"
            //updatedAtMicro gives us the date/time a set was updated in Unix form. Maybe use an array to store all the sets, and then order all the sets by the time they were inputted
            
            //OLD:  $jsonURL = "https://smash.gg/api/-/gg_api./public/tournaments/schedule;filter=%7B%22upcoming%22%3Afalse%2C%22videogameIds%22%3A%221%22%2C%2afterDate%22%3A1483257600%2C%22beforeDate%22%3A$unixDate%7D&page=$page;per_page=30?returnMeta=true";
            $jsonURL = "https://smash.gg/api/-/gg_api./public/tournaments/schedule;filter=%7B%22upcoming%22%3Afalse%2C%22videogameIds%22%3A%221%22%2C%22afterDate%22%3A1483257600%2C%22beforeDate%22%3A$unixDate%7D;page=$page;per_page=30?returnMeta=true" ;
            $raw = file_get_contents($jsonURL);
            $json = json_decode($raw,true); //$json is an assosciative array full of delicious data
            
            if (sizeof($json["items"]) == 0) //if we get to a point where there are no more tourneys
            {
                break;
            }
            
            $tournaments = $json["items"]["entities"]["tournament"];
            
            foreach ($tournaments as $tournament)
            {
                $endDate = $tournament["endAt"];
                $startDate = $tournament["startAt"];
                $jan1st = 1483254000; // jan 1st in unix
                $myDate=getdate();
                $unixDate = $myDate[0]; //gives us the date in UNIX form, which smash.gg uses 
                $yesterday = $unixDate - (24*60*60); //sets it to yesterday
                if ($endDate < $yesterday && $startDate > $jan1st)
                {
                    $slug = str_ireplace("tournament/", "", $tournament["slug"]);
                    echo $slug . " ends at " . $endDate . "<br />";
                    $slugArray[$slugCount] = $slug;
                    $slugCount++;
                    $dates[$slug] = $endDate;
                }
                
            }
            /*
            foreach ($slugArray as $slug)
            {
                echo $slug . "<br />"; //print out all of our slugs
            }
            */
            foreach ($slugArray as $slug) //create events (WE ONLY WANT MELEE)
            {
                $eventJSONURL = "http://api.smash.gg/tournament/$slug?expand[]=event&expand[]=phase&expand[]=groups&expand[]=entrants";
                $eventRaw = file_get_contents($jsonURL);
                $eventJSON = json_decode($eventRaw,true);
                
                
                foreach($eventJSON["entities"]["event"] as $event)
                {
                    
                    if ($event["name"] == "Melee Singles")
                    {
                        $meleeID = $event["id"];
                    }
                }
                $v = 0;
                foreach($eventJSON["entities"]["phase"] as $phase)
                {
                    if ($phase["eventID"] == $meleeID)
                    {
                        $phaseID[$v] = $phase["id"];
                        $v++;
                    }
                }
                foreach($phaseID as $phaseID)
                {
                    $setsJSONURL = "http://api.smash.gg/phase_group/$phaseID?expand[]=sets";
                    $setsRAW = file_get_contents($setsJSONURL);
                    $setsJSON = json_decode($setsRAW,true);
                    
                    //now we need a loop that goes through all of the sets 
                    $allSets = $setsJSON["entities"]["sets"];
                    foreach($allSets as $set)
                    {
                        $setID = $set["id"];
                        $p1ID = $set["entrant1Id"]; //this is the smash.gg id of player 1
                        $p2ID = $set["entrant2Id"]; //this is the smash.gg id of player 2. use these ids to pull rank information from SQL table
                        $p1Score = $set["entrant1Score"];
                        $p2Score = $set["entrant2Score"];
                        $totalScore = $p1Score + $p2Score;
                        $p1Points = $p1Score / $totalScore;
                        $p2Points = $p2Score / $totalScore;
                        
                        //need to check for a few things here: is a player in my users table? is that player ranked? if not, how many ranked sets have they played?(a new player needs 5 ranked sets under their belt to become ranked)
                        //thus i need to add some columns to the users table: 
                        //initialSetsPlayed = 0 for unranked/new players. every ranked set they play, this number goes up by 1
                        //preRankedPoints = 0 for unranked/new players. goes up by the number of points they earn in a set. after 5 sets, the value of preRankedPoints is used to rank an unranked player
                        //to rank (give initial ELO) to an unranked player: take preRankedPoints for player after their fifth recorded ranked set and divide that by 7, call that value z. Player's initial ELO = 1300 + 1300*z.
                        //lowest initial ELO is 1300, highest possible is 2,228
                        
                        $p1Query = "SELECT isRanked FROM users WHERE id = $p1ID";
                        $p2Query = "SELECT isRanked FROM users WHERE id = $p2ID";
                        $p1IsRanked = $db -> query($p1Query);
                        $p2IsRanked = $db -> query($p1Query);
                        if ($p1IsRanked == 1)
                        {
                            $p1Query = "SELECT playerELO FROM users WHERE id = $p1ID";
                            $p1ELO = $db -> query($p1Query); //now he have p1 elo
                        }
  
                        if ($p2IsRanked == 1)
                        {
                            $p2Query = "SELECT playerELO FROM users WHERE id = $p2ID";
                            $p2ELO = $db -> query($p2Query); //now he have p1 elo
                        }

                         // need to include some code for if a player is not in the users database
                        if ($p1IsRanked == 1 && $p2IsRanked == 1) //if both players are ranked
                        {
                            $p1Query = "SELECT rankedSetsPlayed FROM users WHERE id = $p1ID";
                            $p2Query = "SELECT rankedSetsPlayed FROM users WHERE id = $p2ID";
                            $p1SetsPlayed = $db -> query($p1Query); //number of ranked sets p1's played
                            $p2SetsPlayed = $db -> query($p2Query); //numer of ranked sets p1's played
                            
                            if ($p1ELO >= 2300)
                            {
                                $p1K = 10;
                            }
                            else if ($p1ELO < 2300 && $p1ELO >= 1900)
                            {
                                $p1K = 20;
                            }
                            else if ($p1ELO < 1900 && $p1ELO >= 1500)
                            {
                                $p1K = 30;
                            }
                            else
                            {
                                $p1K = 40;
                            }
                            
                            if ($p2ELO >= 2300)
                            {
                                $p2K = 10;
                            }
                            else if ($p2ELO < 2300 && $p2ELO >= 1900)
                            {
                                $p2K = 20;
                            }
                            else if ($p2ELO < 1900 && $p2ELO >= 1500)
                            {
                                $p2K = 30;
                            }
                            else
                            {
                                $p2K = 40;
                            }
                            
                            if ($p1ELO >= $p2ELO)
                            {
                                $dp = $p1ELO - $p2ELO; //difference in ELO between the players
                                for ($i = 0; $i < 50; $i++)
                                {
                                    if ($dp <= $dps[$i] && $dp > $dps[$i+1])
                                    {
                                        $pi = $i / 100; //$i is the index
                                        $p1p = 1 - $pi;
                                        $p2p = 1 - $p1p;
                                    }
                                }
                            }
                            else 
                            {
                                $dp = $p2ELO - $p1ELO; //difference in ELO between the players
                                for ($i = 0; $i < 50; $i++)
                                {
                                    if ($dp <= $dps[$i] && $dp > $dps[$i+1])
                                    {
                                        $pi = $i / 100; //$i is the index
                                        $p2p = 1 - $pi;
                                        $p1p = 1 - $p2p;
                                    }
                                }
                            }
                            $deltap1 = ($p1K * ($p1Points - $p1p));
                            $deltap2 = ($p2K * ($p2Points - $p2p));
                            
                            $p1ELO += $deltap1;
                            $p2ELO += $deltap2;
                            
                            //now we need to use $p1ELO and $p2ELO and update our SQL table 
                            $p1Query = "UPDATE users SET playerELO = $p1ELO WHERE id = $p1ID";
                            $p2Query = "UPDATE users SET playerELO = $p1ELO WHERE id = $p2ID";
                            $db -> query($p1Query);
                            $db -> query($p2Query);
                             
                        }
                        
                    }
                }
                
            }
            
            $totalPages++;
        }
    }
    
?>