<?php
/**
 * @file
 * @ingroup Templates
 */
if( !defined( 'MEDIAWIKI' ) ) {
	die( 1 );
}

/**
 * HTML template for Special:AdminCommunityDashboard
 * @ingroup Templates
 */
class AdminCommunityDashboardTemplate extends QuickTemplate {
	function execute() {
		$widgets = $this->data['widgets'];
		$titles = $this->data['titles'];
		$priorities = $this->data['priorities'];
		$thresholds = $this->data['thresholds'];
		$baselines = $this->data['baselines'];
		$current = $this->data['current'];
?>
<style>
	table { border: 0; border-spacing: 4px; width: 100%; }
	.outer { border: 1px solid #ddd; }
	th { font-size: 9px; }
	tr { vertical-align: top; }
	tr.out>td { padding: 10px; }
	.mid {vertical-align: middle; }
	.wid-id { display: none; }
	.odd { background-color: #ddd; }
	h4 { padding: 5px 0 5px 0; letter-spacing: 0.2em }
	.dlabel { color: #999; }
</style>

<h4><?php echo wfMessage( 'admincommunitydashboard-refresh-data-control' )->text() ?></h4>
<div class="status outer" style="padding: 10px;">
	<i style="text-decoration: underline;"><?php echo wfMessage( 'admincommunitydashboard-status' )->text() ?></i><br />
	<span style="font-weight: bold; font-size: 15px;"><?php echo wfMessage( 'admincommunitydashboard-loading' )->text() ?></span><br />
	<br/>
	<i style="text-decoration: underline;"><?php echo wfMessage( 'admincommunitydashboard-actions' )->text() ?></i><br />
	<ol style="margin-left: 15px;">
		<li><a href="#" class="refresh"><?php echo wfMessage( 'admincommunitydashboard-refresh-status' )->text() ?></a></li>
		<li><a href="#" class="restart"><?php echo wfMessage( 'admincommunitydashboard-restart-script' )->text() ?></a> <?php echo wfMessage( 'admincommunitydashboard-use-caution' )->text() ?></li>
	</ol>
</div>

<br/>

<h4><?php echo wfMessage( 'admincommunitydashboard-widget-customization' )->text() ?></h4>
<div class="outer">
<table class="big">
	<tr>
		<th><?php echo wfMessage( 'admincommunitydashboard-order' )->text() ?></th>
		<th><?php echo wfMessage( 'admincommunitydashboard-priority' )->text() ?></th>
		<th><?php echo wfMessage( 'admincommunitydashboard-widget' )->text() ?></th>
		<th><?php echo wfMessage( 'admincommunitydashboard-maxima' )->text() ?></th>
		<th><?php echo wfMessage( 'admincommunitydashboard-baselines-goals' )->text() ?></th>
	</tr>
	<?php foreach ( $widgets as $i => $widget ): ?>
		<?php
			$isPriority = isset( $priorities[$widget] );
			$checked = $isPriority ? ' checked="yes"' : '';
			wfSuppressWarnings();
			$thresh = $thresholds[$widget];
			$baseline = $baselines[$widget];
			$currentVal = $current[$widget];
			wfRestoreWarnings();
		?>
		<tr class="out">
			<td class="mid"><span class="wid-id"><?php echo $widget ?></span><input type="text" size="2" value="<?php if ( $isPriority ) { echo $i + 1; } ?>" /></td>
			<td class="mid"><input type="checkbox"<?php echo $checked ?> /></td>
			<td class="mid"><?php echo $titles[$widget] ?></td>
			<td>
				<table>
					<tr>
						<td style="width: 70px"><?php echo wfMessage( 'admincommunitydashboard-low-max' )->text() ?></td>
						<td><input class="lowmax" type="text" size="5" value="<?php echo $thresh['low'] ?>" placeholder="<?php echo wfMessage( 'admincommunitydashboard-placeholder-example', 50 )->text() ?>" /></td>
					</tr>
					<tr>
						<td><?php echo wfMessage( 'admincommunitydashboard-mid-max' )->text() ?></td>
						<td><input class="medmax" type="text" size="5" value="<?php echo $thresh['med'] ?>" placeholder="100" /></td>
					</tr>
					<tr>
						<td><?php echo wfMessage( 'admincommunitydashboard-high-max' )->text() ?></td>
						<td><input class="highmax" type="text" size="5" value="<?php echo $thresh['high'] ?>" placeholder="150" /></td>
					</tr>
				</table>
			</td>
			<td>
				<input class="base" type="radio" name="group-<?php echo $widget ?>" value="natural"<?php if ( $baseline ) { echo ''; } else { echo ' checked="checked"'; } ?> /> <?php echo wfMessage( 'admincommunitydashboard-natural-goal' )->text() ?><br />
				<input class="base" type="radio" name="group-<?php echo $widget ?>" value="custom"<?php if ( $baseline ) { ' checked="checked"'; } ?> />
					<?php echo wfMessage( 'admincommunitydashboard-custom-goal' )->text() ?> <input class="custbase" type="text" size="5" value="<?php echo $baseline ?>" placeholder="<?php echo wfMessage( 'admincommunitydashboard-placeholder-example', 75 )->text() ?>" /><br />
				<br />
				<?php
				if ( $currentVal !== '' && $currentVal !== null ) {
					echo wfMessage( 'admincommunitydashboard-current', $currentVal )->parse();
				} else {
					echo wfMessage( 'admincommunitydashboard-current-unknown' )->parse();
				}
				?><br />
			</td>
		</tr>
	<?php endforeach; ?>
</table>

<hr style="color: #eee; background-color: #eee;" />

<div style="margin: 7px;">
	<button class="save" style="margin-left: 15px;" disabled="disabled"><?php echo wfMessage( 'admincommunitydashboard-btn-save' )->text() ?></button>
	<a href="<?php echo SpecialPage::getTitleFor( 'AdminCommunityDashboard' )->getFullURL() ?>" style="margin-left: 5px;"><?php echo wfMessage( 'admincommunitydashboard-btn-cancel' )->text() ?></a><br/>
</div>

</div>
<?php
	} // execute()
} // class