<?php

	define('PATH', "/lolarchive/"); // root of the LoLarchive directory
	define('LOCAL', "/var/www");    // local directory of the LoLarchive directory

	// Start by loading variables that shouldn't be public
	require_once(LOCAL.PATH.'private/config.php');
	
	// Useful variables
	
	// Number to name of month and reverse
	$months = array (
			"Jan" => '01',
			"Feb" => '02',
			"Mar" => '03',
			"Apr" => '04',
			"May" => '05',
			"Jun" => '06',
			"Jul" => '07',
			"Aug" => '08',
			"Sep" => '09',
			"Oct" => '10',
			"Nov" => '11',
			"Dec" => '12'
	);
	foreach($months as $key => $value) { // "Reverse" the array so it can be accessed by numbers of months too
		$months[intval($value)] = $key;
	}
	
	// Display text of game modes
	$modes = array (
			"NONE" => "Custom",
			"NORMAL" => "Normal 5v5",
			"RANKED_SOLO_5x5" => "Ranked Solo 5v5",
			"RANKED_TEAM_5x5" => "Ranked Team 5v5",
			"RANKED_TEAM_3x3" => "Ranked Team 3v3",
			"BOT" => "Co-op vs AI 5v5",
			"BOT_3x3" => "Co-op vs AI 3v3",
			"ARAM_UNRANKED_5x5" => "ARAM",
			"ODIN_UNRANKED" => "Dominion"
	);
	
	/*
	DATABASE CONNECTION FUNCTIONS
	*/
	
	/**
	* Creates a new connection to the database using PDO
	*
	* @return resource PDO object with opened connection
	*/
	function newDBConnection() {
		try
		{
			$return = new PDO('mysql:host='.HOST.';dbname='.DBNAME, USERNAME, PASSWORD);
			$return->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $return;
		}
		catch(Exception $e)
		{
			echo 'Failed to connect to database:<br />';
			echo 'Error : '.$e->getMessage().'<br />';
			echo 'N° : '.$e->getCode();
			exit();
		}
	}
	
	/**
	* Commits one or multiple queries at once.
	*
	* Use this to do INSERT, DELETE or UPDATE queries.
	* Commits all requests in the order they are read -> The order of $queries array
	*
	* @param resource $pdo opened PDO connection
	* @param array|string $queries String or Array of Strings containing the 
	* SQL queries to perform at once.
	* @return array Array of two integers :
	* 	- "1" if the request succeed, else "0"
	*	- Number of affected rows
	*/
	function securedInsert(&$pdo, $queries) {
		try
		{
			$count = 0;
			$result = $pdo->beginTransaction();
			if (is_string($queries)) {
				$cQuery = $pdo->query($queries);
				$count += $cQuery->rowCount();
			} else {
				foreach($queries as $currentQuery) {
					$cQuery = $pdo->query($currentQuery);
					$count += $cQuery->rowCount();
				}
			}
			return array($pdo->commit(), $count);
		}
		catch(Exception $e)
		{
			$pdo->rollback();
			echo 'Error while committing queries (securedInsert) :<br />Query:';
			if (is_string($queries)) {
				echo $queries;
			} else {
				foreach($queries as $currentQuery) {
					echo $currentQuery."<br>";
				}
			}
			echo 'Error : '.$e->getMessage().'<br />';
			echo 'N° : '.$e->getCode();
			exit();
		}
	}
	
	/**
	* Performs a raw (untreated and unsecured) SELECT query.
	*
	* @param resource $pdo opened PDO connection
	* @param string $query Query to perform
	* @return Result of the query
	*/
	function rawSelect(&$pdo, $req) {
		try {
			return $pdo->query($req);
		}
		catch (Exception $e) {
			echo 'Error while committing query :<br />';
			echo 'Error : '.$e->getMessage().'<br />';
			echo 'N° : '.$e->getCode();
			exit();
		}
	}
	
	// BUILDING QUERIES FUNCTIONS
	
	/**
	* Creates a string made of passed conditions
	*
	* Only keys of the array are used to make the result. It has been made to
	* be later used as a parameter to PDO bindParam. Resutrns something of the
	* form :
	* "key1 = :key1 AND key2 = :key2 AND key3 = :key3"
	*
	* @param array of conditions and values
	*/
	function conditions($columns) {
		$conditions = array();
		foreach ($columns as $key => $value) {
			$conditions[] = $key." = :".$key;
		}
		return implode(" AND ", $conditions);
	}
	
		
	/**
	* Prepares a chunk of string designed to be added with other parts of a SQL INSERT
	*
	* @param $columns Array containing keys and values to add to the SQL INSERT
	* @return string keys and values to be inserted
	*/
	function buildInsert($columns) {
		return "(".implode(", ", array_keys($columns)).") VALUES ('".implode("', '", array_values($columns))."')";
	}
	
	/**
	* Prepares a chunk of string designed to be added with other parts of a SQL INSERT
	* 
	* @param $columns Array containing arrays of keys and values to add to the SQL INSERT
	* @return string keys and values to be inserted
	*/
	function buildMultInsert($arrayOfColumns) {
		$result = "(".implode(", ", array_keys($arrayOfColumns[0])).") VALUES ";
		$columns = array();
		foreach($arrayOfColumns as $column) {
			$columns[] = "('".implode("', '", array_values($column))."')";
		}
		$result .= implode(", ", $columns);
		return $result;
	}

	/*
	SECURITY FUNCTIONS
	*/

	/**
	* Secures an user input before treating it.
	*
	* Converts a string to an integer if it is one.
	* If not, escapes apostrophes and other risky stuff.
	*
	* @param resource $pdo Opened PDO connection
	* @param string $string string to secure
	* @return secured string
	*/	
	function secure(&$pdo, $string) {
		// On regarde si le type de string est un nombre entier (int)
		if(ctype_digit($string))
		{
			$string = intval($string);
		} else { // Pour tous les autres types
			$string = $pdo->quote($string);
			$string = addcslashes($string, '%_');
		}
		return $string;
	}
	
	/**
	* "Purifies" text from HTML code before displaying it.
	* 
	* @param string $string String to be displayed
	* @return string String without impact on html etc
	*/
	function purify($string) {
		return htmlspecialchars($string);
	}
	
	/**
	* Applies secure() on all values of an array and all subarrays.
	*
	* @param resource $pdo Opened PDO connection
	* @param resource
	*/
	function secureArray(&$pdo, &$array) {
		/**
		* Don't use this outside.
		*/
		function secureArrayRec(&$input, $key) {
			if (is_string($input)) {$input = secure($pdo, $input);}
		}
		array_walk_recursive($array, 'secureArrayRec');
	}
	
	/*
	API FUNCTIONS
	*/
		
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
	
	/*
	HIGHER LEVEL FUNCTIONS
	*/
	
	/**
	* Adds a summoner to track games
	*
	* @param resource $pdo Opened PDO connection
	* @param resource $c Opened cURL conneciton
	* @param string $region Region of the summoner account
	* @param string $name Summoner name
	* @return int Should return "1" if the request was executed correctly.
	* Does not guarantee the summoner has effectively been tracked, though.
	*/
	function trackNewPlayer(&$pdo, &$c, $region, $name) {
		$json = getSummonerByName($c, $region, $name);
		$jsonArray = json_decode($json, true);
		$aId = $jsonArray['acctId'];
		$sId = $jsonArray['summonerId'];
		$infos = array (
			"region" => $region,
			"summonerId" => $sId,
			"accountId" => $aId,
			"name" => $name
		);
		$request = "INSERT INTO usersToTrack ".buildInsert($infos)." ON DUPLICATE KEY UPDATE name = '".$name."';";
		return securedInsert($pdo, $request); // Returns the number of affected rows
	}
	
	/*
	LOWER LEVEL FUNCTIONS
	undocumented part
	*/
	
	/*
	* Returns the HTML showing an item
	*/
	function item($row, $int) {
		if ($row['ITEM'.$int] > 0) {
			return "<a href=\"http://www.lolking.net/items/".$row['ITEM'.$int]."\"><img class= \"img-rounded imgitem32\" src=\"http://lkimg.zamimg.com/shared/riot/images/items/".$row['ITEM'.$int]."_32.png\" alt=\"".$row['ITEM'.$int]."\"></a>";
		}
	}
	
	/*
	* Returns the image link of a champion
	*/
	function champImg($champId, $champsName) {
		return PATH."img/champions/".ucfirst($champsName[$champId]).".png";
	}
	
	/*
	* Creates HTML text showing players in a game.
	*/
	function players($teamL, $teamR, $region, $id, $champsName) {
		$result = "";
		// Line per line
		for ($i = 0; $i <= 4 ; $i++) {
			$result .= "<tr class=\"playerLine\">";
			// Left team member
			if (isset($teamL[$i])) {
				if ($teamL[$i]['user'] != "") {
					$displayText = $teamL[$i]['user'];
					$displayClass = "littleSummonerLinkName";
				} else {
					$displayText = $teamL[$i]['summonerId'];
					$displayClass = "littleSummonerLinkId";
				}
				$result .= "<td class=\"littleChampIcon\"><img src=\"".champImg(intval($teamL[$i]['championId']), $champsName)."\" class=\"littleChampIcon\" alt=\"".$teamL[$i]['championId']."\"></td>";
				$result .= '<td class="'.$displayClass.'"><a href="'.PATH.'index.php?page=player&amp;region='.$region.'&amp;id='.$teamL[$i]['summonerId'].'">'.$displayText.'</a></td>';
			} else {
				$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerLinkName\"></td>";
			}
			// Right team member
			if (isset($teamR[$i])) {
				if ($teamR[$i]['user'] != "") {
					$displayText = $teamR[$i]['user'];
					$displayClass = "littleSummonerLinkName";
				} else {
					$displayText = $teamR[$i]['summonerId'];
					$displayClass = "littleSummonerLinkId";
				}
				$result .= "<td class=\"littleChampIcon\"><img src=\"".champImg(intval($teamR[$i]['championId']), $champsName)."\" class=\"littleChampIcon\" alt=\"".$teamR[$i]['championId']."\"></td>";
				$result .= '<td class="'.$displayClass.'"><a href="'.PATH.'index.php?page=player&amp;region='.$region.'&amp;id='.$teamR[$i]['summonerId'].'">'.$displayText.'</a></td>';
			} else {
				$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerLinkName\"></td>";
			}
			$result .= "</tr>";
		}
		return $result;
	}
	
	function items($row) {
		$result = "";
		for ($i = 0; $i <= 5; $i++) {
			if (($i) % 3 == 0) { $result.="<tr>"; }
			$result = $result."<td class=\"singleitemcell\">".item($row, $i)."<td>";
			if (($i+1) % 3 == 0) { $result.="</tr>"; }
		}
		return $result;
	}
	
	/**
	* Estimates the duration of a game
	*
	* I insist on the word ESTIMATION.
	* Made from the rules listed on http://leagueoflegends.wikia.com/wiki/Influence_Points
	*
	* @param int $map ID of the map played on
	* @param string $mode Mode played
	* @param int $ip amount of won ip
	* @param int $win 1 if game was won, 0 otherwise
	* @param string $difficulty Level of difficulty if played against bots
	* @param int $level Summoner's level that played the game
	* @return int Estimated duration of the game
	*/
	function timeOf($map, $mode, $ip, $win, $difficulty = "", $level = 30) {
		$dominion = 0.;
		$modifier = 1.;
		switch ($map) { // IP/minute gains depends mainly on the map played on
			case '1': // Summoner's rift
				if ($win) {$ipminute = 2.312;} else {$ipminute = 1.405;}
				break;
			case '8': // Dominion
				if ($win) {$ipminute = 2.312;} else {$ipminute = 1.405;}
				$dominion = 1.;
				break;
			case '10': // Twisted Treeline
				if ($win) {$ipminute = 2.;} else {$ipminute = 1.;}
				break;
			case "12": // ARAM
				if ($win) {$ipminute = 2.312;} else {$ipminute = 1.405;}
				break;
		}
		
		switch ($mode) { // Base IP gain depends on the mode played
			case 'ODIN_UNRANKED': // Dominion
				if ($win) {$base = 20.;} else {$base = 12.5;}
				break;
			case 'NONE': // Custom game
				if ($win) {$base = 18.;} else {$base = 16;}
				$modifier = 0.75;
			case 'RANKED_TEAM_3x3': // TODO : Lots of things to do here
			case 'NORMAL_3x3';
			case 'RANKED_SOLO_5x5':
			case 'RANKED_TEAM_5x5':
			case 'NORMAL': // Normal
				if ($win) {$base = 18.;} else {$base = 16;}
				break;
			case 'BOT_3x3':
			case 'BOT': // Coop vs AI 5v5
				switch ($difficulty) {
					case 'EASY': // Beginner
						if ($win) {$base = 7.;} else {$base = 6.;}
						if ($level == 30) {$ipminute *= 0.55;}
						else if (20 <= $level) {$ipminute *= 0.7;}
						else if (10 <= $level) {$ipminute *= 0.85;}
					case 'MEDIUM': // Intermediate
						if ($win) {$base = 5.;} else {$base = 2.5;}
						if ($level == 30) {$ipminute *= 0.8;}
						else if (20 <= $level) {$ipminute *= 0.9;}
				}
				break;
			case 'ARAM_UNRANKED_5x5':
				if ($win) {$base = 15.;} else {$base = 14.;}
				break;
		}
		return ($ip - $dominion - $base) / ($ipminute * $modifier);
	}
	
	/*
	DEPRECATED FUNCTIONS
	*/
	
	/**
	* Prepares a query, securizing it and executing it.
	*
	* Uses an opened PDO connection to prepare a query using values contained
	* in an	other array. and then sends it (key => value pairs)
	* 
	* @param resource $pdo opened PDO connection
	* @param string $queryToPrepare A request to be prepared following a certain
	* syntax using named parameters -> Check http://php.net/manual/fr/pdo.prepare.php#example-1021
	* @param array $values Array of strings containing values to place in the query.
	* @return The result of the query
	*
	* @deprecated
	*/
	function query(&$pdo, $queryToPrepare, $values) {
		try {
			$req = $pdo->prepare($queryToPrepare);
			return $req->execute($values);
		}
		catch (Exception $e) {
			echo 'Error while preparing/executing query :<br />';
			echo 'Error : '.$e->getMessage().'<br />';
			echo 'N° : '.$e->getCode();
			exit();
		}
	}

	/**
	* Binds parameters of a prepared query to values of an array by its keys
	*
	* @param resource $pdo opened PDO connection
	* @param columns Array containing the values to bind to keys (keys without ":")
	*
	* @deprecated
	*/
	function bindParams(&$pdo, $columns) {
		try {
			foreach ($columns as $key => $value) {
				$pdo->bindParam(":".$key, $value);
			}
		}
		catch (Exception $e) {
			echo 'Error while binding params :<br />';
			echo 'Error : '.$e->getMessage().'<br />';
			echo 'N° : '.$e->getCode();
			exit();
		}
	}
	
	/*
	UTILITY
	*/
	
	// Test tooltip function
	function getToolTipOfItem(&$c, $itemId) {
		curl_setopt($c, CURLOPT_URL, "http://gameinfo.euw.leagueoflegends.com/en/game-info/items/");
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		return trim(curl_exec($c));
	}
?>