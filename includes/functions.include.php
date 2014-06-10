<?php

/*	LoLarchive - Website to keeps track of your games in League of Legends
    Copyright (C) 2013-2014  Kewin Dousse (Protectator)

    This file is part of LoLarchive.

    LoLarchive is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    To contact me : kewin.d@websud.ch
    Project's repository : https://github.com/Protectator/LoLarchive
*/

// Useful variables

// Date format accepted when passed in a filter
$validDateFormat = "/^\d+-\d+-\d+$/";

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

// Full name of regions
$regionName = array(
		"euw" => "Europe West",
		"na" => "North America",
		"eune" => "Europe Nordic &amp, East",
		"br" => "Brazil",
		"tr" => "Turkey",
		"ru" => "Russia",
		"lan" => "Latin America North",
		"las" => "Latin America South",
		"oce" => "Oceania"
);

// Display text of game modes
$modes = array (
		"NONE" => "Custom",
		"NORMAL" => "Normal 5v5",
		"NORMAL_3x3" => "Normal 3v3",
		"RANKED_SOLO_5x5" => "Ranked Solo 5v5",
		"RANKED_TEAM_5x5" => "Ranked Team 5v5",
		"RANKED_TEAM_3x3" => "Ranked Team 3v3",
		"BOT" => "Co-op vs AI 5v5",
		"BOT_3x3" => "Co-op vs AI 3v3",
		"ARAM_UNRANKED_5x5" => "ARAM",
		"ODIN_UNRANKED" => "Dominion",
		"ONEFORALL_5x5" => "One for all",
		"FIRSTBLOOD_2x2" => "Snowdown 2v2",
		"FIRSTBLOOD_1x1" => "Snowdown 1v1",
		"SR_6x6" => "Hexakill",
		"URF" => "U.R.F.",
		"CAP_5x5" => "Team Builder 5v5",
		"URF_BOT" => "U.R.F. vs AI"
);

/*
 HEADER AND FOOTER
 */

/**
 * Prints the header of the page
 * 
 * @param  string $title Title of the page (display in the tab)
 */
function echoHeader($title = "LoLarchive"){
	require_once(LOCAL.'includes/header.php');
}

/**
 * Prints the footer of the page
 */
function echoFooter(){
	require_once(LOCAL.'includes/footer.php');
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
 * @param $req
 * @internal param string $query Query to perform
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
 * @return string Conditions for SQL request 
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
 * @param $arrayOfColumns Array containing arrays of keys and values to add to the SQL INSERT
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
 * Gets most recent games by summoner id
 *
 * @param resource $c opened cURL session
 * @param string $region abbreviated server's name
 * @param string $sId account Id to look for
 * @return array of result
 */
function apiGame(&$c, $region, $sId) {
	$url = API_URL.$region."/v".GAME_API_VERSION."/game/by-summoner/".$sId."/recent?api_key=".API_KEY;
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return json_decode(trim(curl_exec($c)), true);
}

/**
 * Gets summoner infos by its name
 *
 * @param resource $c opened cURL session
 * @param string $region abbreviated server's name
 * @param string $sName summoner name to look for
 * @return array of result
 */
function apiSummonerByName(&$c, $region, $sName) {
	$url = API_URL.$region."/v".SUMMONER_API_VERSION."/summoner/by-name/".$sName."?api_key=".API_KEY;
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return json_decode(trim(curl_exec($c)), true);
}

/**
 * Gets summoner infos by its id
 *
 * @param resource $c opened cURL session
 * @param string $region abbreviated server's name
 * @param string $sName summoner name to look for
 * @return array of result
 */
function apiSummonerNames(&$c, $region, $sIds) {
	if (is_array($sIds)) {$sIds = implode(",", array_slice($sIds, 0, 40));}
	$url = API_URL.$region."/v".SUMMONER_API_VERSION."/summoner/".$sIds."/name?api_key=".API_KEY;
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return json_decode(trim(curl_exec($c)), true);
}

/**
 * Gets items images informations
 * 
 * @param resource $c opened cURL session
 * @param string $region abbreviated server's name
 * @return array of result
 */
function apiItemsImages(&$c, $region) {
	$url = API_URL."static-data/".$region."/v".STATIC_DATA_VERSION."/item?itemListData=image&api_key=".API_KEY;
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return json_decode(trim(curl_exec($c)), true);
}

/**
 * Gets champions images informations
 * 
 * @param resource $c opened cURL session
 * @param string $region abbreviated server's name
 * @return array of result
 */
function apiChampionsImages(&$c, $region) {
	$url = API_URL."static-data/".$region."/v".STATIC_DATA_VERSION."/champion?champData=image&api_key=".API_KEY;
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return json_decode(trim(curl_exec($c)), true);
}

/*
 HIGHER LEVEL FUNCTIONS
 */

/**
 * Gets all tracked players
 * @param  resource $pdo opened PDO connection
 * @return array of result
 */
function getTrackedPlayers(&$pdo) {
	$requestString = "SELECT region, name, summonerId FROM usersToTrack ORDER BY name ASC";
	$result = rawSelect($pdo, $requestString);
	return $result->fetchAll(PDO::FETCH_NAMED);
}

/**
 * Adds a summoner to track games.
 *
 * @param resource $pdo Opened PDO connection
 * @param resource $c Opened cURL conneciton
 * @param string $region Region of the summoner
 * @param string $name Summoner name
 * @param boolean $isId If set to True, then the $name passed is an id.
 * @return int Should return "1" if the request was executed correctly.
 * returns "2" if the summoner is already tracked.
 * Does not guarantee the summoner has effectively been tracked, though.
 */
function trackNewPlayer(&$pdo, &$c, $region, $name, $isId = False) {
	if ($isId) {
		$summoner = apiSummonerNames($c, $region, $name);
	} else {
		$summoner = apiSummonerByName($c, $region, $name);
	}
	if (empty($summoner)) {
		return 0;
	}
	$summoner = current($summoner);
	if (!array_key_exists('id', $summoner) OR $summoner['id'] == 0) {
		return 0;
	}
	$infos = array (
		"region" => $region,
		"summonerId" => $summoner['id'],
		"name" => $summoner['name']
	);
	$request = "INSERT INTO usersToTrack "/*chelou*/.buildInsert($infos)." ON DUPLICATE KEY UPDATE name = '".$name."';";
	$result = securedInsert($pdo, $request); // Returns the number of affected rows
	if ($result[0] == 1) {
		if ($result[1] == 1) {
			return 1;
		} else {
			return 2;
		}
	}
	return 0;
}

/**
 * No longer tracks a summoner.
 *
 * @param resource $pdo Opened PDO connection
 * @param resource $c Opened cURL conneciton
 * @param string $region Region of the summoner
 * @param string $name Summoner name
 * @param boolean $isId If set to True, then the $name passed is an id.
 * @return int Should return "1" if the request was executed correctly.
 * returns "2" if the summoner is already untracked.
 * Does not guarantee the summoner has effectively been tracked, though.
 */
function untrackPlayer(&$pdo, &$c, $region, $name, $isId = False) {
	if ($isId) {
		$summoner = array(array('id' => $name));
	} else {
		$summoner = apiSummonerByName($c, $region, $name);
	}
	if (empty($summoner)) {
		return 0;
	}
	$summoner = current($summoner);
	if (!array_key_exists('id', $summoner) OR $summoner['id'] == 0) {
		return 0;
	}
	$request = "DELETE FROM usersToTrack WHERE summonerId = '".$summoner['id']."' AND region = '".$region."';";
	$result = securedInsert($pdo, $request); // Returns the number of affected rows
	if ($result[0] == 1) {
		if ($result[1] == 1) {
			return 1;
		} else {
			return 2;
		}
	}
	return 0;
}

/**
 * Estimates the total duration of a game based on the playing time
 * of the participating summoners.
 * 
 * @param  resource $pdo    Opened PDO connection
 * @param  int 		$gameId id of the game to (re-)estimate the duration.
 * @return int The estimated duration of that game, or null if there is an error.
 */
function estimateDuration(&$pdo, $gameId) {
	$selectRequestString = "SELECT MAX(timePlayed) FROM data WHERE gameId = :gId";
	$selectRequest = $pdo->prepare($selectRequestString);
	$selectRequest->bindParam("gId", $gameId);
	$selectRequest->execute();
	$average = $selectRequest->fetchAll();
	$average = $average[0]['MAX(timePlayed)'];
	$updateRequestString = "UPDATE games SET estimatedDuration = :time WHERE gameId = :gId";
	$updateRequest = $pdo->prepare($updateRequestString);
	$updateRequest->bindParam(":time", $average);
	$updateRequest->bindParam(":gId", $gameId);
	$updateRequest->execute();
	$affectedRows = $updateRequest->rowCount();
	if ($affectedRows == 1) {
		return $average;
	} else {
		trigger_error("Could not update estimated duration of game ".$gameId." in database.", E_WARNING);
		return null;
	}
}

/**
 * Estimates the winning team of a game based on the individual win values of summoners
 * in that game.
 * 
 * @param  resource $pdo    Opened PDO connection
 * @param  int 		$gameId id of the game to (re-)estimate the duration.
 * @return int The estimated winning team of that game, or null if there is an error.
 */
function estimateWinningTeam(&$pdo, $gameId) {
	$selectRequestString = "SELECT DISTINCT teamId, win FROM players p, data d WHERE d.summonerId = p.summonerId AND p.gameId=d.gameId AND d.gameId=:gId";
	$selectRequest = $pdo->prepare($selectRequestString);
	$selectRequest->bindParam("gId", $gameId);
	$selectRequest->execute();
	$reference = $selectRequest->fetchAll();
	$reference = $reference[0];
	if (ord($reference['win']) == 1) {
		$winningTeam = $reference['teamId'];
	} else {
		$winningTeam = 300 - $reference['teamId'];
	}
	$updateRequestString = "UPDATE games SET estimatedWinningTeam = :winTeam WHERE gameId = :gId";
	$updateRequest = $pdo->prepare($updateRequestString);
	$updateRequest->bindParam(":gId", $gameId);
	$updateRequest->bindParam(":winTeam", $winningTeam);
	$updateRequest->execute();
	$affectedRows = $updateRequest->rowCount();
	if ($affectedRows == 1) {
		return $winningTeam;
	} else {
		trigger_error("Could not update estimated winning team of game ".$gameId." in database.", E_WARNING);
		return null;
	}
}

/**
 * Saves the informations about a summoner by querying the API.
 * 
 * @param  resource	$pdo	Opened PDO connection
 * @param  resource	$c		Opened cURL conneciton
 * @param  string	$region	Region of the summoner
 * @param  int		$id		id of the summoner
 * @return string			Name of the summoner, or null if none was found.
 */
function saveSummonerInfosById(&$pdo, &$c, $region, $id) {
	$summoner = apiSummonerNames($c, $region, $id);
	if (!is_null($summoner)) { // If we found someone in the API
		$summonerName = current($summoner);
		$usersFields = array(
			"id" => $id,
			"user" => $summonerName,
			"region" => $region
			);
		$addUserRequestString = "INSERT IGNORE INTO users ".buildInsert($usersFields);
		$result = securedInsert($pdo, $addUserRequestString);
		if (!($result == array(1, 1))) {
			trigger_error("Tried to save informations about an already existing in database
				summoner.", E_WARNING);
		}
		return $summonerName;
	} else { // If we didn't find anything in the API either
		return null;
	}
}

/**
 * Gets the games in a specified page of a specified search
 *
 * @param resource $pdo Opened PDO connection
 * @param string $region Region of the summoner account
 * @param string $sId Summoner id
 * @return PDOStatement Contains the result of the PDO request
 */
function getPage(&$pdo, $region, $sId, $filters, $page) {
	// TODO
}

/*
 LOWER LEVEL FUNCTIONS
 undocumented part
 */

/**
 * Returns the image link of a champion
 *
 * @return string Link to the image 
 */
function champImg($champId) {
	global $champions;
	if (!isset($champions)) {
		$cUrl = curl_init();
		$championsAnswer = apiChampionsImages($cUrl, "euw");
		curl_close($cUrl);
		$champions = array();
		foreach ($championsAnswer['data'] as $key => $value) {
			$champions[intval($value['id'])] = array("img" => $value['image']['full'], "name" => $value['key'], "display" => $value['name']);
		}
	}
	return STATIC_RESOURCES.STATIC_RESOURCES_VERSION."/img/champion/".$champions[$champId]['img'];
}

/**
 * Reads a file backwards
 * Found this code on http://stackoverflow.com/questions/2961618/how-to-read-only-5-last-line-of-the-text-file-in-php
 * written by Kevin Duke
 */
function read_backward_line($filename, $lines, $revers = false)
{
    $offset = -1;
    $c = '';
    $read = '';
    $i = 0;
    $fp = @fopen($filename, "r");
    while( $lines && fseek($fp, $offset, SEEK_END) >= 0 ) {
        $c = fgetc($fp);
        if($c == "\n" || $c == "\r"){
            $lines--;
            if( $revers ){
                $read[$i] = strrev($read[$i]);
                $i++;
            }
        }
        if( $revers ) $read[$i] .= $c;
        else $read .= $c;
        $offset--;
    }
    fclose ($fp);
    if( $revers ){
        if($read[$i] == "\n" || $read[$i] == "\r")
            array_pop($read);
        else $read[$i] = strrev($read[$i]);
        return implode('',$read);
    }
    return strrev(rtrim($read,"\n\r"));
}

/**
 * Writes text at the end of the access log file
 *
 * @param string $text Text to log
 */
function logAccess($text) {
	$file = LOCAL.'private/logs/access.log';
	$toWrite = "[".date(DateTime::RSS, time())."] [".$_SERVER['REMOTE_ADDR']."] ".$text.PHP_EOL;
	addToFile($file, $toWrite);
}

/**
 * Writes text at the end of the error log file
 *
 * @param string $text Text to log
 */
function logError($text) {
	$file = LOCAL.'private/logs/error.log';
	$toWrite = "[".date(DateTime::RSS, time())."] [".$_SERVER['REMOTE_ADDR']."] ".$text.PHP_EOL;
	addToFile($file, $toWrite);
}

/**
 * Writes text at the end of the error log file
 *
 * @param string $text Text to log
 */
function logAdmin($text) {
	$file = LOCAL.'private/logs/admin.log';
	$toWrite = "[".date(DateTime::RSS, time())."] [".$_SERVER['REMOTE_ADDR']."] ".$text.PHP_EOL;
	addToFile($file, $toWrite);
}

/**
 * Adds text to the end of a file.
 * 
 * @param string $file    Absolute path of the file
 * @param string $content Text to add at the end of the file.
 */
function addToFile($file, $content) {
	$errorString = "Tried to write in file '".$file."' ;".PHP_EOL;
	if (file_exists($file)) {
		if (is_readable($file)) {
			if (is_writable($file)) {
				file_put_contents($file, $content, FILE_APPEND); 
			} else {
				$errorString .= "File is not writable";
				trigger_error($errorString, E_USER_ERROR);
			}
		} else {
			$errorString .= "File is not readable";
			trigger_error($errorString, E_USER_ERROR);
		}
	} else {
		$errorString .= "File does not exist";
		trigger_error($errorString, E_USER_ERROR);
	}
}

/**
 * Transforms a certainly formatted date to SQL's TIMESTAMP format.
 * 
 * @param  string $date date in format "yyyy-mm-dd" to convert
 * @param  string $time time in format "hh:mm:ss"
 * @return string DATETIME (format "dd-mm-yyyy hh:mm:ss")
 */
function dateToSQL($date, $time) {
	return implode("-", array_reverse( explode("-", $date)))." ".$time;
}

/**
 * Transforms a DATETIME SQL to he wanted format to be printed.
 * 
 * @param  string $datetime SQL DATETIME to print
 * @return string	Date and time formatted to be printed
 */
function printableSQLDate($datetime) {
	$year = substr($datetime, 0, 4);
	$month = substr($datetime, 5, 2);
	$day = substr($datetime, 8, 2);
	$hour = substr($datetime, 11, 2);
	$min = substr($datetime, 14, 2);
	return $day.".".$month.".".$year." ".$hour.":".$min;
}

/*
 PRINT FUNCTIONS
 */

/**
 * Puts an array in an HTML table
 * @param  $array Array to be printed
 * @param  string $classes added classes to the table
 * @param  array  $titles  array of titles
 * @return string HTML code
 */
function arrayToTable($array, $classes = "", $titles = array()) {
	$result = "<table class=\"table ".$classes."\">";
	if (!empty($titles)) {
		$result .= "<thead><tr>";
		foreach ($titles as $key => $value) {
			$result .= "<th>$value</th>";
		}
		$result .= "</tr></thead>";
	}
	$result .= "<tbody>";
	foreach ($array as $key => $value) {
		$result .= "<tr>";
			foreach ($value as $metaKey => $metaValue) {
				$result .= "<td>".$metaValue."</td>";
			}
		$result .= "</tr>";
	}
	$result .= "</tbody></table>";
	return $result;
}

/**
 * Puts an array in an HTML table formatted as a vertical list.
 * @param  $array Array to be printed
 * @param  string $classes added classes to the table
 * @return string HTML code
 */
function arrayToVerticalList($array, $classes = "") {
	$result = "<table class=\"table ".$classes."\">";
	$result .= "<tbody>";
	foreach ($array as $key => $value) {
		$result .= "<tr><th>".$key."</th><td>".$value."</td></tr>";
	}
	$result .= "</tbody></table>";
	return $result;
}

/**
 * Transforms an array into HTML <td> cells
 * @param  array $array mono-dimensional array to put in cells
 * @return string       HTML <td>s
 */
function arrayToCells($array, $th = False) {
	if (!empty($array)) {
		($th) ? $tag = "th" : $tag = "td";
		return "<".$tag.">".implode("</".$tag."><".$tag.">", $array)."</".$tag.">";
	}
	return "";
}



/**
 * Generates the HTML code to display the stats header
 * @param array $finalStats Array containing the stats of a search
 * @param int $nbWon      Number of won games
 * @return string HTML code
 */
function HTMLstats($finalStats, $nbWon) {
	if ($finalStats['nbGames'] != 0) {
		$kdaRatio = ($finalStats['d'] != 0) ? round(($finalStats['k']+$finalStats['a'])/$finalStats['d'], 2) : $finalStats['k'] + $finalStats['a'];
		$result  = "<table class=\"table table-condensed\" id=\"stats-table\">";
		$result .= "<tr><td><i class='icon-thumbs-up icon-white'></i> Wins</td><td class='right'><span class='number'>".$nbWon['nb']."</span> / <span class='number'>".$finalStats['nbGames']."</span> games (<span class='number'>".round($nbWon['nb']/$finalStats['nbGames']*100, 2)."%</span>)</td>";
		$result .= "<td class='left'><i class='icon-briefcase icon-white'></i> Average gold/min</td><td class='right'><span class='number'>".round(60*$finalStats['gold']/$finalStats['duration'], 0)."</span></td>";
		$result .= "<td class='left'><i class='icon-certificate icon-white'></i> Dmg on champions/min</td><td class='right'><span class='number'>".number_format($finalStats['dmgToChamps']*60/$finalStats['duration'], 0, '', '\'')."</span></td></tr>";
		$result .= "<tr><td><i class='icon-signal icon-white'></i> KDA</td><td class='right'><span class='number'>".round($finalStats['k'], 1)."</span> / <span class='number'>".round($finalStats['d'], 1)."</span> / <span class='number'>".round($finalStats['a'], 1)."</span></td>";
		$result .= "<td class='left'><i class='icon-screenshot icon-white'></i> cs/min</td><td class='right'><span class='number'>".round(60*$finalStats['minions']/$finalStats['duration'], 2)."</span></td>";
		$result .= "<td class='left'><i class='icon-eye-open icon-white'></i> ward/min</td><td class='right'><span class='number'>".round(60*$finalStats['wards']/$finalStats['duration'], 2)."</span></td></tr>";
		$result .= "<tr><td><i class='icon-indent-left icon-white'></i> Ratio</td><td class='right'><span class='number'>".$kdaRatio."</span></td>";
		$result .= "<td class='left'><i class='icon-time icon-white'></i> Average duration</td><td class='right'><span class='number'>".round($finalStats['duration']/60, 0)."</span> min.</td></tr>";
		$result .= "</table>";
	} else {
		$result = "No game found.";
	}
	return $result;
}

/**
 * Generates the HTML code to display a Error well.
 * 
 * @param string $title		Title of the Error
 * @param string $content	Content of the Error
 * @return string HTML code
 */
function HTMLerror($title, $content) {
	return "<div class='alert alert-error alert-block'><h4>".$title."</h4>".$content."</div>";
}

/**
 * Generates the HTML code to display a Warning well (with an close button).
 * 
 * @param string $title		Title of the Warning
 * @param string $content	Content of the Warning
 * @return string HTML code
 */
function HTMLwarning($title, $content) {
	return "<div class='alert alert-warning alert-block'>
		<button type='button' class='close' data-dismiss='alert'>&times;</button>
		<h4>".$title."</h4>".$content."</div>";	
}

/**
 * Generates the HTML code to display a Success well (with an close button).
 * 
 * @param string $title		Title of the Warning
 * @param string $content	Content of the Warning
 * @return string HTML code
 */
function HTMLsuccess($title, $content) {
	return "<div class='alert alert-success alert-block'>
		<button type='button' class='close' data-dismiss='alert'>&times;</button>
		<h4>".$title."</h4>".$content."</div>";	
}

/**
 * Generates the HTML code to display a Info well (with an close button).
 * 
 * @param string $title		Title of the Warning
 * @param string $content	Content of the Warning
 * @return string HTML code
 */
function HTMLinfo($title, $content) {
	return "<div class='alert alert-info alert-block'>
		<button type='button' class='close' data-dismiss='alert'>&times;</button>
		<h4>".$title."</h4>".$content."</div>";	
}

/**
 * Generates the HTML code to display the name of a summoner and its champion's icon.
 *
 * @param string region The server in which the game has taken place.
 * @param int championId id of the champion played
 * @param string summonerName Display name of the summoner
 * @param int summonerId id of the summoner
 * @return string HTML code
 */
function HTMLparticipant($region, $championId, $summonerName, $summonerId) {
	if ($summonerName != "") {
		$displayText = $summonerName;
		$displayClass = "littleSummonerLinkName";
	} else {
		$displayText = $summonerId;
		$displayClass = "littleSummonerLinkId";
	}
	$result = "<td class=\"littleChampIcon\">".HTMLchampionImg($championId, "small")."</td>";
	$result .= '<td class="'.$displayClass.'"><a href="'.PATH.'index.php?page=player&amp;region='.$region.'&amp;id='.$summonerId.'">'.$displayText.'</a></td>';
	return $result;
}

/**
 * Generates the HTML code to display the image of a champion
 *
 * @param int championId id of the champion
 * @return string HTML code
 */
function HTMLchampionImg($championId, $size = "small") {
	if ($size == "small") {
		return "<img src=\"".champImg(intval($championId))."\" class=\"littleChampIcon\" alt=\"".$championId."\">";
	} else if ($size == "big") {
		return "<img src=\"".champImg(intval($championId))."\" class=\"img-rounded imgChampion\" alt=\"".$championId."\">";
	}
}

/**
 * Generates the HTML code to display participants in a game
 *
 * @param array team1 List of players in team 1 
 * @param array team2 List of players in team 2
 * @return string HTML code
 */
function HTMLparticipants($region, $team1, $team2) {
	$result = "<table class=\"players\">";
	$nbLines = max (5, count($team1), count($team2));
	// Line per line
	for ($i = 0; $i < $nbLines ; $i++) {

		$result .= "<tr class=\"playerLine\">";
		// Left team member
		if (isset($team1[$i])) {
			$result .= HTMLparticipant($region, $team1[$i]['championId'], $team1[$i]['user'], $team1[$i]['summonerId']);
			} else {
			$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerLinkName\"></td>";
		}
		// Right team member
		if (isset($team2[$i])) {
			$result .= HTMLparticipant($region, $team2[$i]['championId'], $team2[$i]['user'], $team2[$i]['summonerId']);
		} else {
			$result .= "<td class=\"littleChampIcon\"></td><td class=\"littleSummonerLinkName\"></td>";
		}
		$result .= "</tr>";
	}

	return $result."</table>";
}

/**
 * Generates the HTML code to display the image of an item
 *
 * @param int itemId id of the item
 * @return string HTML code
 */
function HTMLitem($itemId) {
	if ($itemId != 0) {
		global $itemsImages;
		if (!isset($itemsImages)) {
			$cUrl = curl_init();
			$itemsImages = apiItemsImages($cUrl, "euw");
			curl_close($cUrl);
		}
		$actualItem = $itemsImages['data'][$itemId];
		$href = STATIC_RESOURCES.STATIC_RESOURCES_VERSION."/img/sprite/".$actualItem['image']['sprite'];
		$x = $actualItem['image']['x']*2/3;
		$y = $actualItem['image']['y']*2/3;
		$alttext = $actualItem['name'];
		return '<div class= "img-rounded imgitem32" style="background: url(\''.$href.'\') -'.$x.'px -'.$y.'px no-repeat; background-size: 320px;" title="'.$alttext.'"></div>';
	} else {
		return '<div class= "img-rounded imgitem32"></div>';
	}
}

/**
 * Generates the HTML code to display all the items of a player as table content.
 *
 * Takes 2 lines in a table of 4 columns
 *
 * @param array<int> itemsId List of item's id
 * @return string HTML code
 */
function HTMLinventory($itemsId) {
	array_pad($itemsId, 7, ""); // Completes array with empty Strings
	$result = "<table><tr><td class=\"singleitemcell\">".HTMLitem($itemsId[0])."<td>".
	"<td class=\"singleitemcell\">".HTMLitem($itemsId[1])."<td>".
	"<td class=\"singleitemcell\">".HTMLitem($itemsId[2])."<td>".
	"<td class=\"singleitemcell\" rowspan=\"2\">".HTMLitem($itemsId[6])."<td></tr>".
	"<tr><td class=\"singleitemcell\">".HTMLitem($itemsId[3])."<td>".
	"<td class=\"singleitemcell\">".HTMLitem($itemsId[4])."<td>".
	"<td class=\"singleitemcell\">".HTMLitem($itemsId[5])."<td></tr></table>";
	return $result;
}

/**
 * Generates the HTML code to display a summoner spell
 *
 * @param int spellId id of the summoner spell
 * @return string HTML code
 */
function HTMLsummonerSpell($spellId) {
	return "<div class=\"ss\"><img class=\"icon img-rounded\" src=\"".PATH."img/ss/".$spellId.".png\" alt=\"Summoner Spell n ".$spellId."\"></div>";
}

/**
 * Generates the HTML code to display the two used summoner spells
 *
 * @param int spell1Id id of first summoner spell
 * @param int spell2Id id of second summoner spell
 * @return string HTML code
 */
function HTMLsummonerSpells($spell1Id, $spell2Id) {
	return HTMLsummonerSpell($spell1Id)."\n".HTMLsummonerSpell($spell2Id);
}

/**
 * Generates the HTML code to display a few basic stats about a summoner's game
 *
 * @param int k Number of kills
 * @param int d Number of deaths
 * @param int a Number of assists
 * @param int minions Number of minions slained
 * @param int gold Amount of total gold
 * @return string HTML code
 */
function HTMLkda($k, $d, $a, $minions, $gold) {
	$result = "<div class=\"kdaNumber\">".$k."<img class=\"icon\" src=\"".PATH."img/kill.png\" alt=\"kills\"></div>".
	"<div class=\"kdaNumber\">".$d."<img class=\"icon\" src=\"".PATH."img/death.png\" alt=\"deaths\"></div>".
	"<div class=\"kdaNumber\">".$a."<img class=\"icon\" src=\"".PATH."img/assist.png\" alt=\"assists\"></div>".
	
	"<br><div class=\"minion\">".$minions."<img class=\"icon\" src=\"".PATH."img/minion.png\" alt=\"minions\"></div>".
	"<br><div class=\"gold\">".$gold."<img class=\"icon\" src=\"".PATH."img/gold.png\" alt=\"gold\"></div>";
	return $result;
}

/**
 * Generates the HTML code to display a few stats about a summoner's game
 *
 * @param string type Type of the game
 * @param int win If the game was won
 * @param int duration Duration of the game
 * @param string date Date at start of the game
 * @return string HTML code
 */
function HTMLgeneralStats($type, $text, $duration, $date) {
	$result = $type."<br><span class=\"resultText\">".$text."</span><br>~".round($duration/60)." min.<br>".printableSQLDate($date);
	return $result;
}

/**
 * Generates the HTML code to display data of a game.
 * 
 * @param array $row All data about a game
 * @return  string HTML code
 */
function HTMLdata($row) {
	$hasData = isset($row['spell1']);
	if ($hasData) {
		$inventory = array($row['item0'], $row['item1'], $row['item2'], 
						$row['item3'], $row['item4'], $row['item5'], $row['item6']);
		$result =	"<div class=\"matchcell kdacell\">".HTMLkda($row['championsKilled'], $row['numDeaths'],
							$row['assists'], $row['minionsKilled']+$row['neutralMinionsKilled'], $row['goldEarned'])."</div>".
					"<div class=\"matchcell sscell\">".HTMLsummonerSpells($row['spell1'], $row['spell2'])."</div>".
					"<div class=\"matchcell itemscell\">".HTMLinventory($inventory)."</div>";
	} else {
		$result = "<div class=\"matchcell nodatacell\">No data.</div>";
	}
	return $result;
}

/**
 * Generates the HTML code for the dominion part of a game (replaceing the usual kda+minions)
 * @param [type] $row All data about a game
 */
function HTMLdominion($row) {
	$result = "<div class=\"kdaNumber\">".$k."<img class=\"icon\" src=\"".PATH."img/kill.png\" alt=\"kills\"></div>".
	"<div class=\"kdaNumber\">".$d."<img class=\"icon\" src=\"".PATH."img/death.png\" alt=\"deaths\"></div>".
	"<div class=\"kdaNumber\">".$a."<img class=\"icon\" src=\"".PATH."img/assist.png\" alt=\"assists\"></div>".
	
	"<br><div class=\"minion\">".$minions."<img class=\"icon\" src=\"".PATH."img/minion.png\" alt=\"minions\"></div>".
	"<br><div class=\"gold\">".$gold."<img class=\"icon\" src=\"".PATH."img/gold.png\" alt=\"gold\"></div>";
	return $result; // TODO : Change it from kda to dominion
}

/**
 * Generates the HTML code to display a player's game small view
 *
 * @param array $row All data about a game
 * @return string HTML code
 */
function HTMLplayerGame($row, $win, $duration, $lTeam, $rTeam) {
	global $modes;
	if ($win == 1) {
		$class = "winmatch";
		$text = "Win";
	} else {
		$class = "lossmatch";
		$text = "Loss";
	}

	$result =
		"<div class=\"row\">".
		"	<div class=\"span12\">".
		"		<div class=\"well ".$class." match\" id=\"".$row['gameId'][0]."\">".
		"			<div class=\"matchcell championcell\">".HTMLchampionImg($row['championId'], 'big')."</div>".
		"			<div class=\"matchcell headcell\">".HTMLgeneralStats($modes[$row['subType']], $text, $duration, $row['createDate'])."</div>".
		HTMLdata($row).
		"			<div class=\"matchcell playerscell\">".HTMLparticipants($row['region'][0], $lTeam, $rTeam)."</div>".
		"		</div>".
		"	</div>".
		"</div>";

	return $result;
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
 * @param resource $pedo opened PDO connection
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

/**
 * Simple function to sort an array by a specific key. Maintains index association.
 * @param  array $array Array to sort
 * @param  string $on key to sort by
 * @param  sort_flags $order sorting flas. See http://www.php.net/manual/en/function.sort.php
 * @return array sorted array
 */
function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

// Test tooltip function
function getToolTipOfItem(&$c, $itemId) {
	curl_setopt($c, CURLOPT_URL, "http://gameinfo.euw.leagueoflegends.com/en/game-info/items/");
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	return trim(curl_exec($c));
}
?>