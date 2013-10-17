<?php

	define('PATH', "/lolarchive/");

	define('API_KEY', "18d2e10ecf21b6e12fb81182fa4cf9f1718c873c");

	// Secure functions

	/**
	* Secures an user input before treating it.
	*
	* Converts a string to an integer if it is one.
	* If not, escapes apostrophes and other risky stuff.
	*/	
	function secure($string) {
		// On regarde si le type de string est un nombre entier (int)
		if(ctype_digit($string))
		{
			$string = intval($string);
		} else { // Pour tous les autres types
			$string = mysql_real_escape_string($string);
			$string = addcslashes($string, '%_');
		}
		return $string;
	}
	
	/**
	* "Purifies" text from HTML code beforedisplaying it.
	*/
	function purify($string) {
		return htmlspecialchars($string);
	}
	
	/**
	* Applies secure() on all values of an array and all subarrays.
	*/
	function secureArray(&$array) {
		/**
		* Don't use this outside.
		*/
		function secureArrayRec(&$input, $key) {
			if (is_string($input)) {$input = secure($input);}
		}
		array_walk_recursive($array, 'secureArrayRec');
	}
	
	// API functions
		
	/**
	* Gets summoner infos by name
	*
	* @param resource $c opened cURL session
	* @param string $region abbreviated server's name
	* @param string $name summoner's name to look for
	* @return string json containing the request
	*/
	function getSummonerByName(&$c, $region, $name) {
		$url = "http://legendaryapi.com/api/v1.0/".$region."/summoner/getSummonerByName/".$name;
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('authentication: '.API_KEY));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		return trim(curl_exec($c));
	}
	
	/**
	* Gets most recent games of a summoner
	*
	* @param resource $c opened cURL session
	* @param string $region abbreviated server's name
	* @param string $aId account Id to look for
	* @return string json containing the request
	*/
	function getRecentGames(&$c, $region, $aId) {
		$url = "http://legendaryapi.com/api/v1.0/".$region."/summoner/getRecentGames/".$aId;
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('authentication: '.API_KEY));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		return trim(curl_exec($c));
	}
	
	/**
	* Gets all public data of a summoner
	*
	* @param resource $c opened cURL session
	* @param string $region abbreviated server's name
	* @param string $aId account Id to look for
	* @return string json containing the request
	*/
	function getPublicData(&$c, $region, $aId) {
		$url = "http://legendaryapi.com/api/v1.0/".$region."/summoner/getAllPublicSummonerDataByAccount/".$aId;
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('authentication: '.API_KEY));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		return trim(curl_exec($c));
	}
	
	// Database functions
	
	function trackNewPlayer(&$c, $region, $name) {
		$json = getSummonerByName($c, $region, $name);
		$array = json_decode($json, true);
		$aId = $array['acctId'];
		// TODO : connect to database and add the user.
		$sId = $array['summonerId'];
	}
	
	// Index functions
	function item($row, $int) {
		if ($row['item'.$int] > 0) {
			return "<a href=\"http://www.lolking.net/items/".$row['item'.$int]."\"><img class= \"img-rounded imgitem32\" src=\"http://lkimg.zamimg.com/shared/riot/images/items/".$row['item'.$int]."_32.png\" alt=\"".$row['item'.$int]."\"></a>";
		}
	}
	
	function players($row, $region, $champsFolder, $champsId, $id) {
		$result = "";
		
		for ($i = 1; $i <= 5 ; $i++) {
		
			// check si user affiché = user de la page
			if ($row['a'.$i.'id'] == $id) {
				$row['a'.$i.'user'] = "<span class=\"selfUser\">".$row['a'.$i.'user']."</span>";
			} else if ($row['b'.$i.'id'] == $id) {
				$row['b'.$i.'user'] = "<span class=\"selfUser\">".$row['b'.$i.'user']."</span>";
			}
			
			// user A
			$result .= "<tr class=\"playerLine\">";
			if ($row['a'.$i.'id'] != "0") {
				$result = $result."<td class=\"littleChampIcon\"><img src=\"".$champsFolder.ucfirst($row['a'.$i.'champ']).".png\" class=\"littleChampIcon\" alt=\"".$row['a'.$i.'champ']."\"></td>";
				$result = $result.'<td class="littleSummonerName"><a href="index.php?page=player&amp;region='.$region.'&amp;id='.$row['a'.$i.'id']./*"#".$row['id'].*/'">'.$row['a'.$i.'user'].'</a></td>';
			} else {
				$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerName\"></td>";
			}
			
			// user B
			if ($row['b'.$i.'id'] != "0") {
			$result = $result."<td class=\"littleChampIcon\"><img src=\"".$champsFolder.ucfirst($row['b'.$i.'champ']).".png\" class=\"littleChampIcon\" alt=\"".$row['b'.$i.'champ']."\"></td>";
			$result = $result.'<td class="littleSummonerName"><a href="index.php?page=player&amp;region='.$region.'&amp;id='.$row['b'.$i.'id']./*"#".$row['id'].*/'">'.$row['b'.$i.'user'].'</a></td>';
			} else {
				$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerName\"></td>";
			}
			$result .= "</tr>";
		}
		return $result;
	}
	
	function items($row) {
		$result = "";
		for ($i = 1; $i <= 6; $i++) {
			if (($i-1) % 3 == 0) { $result.="<tr>"; }
			$result = $result."<td class=\"singleitemcell\">".item($row, $i)."<td>";
			if (($i) % 3 == 0) { $result.="</tr>"; }
		}
		return $result;
	}
	
	function timeOf($map, $mode, $ip, $win, $difficulty = "", $level = '30') {
		$dominion = 0.;
		$modifier = 1.;
		if ($mode == "NONE") {
			$modifier = 0.75;
		}
		
		$base = 16. + $win*2.; // Gain classique
		if ($win) {$ipminute = 2.312;} else {$ipminute = 1.405;} // gain/min classique
		
		switch ($map) {
		
			case '1': //              SUMMONER'S RIFT id map
				switch ($mode) {
				
					case "NORMAL": //    NORMAL
						$base = 16. + $win*2.;
						break;
					
					case "BOT":    //    COOP VS IA
						if ($difficulty == "INTERMEDIATE") {
							$base = 6. + $win * 1.;
						} else if ($difficulty == "EASY") {
							if ($win) {$base = 5.;} else {$base = 2.5;}
						}
						break;
						
					case "RANKED_SOLO_5x5":
					case "RANKED_DUO_5x5":
					case "RANKED_TEAM_5x5":
						// TODO : Floor le nombre de minutes a 65
						break;
				}
				break;
			case '10': // TWISTED TREELINE id map

				break;
			case "12": // ARAM id map
			
				break;
			case "dominion":
				if ($win) {$ipminute = 2.;} else {$ipminute = 2.;}
				$dominion = 1.;
				if ($win) {$base = 20.;} else {$base = 12.5;}
				break;
		}
		
		return ($ip - $dominion - $base) / ($ipminute * $modifier);
		
	}
	

	// Database connection
	$connect = mysql_connect("localhost", "lolk", "fnu");
	mysql_select_db("lolking", $connect) or die("erreur select db : " . mysql_error());
	
	// If we get parameters, securize them
	foreach ($_GET as &$thing) {
		$thing = secure($thing);
	}
?>