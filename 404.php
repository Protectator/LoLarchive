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