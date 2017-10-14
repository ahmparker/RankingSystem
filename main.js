
var json1;
var data = null;
var json2;
var slug = "Not Set"; //will be assigned
var eventCount = 0;
var howManySets = 0;

var totalEntrants = [];
var eventEntrants = [];//takes event as a paremeter, gets the number of entrants for that event. eventEntrants[0] on smashnest 14 should yield 93.
var events = [];
var setList = [];
var checkCounter = 0;

var namePointers = [];
var eventPointers = []; //event pointers, 0 being the first event etc
var pointer1 = []; //event id -> name pointers
var pointer2 = []; //phase id -> event id pointer
var pointer3 = []; //event id -> name pointer via players
var eventNames = [];
var eventIDs = [];
var groupIDs = [];
var entrantsPointerCounter = []; //pointer used to count the number of entrants in every event

var eventID = null;
var phaseID = null;
var groupID = null;
var meleeID = null;


function setPage()
{
    //slug = json.entities.tournament.slug;
    document.getElementById("test").innerHTML = "Here's your tourney's slug: " + slug;
    document.getElementById("test2").innerHTML = "There are " + json1.entities.event.length + " events, with " + totalEntrants.length + " entrants. ";
    document.getElementById("test3").innerHTML = "Melee Singles" + "'" + " Event ID : " + meleeID + " ";
    
}

function setSlug ()
{
    data = null;
    json1 = null;
    slug = "Not Set"; //clear json1, data, and slug info from last tourney
    var contentDIV = document.getElementById("content");
    while (contentDIV.firstChild) //remove all the child nodes of the 'content' div
    {
        contentDIV.removeChild(contentDIV.firstChild);
    }
    slug = document.getElementById("slugText").value;
    document.getElementById("test").innerHTML = "Here's your tourney's slug: " + slug;
    getJSON1(slug);
    alert(json1);
    createEntrants();
    createEvents();
    json1.entities.event.forEach(checkMelee);
    setPage();
    
    for (var i = 0; i < groupIDs.length ; i++)
    {
        data = groupIDs[i];
        getJSON2(data);
    }
}

function getJSON1(tourneySlug)
{

    $.ajax({ url: "getEvents.php",
             type: "GET",
             data: {tourneySlug},
             success: function(output)
             {
                 json1 = JSON.parse(output);
             },
             async: false //must be false, none of the other functions can work until this function has finished
        });
}

function getJSON2(phaseGroupID)
{
        json2 = null;
        $.ajax({url: "getSets.php",
                type: "GET",
                data: {phaseGroupID},
                success: function(output)
                {
                    json2 = JSON.parse(output);
                    addSets();

                },
                async: true //this takes FOREVER if false. fair warning
           });
}


function parseJSON(rawjson)
{
    
    
}

function createEntrants()
{
    for (var i = 0; i < json1.entities.entrants.length; i++)
    {
        totalEntrants[i] = json1.entities.entrants[i].id;
        namePointers[i] = json1.entities.entrants[i].name; //connect the total entrants to name
    }
}
function createEvents ()
{
    for (var i = 0; i < json1.entities.event.length; i++) //creates events
    {
        var event_name = json1.entities.event[i].name;
        eventID = json1.entities.event[i].id
        eventPointers[i] = eventID; //0th slot has first event's ID
        pointer1[eventID] = event_name;
        var ID1 = document.createElement("div");
        ID1.id = eventID;
        ID1.className = "eventInfo";
        eventCount++;
        eventNames[i] = event_name;
        eventIDs[i] = eventID;
        makeDiv(eventID);
        document.getElementById(eventID).appendChild(ID1);
         //sets our event IDs and links them to a na
    }
    for (var j = 0; j < json1.entities.phase.length; j++) //creates phases
    {
        var tempID = json1.entities.phase[j].eventId; //the id we're trying to match
        var phaseName = json1.entities.phase[j].name;
        phaseID = json1.entities.phase[j].id; //gets the id of the phase
        pointer2[phaseID] = tempID; //sets the pointer for the event id via the phase id
        var eventID1 = pointer1[tempID]; //sets event to the event name using tempID as a pointer
        var ID2 = document.createElement("div");
        var title = document.createElement("div");
        title.className = "phaseTitle";
        title.id = phaseID;
        title.innerHTML = phaseName;
        ID2.id = phaseID;
        ID2.className = "phase";
        //ID2.innerHTML = "Phase: " + phaseName;
        document.getElementById(tempID).appendChild(ID2);
        document.getElementById(phaseID).appendChild(title);
       
    }
    for (var k = 0; k < json1.entities.groups.length; k++)//creates the groups
    {
        var tempPhaseID = json1.entities.groups[k].phaseId; //gets the id of the phase
        groupID = json1.entities.groups[k].id; //the id we're trying to match
        groupIDs[k] =  groupID;
        var event = pointer2[tempPhaseID]; //sets event to the event name using tempID as a pointer
        var groupName = json1.entities.groups[k].displayIdentifier;
        var group = document.createElement("div");
        var groupTitle = document.createElement("div");
        var allSets = document.createElement("div");
        
        allSets.id = "allSets" + groupID;
        groupTitle.id = groupID;
        group.id = groupID;
        
        groupTitle.className = "groupTitle";
        group.className = "group";
        allSets.className = "allSets";
        groupTitle.innerHTML = "Group: " + groupName;
        groupTitle.setAttribute("onclick", "toggleGroups(this)")
        
        document.getElementById(tempPhaseID).appendChild(group); //adds the group to the phase
        document.getElementById(groupID).appendChild(groupTitle); //adds the title on top 
        document.getElementById(groupID).appendChild(allSets);
        
    }
    for (var p = 0; p < json1.entities.event.length; p++)//set all values of entrants per event array to 0
    {
        eventEntrants[p] = 0;
    }
    for (var u = 0; u <= json1.entities.entrants.length; u++)//go through every entrant in the tourney
    {
        if (u < json1.entities.entrants.length)
        {
            var event2 = json1.entities.entrants[u].eventId; //gets the event the player participated in
            var pointah = eventPointers.indexOf(event2);
            var old = eventEntrants[pointah];
            eventEntrants[pointah] = old + 1;
        }
        else
        {
            for (var z = 0; z < json1.entities.event.length; z++)
            {
                var ID4 = document.createElement("div"); //z's gonna be our event Pointer
                var eventID3 = eventPointers[z];
                ID4.id = eventID3 + " entrants";
                ID4.innerHTML = "(Entrants : " + eventEntrants[z] + ")";
                document.getElementById(eventID3).appendChild(ID4);
            }
        }

    }
}

function checkMelee ()
{
    for (var i = 0; i < json1.entities.event.length; i++)
    {
        if (json1.entities.event[i].name == "Melee Singles")
        {
            meleeID = json1.entities.event[checkCounter].id;
        }
    }
}
function makeDiv(ID)
{
   
    var div1 = document.createElement("div");
    var point = eventIDs.indexOf(ID); //finds the pointer for the event name
    var TEMPID = ID;
    var name = eventNames[point];
    div1.className = "eventFrame";
    div1.id = TEMPID;
    div1.style.background = "white";
    div1.style.color = "black";
    document.getElementById("content").appendChild(div1);
    
    var div = document.createElement("div");
    div.id = TEMPID;
    div.className = "event";
    div.style.background = "white";
    div.style.color = "black";
    div.innerHTML = name;
    document.getElementById(TEMPID).appendChild(div);
 
   
   // document.getElementsByClassName(blah).appendChild(div);
}
function addSets()
{
    //howManySets = json2.entities.sets.length;
    
    for (var i = 0; i < json2.entities.sets.length; i++)
    {
        var phaseSets = json2.entities.sets[i] //sets in the phase
        var setsPhaseID = json2.entities.sets[i].phaseGroupId; //phase ID via the SET
        var setID = json2.entities.sets[i].id;
        var play1 = json2.entities.sets[i].entrant1Id;
        var play2 = json2.entities.sets[i].entrant2Id;
        var p1Score = json2.entities.sets[i].entrant1Score;
        var p2Score = json2.entities.sets[i].entrant2Score;
        var totalScore = p1Score + p2Score;
        var Name = json2.entities.sets[i].fullRoundText;
        var winner = json2.entities.sets[i].winnerId; //gives us the winner of the set in the form of a playID (ie either play1 or play2)
        
        var loserName = document.createElement("div"); //div for our loserName div and so on
        var winnerName = document.createElement("div");
        var p1Points;
        var p2Points;
        var p1OldRank = "p1OldRank"; var p2OldRank = "p2OldRank" //these values will get pulled from our sql table. make sure to check if players are ranked or not!
        var p1K = "p1K"; var p2K = "p2K"; //these will be changed later in the function
        var setName = document.createElement("div");
        var container = document.createElement("div");
        var p1 = document.createElement("div");
        var p2 = document.createElement("div");
        var versus = document.createElement("div");
        var p1Change = document.createElement("div");
        var p2Change = document.createElement("div");
        
        loserName.className = "loser";
        setName.className = "setTitle";
        setName.id = setID;
        p1.className = "Plyr1";
        p2.className = "Plyr2";
        versus.className = "versus";
        container.className = "setContainer";
        container.id = setID;
        p1.id = play1;
        p2.id = play2;
        
       // div.setAttribute("player", "Plyr1");
       // p2.setAttribute("player", "Plyr2");
        p1.setAttribute("playerID", play1);
        p2.setAttribute("playerID", play2);
        
        if(p1Score < 0)
        {
            p1Score = "DQ";        
        }
        if(p2Score < 0)
        {
            p2Score = "DQ";        
        }
        var pi3 = totalEntrants.indexOf(play1);
        var pi4 = totalEntrants.indexOf(play2);
        var p1title = namePointers[pi3];
        var p2title = namePointers[pi4];
        if (play1 != null && play2 != null) //smash.gg does weird things to make byes work, including having players win imaginary games against "null" id'd players. if either one is null, we have a bye.
        {
            p1Points = p1Score / totalScore;
            p2Points = p2Score / totalScore;
            
            p1Points = p1Points.toFixed(2);
            p2Points = p2Points.toFixed(2);
            
            //this is the part where we will change the values in our sql table
            //we have the user ID and whatnot
            if (winner == play1)
            {
                p1.innerHTML = p1title + " /  (W)  / " + " " + p1Points + "pts";
                p2.innerHTML = p2title + " /  (L)  / " + " " + p2Points + "pts";
            }
            else
            {
                p1.innerHTML = p1title + " L " + p1Score + ": " + p1Points + "pts";
                p2.innerHTML = p2title + " W " + p2Score + ": " + p2Points + "pts";
            }
            
            p1Change.innerHTML = "p1's Points: " + p1Points;
            p2Change.innerHTML = "p2's Points: " + p2Points;
            versus.innerHTML = " vs ";
            setName.innerHTML = Name;
            setList[i] = setID; //and this is how ya connect kids
    
            var cl = document.createElement("div"); //clear left
            var cr = document.createElement("div"); //clear right
            cl.className = "clearLeft";
            cr.className = "clearRight";
                
            document.getElementById("allSets" + setsPhaseID).appendChild(container);
            document.getElementById(setID).appendChild(setName);
            document.getElementById(setID).appendChild(p1);
            //document.getElementById(setID).appendChild(p1Change);
            //document.getElementById(setID).appendChild(cl);
            document.getElementById(setID).appendChild(versus);
            document.getElementById(setID).appendChild(p2);
            //document.getElementById(setID).appendChild(p2Change);
            //document.getElementById(setID).appendChild(cr);
            
        }
    }
   
}
function toggleGroups (el) //lotta tourneys.
{
    var id = $(el).attr("id");
    id = "allSets" + id;
    
    if (document.getElementById(id).style.display == "block")
    {
    document.getElementById(id).style.display = "none";
    }
    else
    {
        document.getElementById(id).style.display = "block";
    }
    //document.getElementById(id);
    //make another div for the setContainers called allSets. change the display of this div to hide all the sets. GENIUS
   
    
}
window.onload = function()
{

}
