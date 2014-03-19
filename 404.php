<?php
echoHeader("404 error - LoLarchive");
?>
<script>
(function(){$( "body" ).keypress(function( event ) {
  if ( event.which == 98 || event.which == 66 ) {
     event.preventDefault();
     window.location.replace("index.php");
  }
})})();
</script>
<div class="well">
	<h1>Error 404</h1>
	<div style="text-align:center;"><img src="img/teemoshroom.png"></div>
	<p class="lead error404flavourText">Seems like there's nothing interesting here. You should probably <a href="index.php" class="recallLink">back <img src="img/recall.png"></a></p>
</div>
<?php
echoFooter();
?>