<?php

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
	

	// Connexion à la BDD
	$connect = mysql_connect("localhost", "lolk", "fnu");
	mysql_select_db("lolking", $connect) or die("erreur select db : " . mysql_error());
	
	// Sécurisation des données reçues
	foreach ($_GET as &$thing) {
		$thing = secure($thing);
	}
?>