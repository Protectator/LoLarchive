<?php

/*	LoLarchive - Website to keep track of your games in League of Legends
    Copyright (C) 2013-2014  Kewin Dousse (Protectator)

    This file is part of LoLarchive.

    LoLarchive is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or any later version.

    LoLarchive is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Contact : kewin.d@websud.ch
    Project's repository : https://github.com/Protectator/LoLarchive
*/

// Counting the time required to create the page
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$startTime = $mtime;
// This page loads every other page asked
// It's the one an end-user always asks for.

// Start by loading the config file
require_once('private/config.php');

// Then all the useful functions
require_once('includes/functions.include.php');

// Open the database connection
$pdo = newDBConnection();

// Pages available
$pages = array(
	"player" => "pages/player.php",
	"search" => "pages/search.php",
	"404" => "404.php"
);

// Load the content of the page requested
if (isset($_GET['page'])) {
	if (array_key_exists($_GET['page'], $pages)) {
		try {
			require_once($pages[$_GET['page']]);
		} catch (Exception $e) {
			echo "An error occured during the generation of this page. <br>Seems like you're lost. There's nothing here."/*even you, you are nothing here. Just get a life.*/;
			if (isset($_GET['debug'])) {
				echo "<br>".$e->getMessage();
			}
		}
	} else {
		require_once($pages['404']);
	}
} else {
	require_once($pages["search"]);
}

// Stop counting the time required and display it in comments
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endTime = $mtime;
$totalTime = ($endTime - $startTime);
echo "<!-- This page was created in ".$totalTime." seconds. -->";

// Close the database connection
$pdo = null;
?>