<?php
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