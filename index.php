<?php
require "lib/chromephp/ChromePhp.php";

require "src/Venue.php";
require "src/Review.php";
require "src/DatabaseManager.php";

error_reporting(E_ALL);
ini_set('display_errors', true);

session_start();
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Delve Into Derby</title>
		<link rel="icon" href="img/favicon.png">
        <link rel="stylesheet" href="http://www.w3schools.com/lib/w3.css">
        <link rel="stylesheet" href="stylesheet.css">
		<script src="js/index.js"></script>
    </head>
    <body>
		<?php

			$databaseManager = "N/A";
			$venueType;
			$venueID;
			$venueList;

			// $_SESSION = array(); // Clear session

			// ChromePhp::log("_POST contents (" . count($_POST) . " values):\n" . var_export($_POST, true));
			ChromePhp::log("Ready to load session. _SESSION contains " . count($_SESSION) . " values.");

			// Check for changes in venue type
			if (isset($_POST["t"])) {
				$last = "N/A";

				if (isset($_SESSION["venueType"])) {
					$last = $_SESSION["venueType"];
				}

				ChromePhp::log("POST typeID: " . $_POST["t"]);
				$venueType = $_POST["t"];
				unset($_POST["t"]);

				if ($last == "N/A" || $venueType != $last) {
					unset($_SESSION["venueID"]); // Old venue ID is wrong
					unset($_SESSION["venueList"]); // Venues need re-loading
				}
			}
			else {
				if (isset($_SESSION["venueType"])) {
					$venueType = $_SESSION["venueType"];
					ChromePhp::log("Using session venueType ($venueType).");
				}
				else {
					ChromePhp::log("No type stored - defaulting to 'R'.");
					$venueType = "R";
				}
			}

			// Check for change of venue
			if (isset($_POST["v"])) {
				$venueID = $_POST["v"];
				unset($_POST["v"]);
				ChromePhp::log("POST venueID: " . $venueID);
			}
			else {
				if (isset($_SESSION["venueID"])) {
					$venueID = $_SESSION["venueID"];
					ChromePhp::log("Using session venueID ($venueID).");
				}
				else {
					ChromePhp::log("No venueID POSTed or stored - defaulting to type default.");
					$venueID = getDefaultVenue($venueType);
				}
			}

			// Check / initialise the database manager

			if (isset($_SESSION["databaseManager"])) {
				$databaseManager = $_SESSION["databaseManager"];
				ChromePhp::log("Using session DatabaseManager.");
			}
			else {
				$databaseManager = new DatabaseManager();
				ChromePhp::log("No DatabaseManager in session - created new instance.");
			}
			
			// Check the venue list

			if (isset($_SESSION["venueList"])) {
				ChromePhp::log("Using session venue list.");
				$venueList = $_SESSION["venueList"];
			}
			else {
				ChromePhp::log("No venue list in session - re-loading.");
				$venueList = $databaseManager->loadVenues($venueType);
			}
			
			createVenueList($venueList, $venueType, $venueID);
			// createMap($venueList[$venueID]);
			
			// Disconnect from the database
			$databaseManager->disconnect();
			ChromePhp::log("Disconnected from the database.");

			// Save the session
			ChromePhp::log("Saving session...");
			$_SESSION["databaseManager"] = $databaseManager;
			$_SESSION["venueType"] = $venueType;
			$_SESSION["venueID"] = $venueID;
			$_SESSION["venueList"] = $venueList;
			session_write_close();
			ChromePhp::log("Session saved.");

			//

			/**
			 * Loads and displays the Google Maps map.
			 **/
			function createMap($venue) {
				PhpConsole::log("Querying maps for '" . $venue->getName() . "'.");
				$query = str_replace(' ', '+', $venue->getName() . " Derby"); // Should do it
				PhpConsole::log("Querying maps: $query");

				echo '<iframe width="600" height="450"
					  frameborder="0" style="border:0"
					  src="https://www.google.com/maps/embed/v1/place?key=AIzaSyCe4BB7jH_lBh7vMj0xJrvh8vivkhJNwj0
						   &q=' . $query . '">
					  </iframe>';
			}

			/**
			 * Creates the left-side venue list.
			 **/
			function createVenueList($venueList, $venueType, $venueID) {
				ChromePhp::log("Creating venue list.");

				echo '<nav class="w3-sidenav w3-light-grey" onmouseout="closeVenueList()" style="width:25%">
					  <div class="w3-container w3-section">
					  <div class="w3-container">
						<li class="w3-dropdown-hover w3-light-grey">
						<h3><a href="#">' . getVenueTypeName($venueType) . 's</a></h3>
						<div class="w3-dropdown-content">
						  <a href="#" onclick="swapVenueType(\'R\')">Restaurants</a>
						  <a href="#" onclick="swapVenueType(\'C\')">Cinemas</a>
						  <a href="#" onclick="swapVenueType(\'M\')">Museums</a>
						</div>
					  </div>';
					  
				echo '<ul href="#" class="w3-ul w3-hoverable w3-container w3-section">';
				
				ChromePhp::log("Looping through " . count($venueList) . " venues.");

				// Add each venue to the list
				for ($i = 0; $i != count($venueList); $i++) {
					$id = $venueList[$i]->getID();
					$name = $venueList[$i]->getName();
					$address = $venueList[$i]->getAddress();
					$postcode = $venueList[$i]->getPostcode();
					$rating = $venueList[$i]->getAverageRating();
					
					// ChromePhp::log("Creating list entry for $i/" . count($venueList) . ": '" . $name . "'");
				
					if ($id == $venueID) {
						echo '<li class="w3-blue">';
					}
					else {
						echo '<li>';
					}
					echo '<a href="#" onclick="swapVenue(\'' . $id . '\')" style="fill_div" class="w3-hover-none w3-hover-text-white" >';
					echo '<span class="w3-large">';
					echo '<img src="img/rating/' . $rating . '.png" class="w3-right" style="width:25%">';
					echo "$name</span><br>";
					echo "<span>$address, Derby, $postcode</span>";
					echo '</li>';
				}
				echo '</ul>
					  </div>
					  </nav>';
			}

			function getVenueTypeName($venueType) {
				switch($venueType) {
					case "R":
						return "Restaurant";

					case "C":
						return "Cinema";

					case "M":
						return "Museum";
				}
			}
			
			/**
			 * Returns the default venue ID for this type (who needs OCP...)
			 **/
			function getDefaultVenue($venueType) {
				switch($venueType) {
					case "R":
						return 0;
				
					case "C":
						return 20;

					case "M":
						return 24;
				}
				return -1;
			}
		?>
    </body>
</html>