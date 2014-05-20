<?php

/**
 * Represent one summoner's data of a single game.
 * @author Kewin Douse kewin.d@websud.ch
 */
class summonerGameData
{
	private $_data;
	
	/**
	 * Constructor
	 * @param array $gameData One of the games returned by Riot Games' API method game-v1.3 
	 */
	public function __construct(array $gameData)
	{
		if (!is_array($gameData)) {
			trigger_error("Game data should be an array, received a ".gettype($gameData)." instead.", E_USER_WARNING);
			return;
		} elseif (empty($gameData)) {
			trigger_error("Game data is empty.", E_USER_WARNING);
			return;
		}
		$this->_data = $gameData;
	}

	/**
	 * Returns one stat value of the game by its key
	 * @param  string $key Stat key 
	 * @return string Value of the stat
	 */
	public function stat($key)
	{
		if (!is_string($key)) {
			trigger_error("Stat key should be a string, received a ".gettype($gameData)." instead.", E_USER_WARNING);
			return;
		} elseif (empty($key)) {
			trigger_error("Stat key is empty.", E_USER_WARNING);
			return;
		}
		return $this->_data['stats'][$key];
	}

	/**
	 * Returns the participants in that game
	 * @param  boolean $actualPlayer Set to TRUE if you want to include the the referenced player
	 * @return array Participants                
	 */
	public function fellowPlayers($actualPlayer = FALSE)
	{
		$result = $_data['fellowPlayers'];
		if ($actualPlayer) {
			$result[] = array(
				'championId' => $this->_data['championId'],
				'teamId' => $this->_data['teamId'],
				'summonerId' => $this->_data['summonerId']
			);
		}
		return $result;
	}

}

?>