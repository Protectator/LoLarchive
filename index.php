<?
require_once('header.php');

require_once('includes/functions.include.php');

if (isset($_GET['page'])) {
	$page = addcslashes($_GET['page'], '%_').".php";
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
	include('search.php');
}

include('footer.php');?>