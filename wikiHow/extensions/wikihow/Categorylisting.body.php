<?
class Categorylisting extends SpecialPage {

    function __construct($source = null) {
        SpecialPage::SpecialPage( 'Categorylisting' );
    }

	function execute($par) {
		global $wgOut;

		$this->setHeaders();
		$wgOut->setRobotpolicy('index,follow');
		$wgOut->addHTML(wfMsg('categorylisting_subheader'));
		$wgOut->addHTML(preg_replace('/\<[\/]?pre\>/', '', wfMsg( 'categorylisting_categorytable', wfGetPad() )));

		return;

	}
}
