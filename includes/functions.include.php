<?php

	define('PATH', "/lolarchive/");
	define('LOCAL', "/var/www");

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
	foreach($months as $key => $value) {
		$months[intval($value)] = $key;
	}
	
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
	
	/**
	* Commits one or multiple queries at once.
	*
	* Use this to do INSERT, DELETE or UPDATE queries.
	* Commits all requests in the order they are read -> The order of $queries array
	*
	* @param resource $pdo opened PDO session
	* @param array|string $queries String or Array of Strings containing the 
	* SQL queries to perform at once.
	* @return integer Number of affected rows
	*/
	function securedInsert(&$pdo, $queries) {
		try
		{
			$pdo->beginTransaction();
			if (is_string($queries)) {
				$pdo->query($queries);
			} else {
				foreach($queries as $currentQuery) {
					$pdo->query($currentQuery);
				}
			}
			$result = $pdo->commit();
			return $result;
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
	* @param resource $pdo opened PDO session
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

	/*
	SECURITY FUNCTIONS
	*/

	/**
	* Secures an user input before treating it.
	*
	* Converts a string to an integer if it is one.
	* If not, escapes apostrophes and other risky stuff.
	*
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
	*/
	function purify($string) {
		return htmlspecialchars($string);
	}
	
	/**
	* Applies secure() on all values of an array and all subarrays.
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
	HIGH LEVEL FUNCTIONS
	*/
	
	/**
	* To be tested
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
	LOW LEVEL FUNCTIONS
	*/
	
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
				$result = $result.'<td class="littleSummonerName"><a href="'.PATH.'index.php?page=player&amp;region='.$region.'&amp;id='.$row['a'.$i.'id']./*"#".$row['id'].*/'">'.$row['a'.$i.'user'].'</a></td>';
			} else {
				$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerName\"></td>";
			}
			
			// user B
			if ($row['b'.$i.'id'] != "0") {
			$result = $result."<td class=\"littleChampIcon\"><img src=\"".$champsFolder.ucfirst($row['b'.$i.'champ']).".png\" class=\"littleChampIcon\" alt=\"".$row['b'.$i.'champ']."\"></td>";
			$result = $result.'<td class="littleSummonerName"><a href="'.PATH.'index.php?page=player&amp;region='.$region.'&amp;id='.$row['b'.$i.'id']./*"#".$row['id'].*/'">'.$row['b'.$i.'user'].'</a></td>';
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
			case '8': // DOMINION id map

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
	
	/*
	DEPRECATED FUNCTIONS
	*/
	
	/**
	* Prepares a query, securizing it and executing it.
	*
	* Uses an opened PDO connection to prepare a query using values contained
	* in an	other array. and then sends it (key => value pairs)
	* 
	* @param resource $pdo opened PDO session
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
	* @param resource $pdo opened PDO session
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
	
	// If we get parameters, securize them
	/*
	foreach ($_GET as &$thing) {
		$thing = secure($thing);
	}
	*/
?>