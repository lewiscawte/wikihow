
	<? if (@$showAds): ?>
		<script type="text/javascript"><!--
		window.googleAfmcRequest = {
			client: 'ca-mb-pub-9543332082073187',
			ad_type: 'text_image',
			output: 'html',
			channel: '2856335553',
			format: '320x50_mb',
			oe: 'utf8',
			color_border: 'ece9e3',
			color_bg: 'ece9e3',
			color_link: '23198c',
			color_text: '000000',
			color_url: '3a6435'
		};
		//--></script>
		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_afmc_ads.js"></script>
	<? endif; ?>
	<div id="footer">
		<?= EasyTemplate::html('search-box') ?>
		<div id="footer_links">
			<a href="<?= $redirMainUrl ?>"><?= wfMsg('full-site') ?></a>
			<? if (!empty($editUrl)): ?>
				| <a href="<?= $editUrl ?>"><?= wfMsg('edit') ?></a>
			<? endif; ?>
			<a href="<?= $itunesUrl ?>" style="float:right"><?= wfMsg('try-iphone-app') ?></a>
		</div><!--end footer_links-->
	</div>
	<?= MobileWikihow::showDeferredJS() ?>
	<?= MobileWikihow::showBootStrapScript() ?>

	<script type="text/javascript">
		try {var pageTracker = _gat._getTracker("UA-2375655-1"); 
		pageTracker._setDomainName(".wikihow.com");
		pageTracker._trackPageview();} catch(err) {}
	</script>
	<?= wfReportTime() ?>
</body>
</html>
