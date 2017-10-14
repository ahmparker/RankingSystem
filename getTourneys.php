<?php

    //$servername = $_SERVER['REMOTE_ADDR']; //servername is the IP address of this c9 environment

    
    ('simple_html_dom.php');
    getSlugs();
    function getSlugs()
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
    
 
        
        $page = 1;
        $totalPages = 1;
        $slugArray;
        $slugCount = 0;
        for($page = 1; $page <= $totalPages; $page++)
        {
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
            foreach ($slugArray as $slug)
            {
                //echo $slug . "<br />";
                $query = "SELECT id FROM pastTournaments WHERE tourneySlug = '$slug'";
                //echo $query . "<br />";
                $result = $db -> query ($query);
                //echo $result . "<br />";
                $date = $dates[$slug];
                if(($result -> num_rows) == 0)  // program finds a tourney we haven't used
                {
                    
                    //$newQuery = "INSERT INTO pastTournaments (tourneySlug) VALUES ('$slug')";
                    $newQuery = "INSERT INTO pastTournaments (tourneySlug, endedAt) VALUES ('$slug', $date)";
                    //echo ("$newQuery <br />");
                    if ($db -> query($newQuery) == true);
                    {
                        echo ("queried! <br />");
                    }
                }    
            }

            $totalPages++;

        }
    }
    
?>