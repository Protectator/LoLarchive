<?php
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
							<option>EUW</option>
							<option>EUNE</option>
							<option>NA</option>
							<option>BR</option>
							<option>TR</option>
							<option>RU</option>
							<option>LAN</option>
							<option>LAS</option>
							<option>OCE</option>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">Summoner name</label>
					<div class="controls">
						<input type="text" id="name" name="name" class="input-medium">
						<?php
						if (SHOW_TRACKED_SUMMONERS) {
							echo ' or <select id="id" name="id" class="input-medium"><option></option>';
							$players = getTrackedPlayers($pdo);
							foreach ($players as $key => $value) {
								echo '<option value="'.$value['summonerId'].'">'.$value['name'].'</option>';
							}
							echo '</select>';
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