<?php
/*********************************************************************
    display_open_topics.php

    Displays a block of the last X number of open tickets.

    Neil Tozier <tmib@tmib.net>
    Copyright (c)  2010-2017
    For use with osTicket version 1.10+ (http://www.osticket.com)

	This release was tested with both 1.10 and 1.10.1
	This version was origianlly rewritten for PHP5.6 and then 7.0.
	Backwards compatibility in comments is from the last version of this script.
	
    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See osTickets's LICENSE.TXT for details.
**********************************************************************/

// EDIT THESE!!!
// The maximum amount of open tickets that you want to display.
$limit ='10';  
// OPTIONAL: if you know the id for Open in your database put it here.  If you do not you can look 
// it up in DBPREFIX_ticktet_status, or this script will look it up for you.  This is 1 in all my installations.
// changing this to a number will save you a SQL query. If you are running 1.8 or prior change this to "open".
// NOTE: Backwards compatibility has not been tested.
$openid = '1';
// If you are running 1.8 or prior change this to "status".  If you are running 1.9 or 1.10 change this to "status_id"
// NOTE: Backwards compatibility has not been tested.
$status = 'status_id';
// The columns that you want to collect data for from the db
// Please note that due to db structure changes in 1.8.x you can only include columns from the ost_ticket table
// and this script does not handle custom fields at this time.
$columns = "ticket_id, user_id, created, updated";

// OPTIONAL: just in case we cant see table prefix. (this should just work)
if (null !== TABLE_PREFIX) {
  define('TABLE_PREFIX','ost_');
}

  // get Open id (which is usually 1)
if(empty($openid)) { 
  $opensql = "SELECT id FROM ".TABLE_PREFIX."ticket_status WHERE name='Open'";
  $openresult = db_query($opensql);
  $openid = db_result($openresult,0,"id");
}

// DB connect and query.  Get the columns that were specified.
$res = db_query("SELECT $columns
			 FROM ".TABLE_PREFIX."ticket
			 WHERE $status = $openid
			 ORDER BY created DESC
			 LIMIT 0,$limit");

// declare a variable before we use it
$events = array();

// figure out how many rows
$num = db_num_rows($res);

// Display logic (catch no open tickets and display a message
if (db_num_rows($res) == '0') {
	echo "<p style='text-align:center;'><span id='msg_warning'>There are no tickets open at this time.</span></p>";
}
// Display details and get more information
else {
	// start the display
	echo "<table border-color=#BFBFBF border=0 cell-spacing=2><tr style='background-color: #BFBFBF;'>";
	echo "<td id='openticks-a' style='min-width:150px;'><b>Name</b></td><td id='openticks-a' style='min-width:250px;'><b>Issue</b></td><td id='openticks-a' style='min-width:150px;'><b>Opened on</b></td><td id='openticks-b' style='min-width:150px;'><b>Last Update</b></td></tr>";

	while ($row = db_fetch_row($res)) $events[] = $row[0];
		$i = '0';
		foreach ($events as $e) {

			$opensql = "SELECT user_id FROM ".TABLE_PREFIX."ticket WHERE ticket_id=".$e;
			$openresult = db_query($opensql);
			$user_id = db_result($openresult,0,"ticket_id");
		
			$opensql = "SELECT name FROM ".TABLE_PREFIX."user WHERE id=".$user_id;
			$openresult = db_query($opensql);
			$username = db_result($openresult,0,"name");
		
			$opensql = "SELECT created FROM ".TABLE_PREFIX."ticket WHERE ticket_id=".$e;
			$openresult = db_query($opensql);
			$created = db_result($openresult,0,"created");
		
			$opensql = "SELECT updated FROM ".TABLE_PREFIX."ticket WHERE ticket_id=".$e;
			$openresult = db_query($opensql);
			$updated = db_result($openresult,0,"updated");

			// if ticket not updated yet gracefully say so
			if ($updated == '0000-00-00 00:00:00') {
				$updated = 'not updated yet';
			}
 
			// look up internal form id
			$entryIdsql = "SELECT id,form_id FROM ".TABLE_PREFIX."form_entry WHERE object_id=$e LIMIT 1";
			$entryIdresult = db_query($entryIdsql);
			$entry_id = db_result($entryIdresult,0,"id");
			//echo "entry_id: ".$entry_id."<br>";  // uncomment to debug
  
			// get subject
			$subjectsql = "SELECT value FROM ".TABLE_PREFIX."form_entry_values WHERE entry_id=$entry_id and field_id=5";
			$subjectresult = db_query($subjectsql);
			$subject = db_result($subjectresult,0,"value");
  
			// get priority
			$prioritysql = "SELECT value FROM ".TABLE_PREFIX."form_entry_values WHERE entry_id=$entry_id and field_id=7";
			$priorityresult = db_query($prioritysql);
			$priority = db_result($priorityresult,0,"value");
			
			if(is_null($priority)) {
				$priority = 'Normal';
			}

			// change row back ground color to make more readable
			if(($i % 2) == 1)  //odd
				{$bgcolour = '#F6F6F6';}
			else   //even
				{$bgcolour = '#FEFEFE';}
 
			//populate the table with data
			echo "<tr align=center><td BGCOLOR=$bgcolour id='openticks-a' nowrap style='min-width:150px;'> &nbsp; ".$username." &nbsp; </td>"
			."<td BGCOLOR=$bgcolour id='openticks-a' style='min-width:200px;'> &nbsp; ".$subject." &nbsp; </td>"
			."<td BGCOLOR=$bgcolour id='openticks-a'> ".$created." </td>"
			."<td BGCOLOR=$bgcolour id='openticks-b'> ".$updated." </td></tr>";
	
			//echo "priority: ".$priority."<br>";  // uncomment to debug
			//echo "user_id: ".$user_id."<br>";  // uncomment to debug
			//echo "name: ".$username."<br>";  // uncomment to debug
			//echo "subject: ".$subject."<br>";  // uncomment to debug
			//echo "created: ".$created."<br>";  // uncomment to debug
			//echo "updated: ".$updated."<br>";  // uncomment to debug
			
			$i++;
		}
	echo "</table>";
}
?>