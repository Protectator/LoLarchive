<?php
echoHeader("404 error - LoLarchive");
?>
<div class="well">
	<h1>Error 404</h1>
	<p class="lead">Seems like there's nothing interesting here. You should probably go <a href="<?echo $_SERVER['HTTP_REFERER'];?>">back</a>.</p>
	<div style="text-align:center;"><img src="img/teemoshroom.png"></div>
</div>
<?php
echoFooter();
?>