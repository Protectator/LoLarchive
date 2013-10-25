<?
require_once('includes/functions.include.php');

// Database connection
$pdo = newDBConnection();

// Securize inputs
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}

// Loads the header
require_once('includes/header.php');

// Load the content of the page requested
if (isset($_GET['page'])) {
	$page = "pages/".secure($_GET['page']).".php";
	try {
		if (!file_exists($page)) {
			throw new Exception ($page.' does not exist');
		} else {
		require_once($page);
		}
	} catch (Exception $e) {
		echo "Seems like you're lost. There's no page here.";
	}
} else {
	include('pages/search.php');
}

// Load the footer
include('includes/footer.php');

// Close the database connection
$pdo = null;
?>