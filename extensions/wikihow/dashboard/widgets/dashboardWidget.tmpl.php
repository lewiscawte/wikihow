<?= $header ?>
<div class="comdash-widget-body">
	<div class="comdash-weather <?= $weather ?>">All Clear</div>
	<div class="comdash-count">
		<span><?= $data['ct'] ?></span><?= $countDescription ?>
	</div>
	<div style="display:none">
		unpatrolled:<br/>
		<span class="cd-rcw-unpatrolled"><?= $data['unpatrolled'] ?></span><br/>
		completed today:<br/>
		<span class="cd-rcw-completed"><?= intval($completedToday) ?></span><br/>
		thresholds: <?= print_r($thresholds,true) ?><br/>
		<br/>
	</div>
</div>
<div class="comdash-widget-footer">
	<div class='comdash-lastcontributor'><span class='avatar'><?= $data['lt']['im'] ?></span><span>Last</span><span class='name'><?= $data['lt']['na'] ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
	<div class='comdash-topcontributor'><?= $moreLink ?><span class='avatar'><?= $data['tp']['im'] ?></span><span>Leader</span><span class='name'><?= $data['tp']['na'] ?></span><span class='time'><?= $data['tp']['da'] ?></span></div>
</div>
<div class="comdash-widget-leaders">
	<div class="comdash-widget-leaders-content">
		<div class="comdash-widget-header">Leaders: <?= $title ?></div>
		<div class="comdash-widget-body">
			<table cellpadding="0" cellspacing="0">
				<tr class="first">
					<td class="avatar"><img src="http://pad1.whstatic.com/images/avatarOut/769792.jpg?20091019234315" /></td>
					<td class="user"><a href="#">Jack Herrick</a></td>
					<td class="count">23</td>
				</tr>
				<tr>
					<td class="avatar"><img src="http://pad1.whstatic.com/images/avatarOut/769792.jpg?20091019234315" /></td>
					<td class="user"><a href="#">Jack Herrick</a></td>
					<td class="count">23</td>
				</tr>
				<tr>
					<td class="avatar"><img src="http://pad1.whstatic.com/images/avatarOut/769792.jpg?20091019234315" /></td>
					<td class="user"><a href="#">Jack Herrick</a></td>
					<td class="count">123</td>
				</tr>
				<tr>
					<td class="avatar"><img src="http://pad1.whstatic.com/images/avatarOut/769792.jpg?20091019234315" /></td>
					<td class="user"><a href="#">Jack Herrick</a></td>
					<td class="count">3</td>
				</tr>
				<tr>
					<td class="avatar"><img src="http://pad1.whstatic.com/images/avatarOut/769792.jpg?20091019234315" /></td>
					<td class="user"><a href="#">Jack Herrick</a></td>
					<td class="count">3</td>
				</tr>
			</table>
		</div>
		<div class="comdash-widget-footer">
			<div class='comdash-lastcontributor'><span class='avatar'><?= $data['lt']['im'] ?></span><span>Last</span><span class='name'><?= $data['lt']['na'] ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
			<a href="#" class="comdash-close" id="comdash-close-<?= $widgetName ?>">Done</a>
		</div>
	</div>
</div>