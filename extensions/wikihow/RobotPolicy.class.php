<?
$wgHooks['BeforePageDisplay'][] = array("RobotPolicy::setUserProfileRobotPolicy");

class RobotPolicy {

	public static function setUserProfileRobotPolicy() {
		global $wgOut, $wgTitle;
		if (self::isFilterPage()
			&& (self::numEdits() < 20
				|| strpos($wgTitle->getText(), '/') !== false))
		{
			$wgOut->setRobotPolicy("noindex,follow");
		}
		return true;
	}

	private static function isFilterPage() {
		global $wgTitle;
		return $wgTitle->getNamespace() == NS_USER;
	}

	private static function numEdits() {
		global $wgTitle;
		if ($wgTitle->getNamespace() != NS_USER) {
			return 0;
		}
		$u = split("/", $wgTitle->getText());
		return User::getAuthorStats($u[0]);
	}
}
