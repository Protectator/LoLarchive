<?
require_once('includes/functions.include.php');

require_once('includes/header.php');

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

include('includes/footer.php');?>