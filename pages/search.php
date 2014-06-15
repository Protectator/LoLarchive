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

echoHeader();
?>
<div class="row">
	<div class="span12">
		<form class="form-horizontal well" action="<?php echo PATH;?>index.php" method="get">
			<fieldset>
				<legend>Find a summoner</legend>
				<input type="hidden" name="page" value="player"/> 
				<div class="control-group">
					<label class="control-label">Region</label>
					<div class="controls">
						<select id="region" name="region" class="input-small">
							<option value="EUW">EUW</option>
							<option value="EUNE">EUNE</option>
							<option value="NA">NA</option>
							<option value="BR">BR</option>
							<option value="TR">TR</option>
							<option value="RU">RU</option>
							<option value="LAN">LAN</option>
							<option value="LAS">LAS</option>
							<option value="OCE">OCE</option>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">Summoner name</label>
					<div class="controls">
						<input type="text" id="name" name="name" class="input-medium" maxlength="16">
						<?php
						if (SHOW_TRACKED_SUMMONERS) {
							echo ' or <select id="id" name="id" class="input-large"><option></option>';
							$players = getTrackedPlayers($pdo);
							foreach ($players as $key => $value) {
								echo '<option value="'.$value['summonerId'].'">'.$value['name']." [".$value['region']."]".'</option>';
							}
							echo '</select>';
							echo '<input type="hidden" id="hiddenRegion" name="region" disabled>';
						} 
						?>
					</div>
				</div>
				<div class="form-actions">
					<div class="controls">
						<button type="submit" class="btn btn-primary"><i class="icon-search icon-white"></i> Search</button>
						<?php if (LINKTO_LOLKING) { echo "<button type=\"submit\" class=\"btn disabled\" disabled>LolKing</button>";} ?>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>
<?php
echoFooter();
?>