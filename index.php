<?
require_once('includes/functions.include.php');

// Database connection
$pdo = newDBConnection();

// Pages available
$pages = array(
	"'player'" => "pages/player.php",
	"'search'" => "pages/search.php"
);

// Securize inputs
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}

// Loads the header
require_once('includes/header.php');

// Load the content of the page requested
if (isset($_GET['page'])) {
	try {
		require_once($pages[$_GET['page']]);
	} catch (Exception $e) {
		echo "Seems like you're lost. There's no page here.";
	}
} else {
	require_once($pages["'search'"]);
}

// Load the footer
include('includes/footer.php');

// Close the database connection
$pdo = null;
?>