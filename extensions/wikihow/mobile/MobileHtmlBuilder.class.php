<?
global $IP;
require_once("$IP/includes/SkinTemplate.php");

abstract class MobileHtmlBuilder {
	protected $deviceOpts = null;
	protected $nonMobileHtml = '';
	protected $t = null;
	private static $jsScripts = array();
	private static $jsScriptsCombine = array();
	private $cssScriptsCombine = array();

	public function createByRevision(&$t, &$r) {
		global $wgParser, $wgOut;

		$html = '';
		if(!$t) {
			return $html;
		}

		if ($r) {
			$popts = $wgOut->parserOptions();
			$popts->setTidy(true);
			$html = $wgParser->parse($r->getText(), $t, $popts, true, true, $r->getId());
			$html = $html->mText;
			$popts->setTidy(false);
			$html = $this->createByHtml($t, $html);
		}
		return $html;
	}

	public function createByHtml(&$t, &$nonMobileHtml) {
		if ((!$t || !$t->exists()) && !($this instanceof Mobile404Builder)) {
			return '';
		}

		$this->deviceOpts = MobileWikihow::getDevice();
		$this->t = $t;
		$this->nonMobileHtml = $nonMobileHtml;
		$this->setTemplatePath();
		$this->addCSSLibs();
		$this->addJSLibs();
		return $this->generateHtml();
	}

	private function generateHtml() {
		$html = '';
		$html .= $this->generateHeader();
		$html .= $this->generateBody();
		$html .= $this->generateFooter();
		return $html;
	}

	abstract protected function generateHeader();
	abstract protected function generateBody();
	abstract protected function generateFooter();

	protected function getDefaultHeaderVars() {
		global $wgRequest, $wgLanguageCode;

		$t = $this->t;
		$articleName = $t->getText();
		$action = $wgRequest->getVal('action', 'view');
		$deviceOpts = $this->getDevice();
		$pageExists = $t->exists();
		$randomUrl = '/' . wfMsg('special-randomizer');
		$isMainPage = $articleName == wfMsg('mainpage');
		$titleBar = $isMainPage ? wfMsg('mobile-mainpage-title') : wfMsg('pagetitle', $articleName);
		$canonicalUrl = 'http://' . MobileWikihow::getNonMobileSite() . '/' . $t->getPartialURL();
		$js = $wgLanguageCode == 'en' ? array('stu') : null;

		$headerVars = array(
			'isMainPage' => $isMainPage,
			'title' => $titleBar,
			'css' => $this->cssScriptsCombine,
			'js' => $js,  // only include stu js in header. The rest of the js will get loaded by showDeferredJS called in article.tmpl.php
			'randomUrl' => $randomUrl,
			'deviceOpts' => $deviceOpts,
			'canonicalUrl' => $canonicalUrl,
			'pageExists' => $pageExists,
			'jsglobals' => Skin::makeGlobalVariablesScript(array('skinname' => 'mobile')),
		);
		return $headerVars;
	}

	protected function getDefaultFooterVars() {
		global $wgRequest;
		$t = $this->t;
		$redirMainBase = '/' . wfMsg('special') . ':' . wfMsg('MobileWikihow') . '?redirect-non-mobile=';
		$footerVars = array(
			'showSharing' => !$wgRequest->getVal('share', 0),
			'isMainPage' => $t->getText() == wfMsg('mainpage'),
			'pageUrl' => $t->getFullUrl(),
			'showAds' => false,  //temporarily taking ads out of the footer
			'deviceOpts' => $this->getDevice(),
			'redirMainUrl' => $redirMainBase,
		);

			$footerVars['androidAppUrl'] = 'https://market.android.com/details?id=com.wikihow.wikihowapp';
			$footerVars['androidAppLabel'] = wfMsg('try-android-app');

			$footerVars['iPhoneAppUrl'] = 'http://itunes.apple.com/us/app/wikihow-how-to-diy-survival-kit/id309209200?mt=8';
			$footerVars['iPhoneAppLabel'] = wfMsg('try-iphone-app');

		return $footerVars;
	}

	public static function showDeferredJS() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			$vars = array(
				'scripts' => self::$jsScripts,
				'scriptsCombine' => self::$jsScriptsCombine,
			);
			return EasyTemplate::html('include-js.tmpl.php', $vars);
		} else {
			return '';
		}
	}

	public static function showBootStrapScript() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			return '<script>mobileWikihow.startup();</script>';
		} else {
			return '';
		}
	}
	
	protected function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	protected function getDevice() {
		return $this->deviceOpts;
	}

	protected function addJSLibs() {
		// We separate the lib from JS from the other stuff so that it can
		// be cached.  iPhone caches objects under 25k.
		self::addJS('mjq', false); // jQuery

		self::addJS('mwh', true); // wikiHow's mobile JS
		self::addJS('mga', true); // google analytics script
	}

	protected function addCSSLibs() {
		$this->addCSS('mwhc');
	}

	protected function addCSS($script) {
		$this->cssScriptsCombine[] = $script;
	}

	public static function addJS($script, $combine) {
		if ($combine) {
			self::$jsScriptsCombine[] = $script;
		} else {
			self::$jsScripts[] = $script;
		}
	}
}

class MobileArticleBuilder extends MobileBasicArticleBuilder {

	private function addCheckMarkFeatureHtml(&$vars) {
		global $IP;
		require_once("$IP/extensions/wikihow/checkmarks/CheckMarks.class.php");

		CheckMarks::injectCheckMarksIntoSteps($vars['sections']);
		$vars['checkmarks'] = CheckMarks::getCheckMarksHtml();
	}

	protected function addExtendedArticleVars(&$vars) {
		global $wgLanguageCode;

		if ($wgLanguageCode == 'en') {
			$this->addCheckMarkFeatureHtml($vars);
		}
		
		$vars['isTestArticle'] = $this->isTestArticle();
	}

	protected function isTestArticle() {
		static $testArticles = array(
			"Help-Someone-Having-a-Panic-Attack",
			"Write-a-Letter-of-Permission",
			"Start-Your-Own-Exercise-Regimen-and-Stick-to-It",
			"Find-Your-Lucky-Numbers-in-Numerology",
			"Write-an-Analytical-Essay",
			"Avoid-Having-Sagging-Breasts-As-A-Young-Woman",
			"Replace-Eggs-in-Your-Cooking",
			"Give-a-Foot-Massage",
			"Get-Rid-of-Shin-Splints",
			"Help-Someone-with-Depression",
			"Change-a-Tire",
			"Freeze-Strawberries",
			"Treat-a-Cold",
			"Marinate-a-Steak",
			"Cure-Alcohol-Breath",
			"Make-a-Dress",
			"Get-Your-Best-Friend-Back",
			"Stay-Up-All-Night-Alone-(for-Kids)",
			"Make-Eggs-in-a-Basket",
			"Make-School-Time-Fly",
			"Paint-Wine-Glasses",
			"RSVP",
			"Texas-Rig-a-Plastic-Worm",
			"Travel-With-One-Bag",
			"Braid-Hair",
			"Become-a-Lawyer-in-the-United-States",
			"Kill-a-Tick",
			"Be-Romantic",
			"Write-a-Movie-Review",
			"Turn-Your-Computer-Screen-Upside-Down",
			"Rap",
			"Smoke-in-Your-House-without-People-Knowing",
			"Fish",
			"Go-to-Bed-Early",
			"Block-Mobile-Phone-Spam",
			"Smoke-a-Tobacco-Pipe",
			"Soothe-a-Burnt-Tongue",
			"Know-if-You-Love-Someone",
			"Strengthen-Your-Wrists",
			"Build-an-Effective-Pokemon-Deck-(TCG)",
			"Get-Your-Ex-Back",
			"Change-a-Windows-XP-Product-Key",
			"Build-a-Go-Kart",
			"Reduce-Expenses",
			"Roll-a-Cigarette",
			"Treat-an-Ankle-Sprain",
			"Break-in-a-New-Baseball-Glove",
			"Make-Someone-Fall-in-Love-with-You",
			"Give-Your-Boots-That-Military-Mirror-Shine",
			"Make-Hot-Sauce",
			"Get-a-Girl-Way-out-of-Your-League",
			"Become-a-Fashion-Designer",
			"Order-at-Starbucks",
			"Be-a-Good-Listener",
			"Change-a-Car-Battery",
			"Sew-Using-Patterns",
			"Make-Paper",
			"Remove-a-Splinter-Under-Your-Fingernail",
			"Make-Jacob's-Ladder-out-of-String",
			"Write-a-Thank-You-Note-for-Money",
			"Write-a-Journal-Entry",
			"Get-Kissable-Lips",
			"Solve-Differential-Equations",
			"Buy-Gold",
			"Be-a-Hipster-Girl",
			"Keep-Your-Navel-Piercing-Clean",
			"Make-a-Pinwheel",
			"Send-Pictures-from-Your-Cell-Phone-to-Your-Computer",
			"Make-a-Rubber-Band-Ball",
			"Unfriend-Someone-on-Facebook-Without-Actually-Unfriending-Them",
			"Keep-Your-Man-Happy",
			"Get-Fit-in-the-Gym",
			"Exercise-for-Firmer-Boobs-and-Butts",
			"Relieve-Bloating-With-Food",
			"Stand-up-for-Yourself",
			"Read-a-Weather-Map",
			"Cope-with-a-Stomach-Flu",
			"Start-a-Charity",
			"Make-a-Color-Image-Look-Like-a-Sketch-in-Photoshop",
			"Build-a-Home-Studio-for-Computer-Based-Music-Recording",
			"Make-Carrot-Juice",
			"Heal-Acne-Fast-and-Naturally",
			"Live-in-the-Moment",
			"Repair-a-Fiberglass-Tub-or-Shower",
			"Wake-Up-On-Time",
			"Get-Your-Girlfriend-a-Great-Birthday-Present",
			"Make-a-Mini-Flyable-Hot-Air-Balloon-with-Candles",
			"Make-a-Pop-Up-Card",
			"Make-Natural-Outdoor-Fly-Repellent-with-Essential-Oils",
			"Make-Clothes-Look-Vintage-and-Worn",
			"Deal-With-a-Bad-Kisser",
			"Upgrade-a-Processor",
			"Dress-in-the-American-1950s-Fashion",
			"Check-an-Electric-Motor",
			"Remove-Followers-on-Twitter",
			"Create-an-American-1940's-Hairstyle",
			"Let-Go-of-Someone-Who-You-Deeply-Loved",
			"Kiss-With-Braces",
			"Freeze-Cakes",
			"Turn-Salt-Water-Into-Drinking-Water",
			"Stop-Loving-Someone-Who-Doesn't-Love-You",
			"Make-Almond-Milk",
			"Repair-a-Zipper-when-the-Slider-Has-Come-Off-Completely",
			"Calculate-Mean-and-Standard-Deviation-With-Excel-2007",
			"Get-a-Badonkadonk",
			"Be-Assertive",
			"Have-Beautiful-Silky,-Shiny,-Straight-Hair",
			"Remove-Odors-from-Your-Car",
			"Choose-Between-a-Car-with-Automatic-or-Manual-Transmission",
			"Set-up-an-FTP-Server-in-Ubuntu-Linux",
			"Prevent-Scarring",
			"Use-a-Colon-in-a-Sentence",
			"Hook-Up-with-a-Girl-Who-Has-a-Boyfriend",
			"Create-Good-Study-Habits-for-Exams",
			"Develop-the-'Sherlock-Holmes'-Intuition",
			"Strengthen-Your-Ankles",
			"Set-up-a-Joomla-Website",
			"Stop-Bleeding",
			"Clean-a-Microwave",
			"Grow-Basil-from-Seed",
			"Be-a-Great-Host-or-Hostess-in-a-Restaurant",
			"Open-Your-Spiritual-Chakras",
			"Ignore-People-You-No-Longer-Wish-to-Be-Around",
			"Make-Yourself-Famous-on-YouTube",
			"Win-More-Scratch-Offs",
			"Keep-a-Sharp-Mind-and-Good-Attitude",
			"Get-Rid-of-Bacne",
			"Act-Around-Girls",
			"Deal-With-Stress",
			"Make-a-PowerPoint-Presentation-That-Includes-Audio-and-Video-Files",
			"Hypnotize-Yourself-Using-the-Best-Me-Technique",
			"Lighten-Your-Hair-With-Cinnamon",
			"Write-a-Business-Letter",
			"Respect-Yourself",
			"Gain-Muscle-Fast",
			"Get-Rid-of-Gophers",
			"Use-Pinterest",
			"Color-Hair-With-Food-Coloring",
			"Make-a-Funnel-or-Cone-from-Paper",
			"Turn-a-Windows-PC-Into-a-Mac",
			"Get-a-Carfax-for-Free",
			"Kiss-a-Girl-Smoothly-with-No-Chance-of-Rejection",
			"Hack-Windows-with-a-Limited-Account",
			"Get-a-Man-to-Leave-His-Wife",
			"Buy-Firearms-in-California",
			"Write-With-Your-Left-Hand-(if-Right-Handed)",
			"Value-Your-Pokemon-Cards",
			"Treat-a-Corn-or-Callus",
			"Learn-to-Use-Linux",
			"Eat-Like-a-Body-Builder",
			"Get-Wider-Shoulders",
			"Dramatically-Speed-up-Windows-XP",
			"Treat-a-Girl-Well",
			"Make-a-Rope-Braid",
			"Fell-a-Tree",
			"Create-an-RSS-Feed",
			"Avoid-Looking-Like-an-American-Tourist",
			"Care-for-a-Bromeliad",
			"Make-Frizzy-or-Curly-Hair-Into-Straight-Hair",
			"Choose-the-Right-Hair-Colour",
			"Treat-Road-Rash",
			"Make-Caramel-from-Sweetened-Condensed-Milk",
			"Take-Care-of-Lucky-Bamboo",
			"Pass-a-Drug-Test-in-Short-Notice",
			"Create-a-Stop-Motion-Animation",
			"Get-a-Girlfriend-in-Middle-School",
			"Peel-an-Egg",
			"Switch-from-Yahoo!-Mail-to-Gmail",
			"Sterilize-Baby-Bottles",
			"Fix-a-Huge-Argument-with-Your-Girlfriend",
			"Overcome-Your-Fear-of-Roller-Coasters",
			"Remove-Grease-Stains",
			"Grind-(for-Girls)",
			"Draw-a-Floor-Plan-to-Scale",
			"Arc-Weld",
			"Drink-Green-Tea-Without-the-Side-Effects",
			"Know-When-to-Break-Up",
			"Fake-a-Fever",
			"Break-up-Concrete",
			"Make-a-T-Shirt-a-Tank-Top",
			"Address-Catholic-Clergy",
			"Calculate-the-Volume-of-a-Cone",
			"Get-a-Title-to-an-Abandoned-Vehicle",
			"Make-Yourself-Tired-So-That-You'll-Fall-Asleep",
			"Read-12-Digit-UPC-Barcodes",
			"Change-Your-Last-Name",
			"Not-Lose-Your-Girlfriend",
			"Measure-Biodiversity-for-a-College-Field-Project",
			"Treat-Thrush",
			"Make-Your-Own-White-Board-(Dry-Erase-Board)",
			"Build-a-House",
			"Clean-Tough-Stains-from-a-Bathtub",
			"Take-Compliments",
			"Defuse-a-Situation-With-a-Difficult-Customer",
			"Make-Your-Bedroom-Unique",
			"Do-the-Caveman-Diet",
			"Adapt-a-Mousetrap-Car-for-Distance",
			"Be-a-Perfect-Girl",
			"Change-a-Computer-Password-Using-Command-Prompt",
			"Look-Good-In-Your-School-Uniform",
			"Break-a-BIOS-Password",
			"Care-for-Newly-Pierced-Ears",
			"Take-a-Screenshot-of-the-Entire-Screen",
			"React-when-Insulted-or-Teased",
			"Look-Good-Without-Makeup",
			"Survive-in-the-Woods",
			"Make-a-Baking-Soda-and-Vinegar-Rocket",
			"Calculate-a-Test-Grade",
			"Import-Archived-Outlook-Email-Into-Gmail-Using-GML",
			"Talk-to-Your-Crush-Even-Though-You-Are-Shy",
			"Make-Wedding-Invitations",
			"Pack-for-a-Move",
			"Look-Cute-in-Front-of-Your-Ex,-Crush,-or-Boyfriend-(Teen-Girls-Only)",
			"Romantically-Hug-a-Guy",
			"Get-Out-of-Debt",
			"Be-a-Nude-Art-Model",
			"Start-a-Friends-With-Benefits-Relationship",
			"Get-a-Good-Man",
			"Make-Frosting",
			"Do-a-Good-Base-Coat-Clear-Coat-Paint-Job",
			"Not-Be-Annoying",
			"Create-a-Good-Documentary-Film",
			"Change-Your-Look-Over-the-Summer",
			"Start-a-Revolution",
			"Survive-a-Nuclear-Attack",
			"Dreadlock-Straight-Hair",
			"Choose-a-Dress-Shirt",
			"Fold-a-Paper-Crane",
			"File-Bankruptcy-in-the-United-States",
			"Get-Car-Loans-After-Bankruptcy",
			"Turn-Jeans-into-Shorts",
			"Hold-in-Pee-when-You-Can't-Use-the-Bathroom",
			"Build-Trust",
			"Make-a-Sock-Monkey",
			"Ask-Open-Ended-Questions",
			"Customise-Converse-Trainers",
			"Text-Your-Crush-and-Start-a-Conversation",
			"Avoid-Copyright-Infringement",
			"Plant-Flowers",
			"Make-and-Use-Iron-on-Transfers",
			"Make-Pictures-out-of-Text",
			"Eat-a-Kiwano-(Horned-Melon)",
			"Make-Distressed-Jeans",
			"Get-a-Man-(for-Gay-Men)",
			"Be-Irresistible-to-Men",
			"Encourage-a-Woman-to-Fall-in-Love-with-You",
			"Be-a-Spy",
			"Tell-if-Your-Best-Friend-Loves-You",
			"Connect-a-DVD-Player,-VCR,-and-Digital-Cable-Box",
			"Apply-Magnetic-Nail-Polish",
			"Make-a-3D-Paper-Snowflake",
			"Look-Like-a-Pornstar",
			"Prepare-Tofu",
			"Get-Skinny-Legs-Quick",
			"Refill-and-Reuse-a-Printer-Cartridge",
			"Run-Away-From-Home",
			"Set-Up-an-Ergonomically-Correct-Workstation",
			"Recover-Data-from-the-Hard-Drive-of-a-Dead-Laptop",
			"Detect-Hidden-Cameras-and-Microphones",
			"Survive-in-Federal-Prison",
			"Uninstall-McAfee-Security-Center",
			"Grow-Banana-Plants",
			"Type",
			"Tell-if-You-Genuinely-Like-Someone",
			"Host-a-Sleepover-(Teen-Girls)",
			"Prevent-Razor-Burn",
			"Grow-a-Clover-Lawn",
			"Cure-a-Child's-Stomach-Ache",
			"Give-Your-Boyfriend-Space",
			"Cheer-Up-a-Friend-After-a-Breakup",
			"Remember-Your-Past-Lives",
			"Deal-With-Falling-in-Love-with-Your-Best-Friend-(for-Guys)",
			"Get-and-Maintain-a-Healthy-Lawn",
			"Be-a-Great-Waitress",
			"Give-a-Back-Massage",
			"Increase-Your-Appetite",
			"Eat-Properly",
			"Pierce-Your-Own-Belly-Button-at-Home",
			"Make-Fried-Oreos",
			"Make-Your-Girlfriend-Want-to-Have-Sex-With-You",
			"Make-Cheap-Wine",
			"Wash-Clothes-by-Hand",
			"Enjoy-Being-Alone",
			"Keep-Chickens-from-Eating-Their-Own-Eggs",
			"Get-Companies-to-Send-You-Free-Stuff",
			"Reset-a-Lost-iPod-Password",
			"Look-and-Act-Like-Cat-Valentine-from-Victorious",
			"Relieve-Heartburn",
			"Be-a-Waiter",
			"Tease-Your-Hair",
			"Get-a-Girl-to-Like-You-when-She-Has-No-Interest-in-Relationships",
			"Grow-Pitcher-Plants",
			"Answer-the-Phone-Politely",
			"Construct-a-Raised-Planting-Bed",
			"Simplify-a-Square-Root",
			"Get-Rid-of-Old-Books",
			"Act-Around-a-Guy-You-Like",
			"Stop-Picking-Your-Nose",
			"Avoid-a-Teenage-Pregnancy",
			"Be-Humble",
			"Tie-a-Hangmans-Noose",
			"Use-Telnet-on-Mac-OS-X",
			"Eliminate-a-Flea-Infestation-in-Your-Home",
			"Make-Laptop-Screens-Readable-Outdoors",
			"Add-a-Layer-Mask-in-Photoshop",
			"Make-Windows-7-Search-File-Contents",
			"Get-a-Guy-That-Likes-You-to-Ask-You-Out",
			"Make-Your-Dog-Smell-Better",
			"Make-a-Chocolate-Cake",
			"Drain-and-Refill-Your-Swimming-Pool",
			"Install-phpMyAdmin-on-Your-Windows-PC",
			"Learn-to-Sing",
			"Become-a-Millionaire",
			"Remember-Dreams",
			"Be-Creative",
			"Make-Vegan-Pepperoni",
			"Teach-the-Multiplication-Tables-to-Your-Child",
			"Hold-and-Use-a-Cane-Correctly",
			"Power-Nap",
			"Perm-Your-Hair",
			"Test-Network-and-Internet-Latency-(Lag)-in-Microsoft-Windows",
			"Rotate-Text-in-Microsoft-Word",
			"Know-if-a-Girl-Doesn't-Like-You-Back",
			"Tell-if-a-Female-Friend-Wants-a-Romantic-Relationship",
			"Crop-Pages-in-a-PDF-Document",
			"Release-Anger",
			"French-Twist-Hair",
			"Get-Over-the-Crush-on-Your-Best-Friend",
			"Copy-and-Paste-PDF-Content-Into-a-New-File",
			"Create-a-Bonsai-Tree",
			"Play-Beer-Pong",
			"Pray",
			"Paint-with-a-Compressed-Air-Sprayer",
			"Ground-Yourself-to-Avoid-Destroying-a-Computer-with-Electrostatic-Discharge",
			"Care-for-a-\"Red-Eared-Slider\"-Turtle",
			"Get-in-Shape",
			"Be-Classy",
			"Make-Croissants",
			"Make-a-Flea-Trap",
			"Stop-Worrying-and-Start-Living",
			"Fix-a-Scratch-on-an-LCD-Screen",
			"Get-Into-an-Ivy-League-School",
			"Determine-a-Building's-Construction-Type",
			"Make-a-Diamond-Kite",
			"Highlight-Your-Hair-Naturally",
			"Drive-a-Car-With-an-Automatic-Transmission",
			"Sail-a-Boat",
			"Finger-Knit",
			"Sift-Flour-Without-a-Sifter",
			"Look-Fabulous-at-a-Concert",
			"Groom-a-Dog",
			"Do-Crunches",
			"Copyright-a-Book",
			"Make-an-Origami-Jumping-Frog",
			"Wear-White-Dresses",
			"Make-Ginger-Ale",
			"Convert-WMV-to-AVI",
			"Get-Baby-Soft-Skin",
			"Know-if-You-Have-a-Crush-on-a-Guy",
			"Style-Scene-Hair",
			"Boil-a-Hot-Dog",
			"Make-the-Best-Hookah-for-a-Pleasurable-Smoke",
			"Dye-Hair",
			"Make-Kombucha-Tea",
			"Cure-Toenail-Fungus-With-Vinegar",
			"Tell-when-a-Guy-Is-Using-You-for-Sex",
			"Know-If-You-Are-a-Lesbian",
			"Make-a-Tuna-Sandwich",
			"Lose-Water-Weight",
			"Get-Smooth-Legs",
			"Make-Feta-Cheese",
			"Apply-for-a-Job",
			"Make-Silk,-Satin-or-Ribbon-Roses",
			"Toilet-Train-Your-Cat",
			"Help-a-Cat-Give-Birth",
			"Create-an-Inexpensive-Photography-Lightbox",
			"Restrict-Web-Browsing-Using-Internet-Explorer",
			"Juggle-a-Soccer-Ball",
			"Motivate-Yourself-to-Work-Out",
			"Not-Get-Nervous",
			"Speed-up-Hair-Growth",
			"Dress-Like-a-Hippie",
			"Organize-a-Flash-Mob",
			"Bake-a-Cake",
			"Make-Paneer-(Indian-Cheese)",
			"Make-Alcohol-from-Common-Table-Sugar",
			"Make-a-Strawberry-Banana-Smoothie",
			"Get-More-Intimate-Without-Having-Sex",
			"Show-a-Girl-You-Like-Her",
			"Lace-Vans-Shoes",
			"Make-a-Computer-Operating-System",
			"Write-an-Agenda-for-a-Meeting",
			"Have-a-Witty-Conversation",
			"Plant-a-Tree",
			"Look-Good-in-Pictures",
			"Build-a-Picnic-Table",
			"Change-Text-Color-in-HTML",
			"Crochet-a-Granny-Square",
			"Breed-Betta-Fish",
			"See-if-He's-the-One",
			"Make-a-Short-Film",
			"Find-out-What-Your-Spiritual-Animal-Is",
			"Practice-Random-Acts-of-Kindness",
			"Print-from-Your-iPhone",
			"Turn-a-Girl-Friend-Into-a-Girlfriend",
			"Guess-Someone's-Astrological-Sign",
			"Freestyle-Rap",
			"Have-Fun-Alone",
			"Put-Anchor-Screws-in-a-Wall",
			"Write-to-HM-Queen-Elizabeth-II",
			"Sell-Your-Pokemon-Cards",
			"Apply-Makeup-for-a-Natural-Look",
			"Custom-Paint-Your-Electric-Guitar",
			"Find-Out-if-Your-Phone-Is-Unlocked",
			"Calculate-the-Distance-from-Lightning",
			"Be-a-Lady",
			"Remove-Gel-Nails",
			"Deodorize-a-Room",
			"Know-when-You-Love-Him-or-Her",
			"Ask-Rich-People-for-Money",
			"Write-a-Research-Paper",
			"Replace-a-Bicycle-Tire",
			"Introduce-a-New-Dog-to-Your-House-and-Other-Dogs",
			"Check-Your-iPod's-Generation",
			"Fold-Paper-Into-a-Secret-Note-Square",
			"Make-Lye",
			"Sprint-Faster",
			"Play-the-Flute",
			"Make-a-Huge-Blister-Heal",
			"Hold-Hands",
			"Fall-Asleep-Easier-As-a-Teen",
			"Make-Your-Vagina-Smell-Good",
			"Attract-a-Sagittarius-Man",
			"Talk-to-a-Guy-Who-Doesn't-Like-You-Anymore",
			"Make-a-Poster",
			"Remove-Latex-Paint-from-Clothes",
			"Download-YouTube-Videos",
			"Write-a-Letter-to-Your-Teacher",
			"Fold-a-Dollar-Into-a-Heart",
			"Harvest-Rhubarb",
			"Remove-Musty-Smell-from-Clothes",
			"Pierce-Your-Own-Lip",
			"Look-Drop-Dead-Gorgeous",
			"Write-a-Book",
			"Make-Oatmeal",
			"Smile-With-the-Eyes",
			"Calculate-Cube-Root-by-Hand",
			"Be-a-Good-Parent",
			"Make-Your-Fingernails-Look-Good",
			"Secure-Your-Wireless-Home-Network",
			"Get-Your-Girlfriend-to-Kiss-or-Hug-You-More-Often",
			"Find-Your-Stolen-Phone",
			"Get-360-Waves",
			"Improve-Your-Singing-Voice-Without-Taking-Singing-Lessons",
			"Add-an-Extra-Hard-Drive",
			"Relieve-a-Tension-Headache",
			"Drill-Into-Concrete",
			"Write-a-Ballad",
			"Buy-a-House",
			"Transfer-Data-Between-a-Cell-Phone-and-Computer",
			"Choose-What-Color-to-Wear",
			"Tell-if-Jade-Is-Real",
			"Physically-Flirt-with-a-Girl",
			"Make-an-Ice-Cream-Cake",
			"Make-KFC-Original-Fried-Chicken",
			"Be-a-Scene-Girl",
			"Clean-a-Clothes-Dryer-Vent",
			"Keep-Your-Girlfriend-Happy",
			"Create-a-Google-Map-With-Excel-Data-and-Fusion-Tables",
			"Make-Your-Boyfriend-Love-You-More",
			"Date-an-Aries-Female",
			"Infuse-Vodka-with-Flavor",
			"Make-Your-Own-Whipped-Cream",
			"Make-Lemonade",
			"Cure-Jock-Itch",
			"Clean-Copper",
			"Make-a-Sandwich",
			"Dress-Indie",
			"Convince-Someone-to-Trust-You-Again",
			"Talk-to-Someone-You've-Never-Met",
			"Know-You-Are-Getting-Your-Period",
			"Make-a-Ladder-Golf-Game",
			"Do-String-Figures",
			"Make-a-Peanut-Butter-and-Jelly-Sandwich",
			"Tie-Dye-a-Shirt",
			"Connect-One-Router-to-Another-to-Expand-a-Network",
			"Spell-Commonly-Misspelled-Words",
			"Compress-Large-Files",
			"Go-from-Introvert-to-Extrovert",
			"Tell-Your-Boyfriend-You-Want-to-Have-Sex",
			"Beat-Boredom-over-the-Summer",
			"Calculate-Center-of-Gravity",
			"Stop-Thinking-Too-Much",
			"Heal-a-Broken-Heart",
			"Get-Your-Long-Term-Boyfriend-to-Propose-You",
			"Create-a-New-Facebook-Group",
			"Hatch-Turkey-Eggs-in-an-Incubator",
			"Remove-a-Sticker-from-Glass",
			"Be-Emo",
			"Pry-off-a-Watch-Backing-Without-Proper-Tools"
		);

		return in_array($this->t->getDBKey(), $testArticles) !== false ? true : false;
	}

	protected function addCSSLibs() {
		global $wgLanguageCode;

		parent::addCSSLibs();
		if ($wgLanguageCode == 'en') {
			$this->addCSS('mcmc'); // Checkmark css
		}
	}

	protected function addJSLibs() {
		global $wgLanguageCode;

		parent::addJSLibs();
		if ($wgLanguageCode == 'en') {
			self::addJS('cm', true); // checkmark js
		}
	}
}
class MobileBasicArticleBuilder extends MobileHtmlBuilder {

	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function getArticleParts() {
		return $this->parseNonMobileArticle($this->nonMobileHtml);
	}


	protected function generateBody() {
		global $wgLanguageCode;

		list($sections, $intro, $firstImage) = $this->getArticleParts();
		if ($firstImage) {
			$title = Title::newFromURL($firstImage, NS_IMAGE);
			if ($title) {
				$introImage = RepoGroup::singleton()->findFile($title);
			}
			if ($introImage) {
				$thumb = $introImage->getThumbnail(290, 194);
				$width = $thumb->getWidth();
				$height = $thumb->getHeight();
			} else {
				$firstImage = '';
			}
		} 

		//articles that we don't want to have a top (above tabs)
		//image displayed
		$titleUrl = "";
		if($this->t != null)
			$titleUrl = $this->t->getFullURL();
		$exceptions = ConfigStorage::dbGetConfig('mobile-topimage-exception');
		$exceptionArray = explode("\n", $exceptions);
		if(in_array($titleUrl, $exceptionArray)) {
			$firstImage = false;
		}

		if (!$firstImage) {
			$thumb = null;
			$width = 0; $height = 0;
		}

		$deviceOpts = $this->getDevice();
		$articleVars = array(
			'title' => $this->t->getText(),
			'sections' => $sections,
			'intro' => $intro,
			'thumb' => &$thumb,
			'width' => $width,
			'height' => $height,
			'deviceOpts' => $deviceOpts,
			'nonEng' => $wgLanguageCode != 'en',
			'isGerman' => $wgLanguageCode == 'de',
		);
		$this->addExtendedArticleVars(&$articleVars);

		$this->setTemplatePath();
		return EasyTemplate::html('article.tmpl.php', $articleVars);
	}

	protected function addExtendedArticleVars(&$vars) {
		// Nothing to add here. Used for subclasses to inject variables to be passed to article.tmpl.php html
	}
	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		$t = $this->t;
		$partialUrl = $t->getPartialURL();
		$footerVars['redirMainUrl'] = $footerVars['redirMainUrl'] . urlencode($partialUrl);
		$baseMainUrl = 'http://' . MobileWikihow::getNonMobileSite() . '/';
		$footerVars['editUrl'] = $baseMainUrl . 'index.php?action=edit&title=' . $partialUrl;
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	protected function addCSSLibs() {
		parent::addCSSLibs();
		$this->addCSS('mwha');
	}

	protected function addJSLibs() {
		parent::addJSLibs();
	}

	/**
	 * Parse and transform the document from the old HTML for NS_MAIN articles to the new mobile
	 * style. This should probably be pulled out and added to a subclass that can then be extended for
	 * builders that focus on building NS_MAIN articles
	 */
	protected function parseNonMobileArticle(&$article) {
		global $wgWikiHowSections, $IP, $wgContLang;

		$sectionMap = array(
			wfMsg('Intro') => 'intro',
			wfMsg('Ingredients') => 'ingredients',
			wfMsg('Steps') => 'steps',
			wfMsg('Video') => 'video',
			wfMsg('Tips') => 'tips',
			wfMsg('Warnings') => 'warnings',
			wfMsg('relatedwikihows') => 'relatedwikihows',
			wfMsg('sourcescitations') => 'sources',
			wfMsg('thingsyoullneed') => 'thingsyoullneed',
		);

		$lang = MobileWikihow::getSiteLanguage();
		$imageNsText = $wgContLang->getNsText(NS_IMAGE);
		$device = MobileWikihow::getDevice();

		// munge steps first
		$opts = array(
			'no-ads' => true,
		);
		require_once("$IP/skins/WikiHowSkin.php");
		$article = WikiHowTemplate::mungeSteps($article, $opts);

		// Make doc correctly formed
$articleText = <<<DONE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="$lang" lang="$lang">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset='utf-8'" />
</head>
<body>
$article
</body>
</html>
DONE;
		require_once("$IP/extensions/wikihow/mobile/JSLikeHTMLElement.php");
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
		$doc->strictErrorChecking = false;
		$doc->recover = true;
		//$doc->preserveWhiteSpace = false;
		//$wgOut->setarticlebodyonly(true);
		@$doc->loadHTML($articleText);
		$doc->normalizeDocument();
		//echo $doc->saveHtml();exit;
		$xpath = new DOMXPath($doc);

		// Delete #featurestar node
		$node = $doc->getElementById('featurestar');
		if (!empty($node)) {
			$node->parentNode->removeChild($node);
		}

		// Remove all "Edit" links
		$nodes = $xpath->query('//a[@id = "gatEditSection"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// Resize youtube video
		$nodes = $xpath->query('//embed');
		foreach ($nodes as $node) {
			$url = '';
			$src = $node->attributes->getNamedItem('src')->nodeValue;
			if (!$device['show-youtube'] || stripos($src, 'youtube.com') === false) {
				$parent = $node->parentNode;
				$grandParent = $parent->parentNode;
				if ($grandParent && $parent) {
					$grandParent->removeChild($parent);
				}
			} else {
				foreach (array(&$node, &$node->parentNode) as $node) {
					$widthAttr = $node->attributes->getNamedItem('width');
					$oldWidth = (int)$widthAttr->nodeValue;
					$newWidth = $device['max-video-width'];
					if ($newWidth < $oldWidth) {
						$widthAttr->nodeValue = (string)$newWidth;

						$heightAttr = $node->attributes->getNamedItem('height');
						$oldHeight = (int)$heightAttr->nodeValue;
						$newHeight = (int)round($newWidth * $oldHeight / $oldWidth);
						$heightAttr->nodeValue = (string)$newHeight;
					}
				}
			}
		}

		/*
		// Remove <a name="..."></a> tags
		$nodes = $xpath->query('//a[@name and not(@href)]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		*/

		// Remove templates from intro so that they don't muck up
		// the text and images we extract
		$nodes = $xpath->query('//div[@class = "template_top"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		//self::walkTree($doc->documentElement, 1);exit;
		//echo $doc->saveHtml();exit;

		// Grab intro text
		$intro = '';
		$nodes = $xpath->query('//body/div/p');
		foreach ($nodes as $i => $node) {
			$text = $node->textContent;
			if (!empty($text) || $i == 1) {
				$introNode = $node;
				$intro = $text;
				break;
			}
		}

		if ($introNode) {
			// Grab first image from article
			$imgs = $xpath->query('//img', $introNode);
			$firstImage = '';
			foreach ($imgs as $img) {
				// parent is an <a> tag
				$parent = $img->parentNode;
				if ($parent->nodeName == 'a') {
					$href = $parent->attributes->getNamedItem('href')->nodeValue;
					if (preg_match('@(Image|' . $imageNsText . '):@', $href)) {
						$firstImage = preg_replace('@^.*(Image|' . $imageNsText .'):([^:]*)([#].*)?$@', '$2', $href);
						$firstImage = urldecode($firstImage);
						break;
					}
				}
			}

			// Remove intro node
			$parent = $introNode->parentNode;
			$parent->removeChild($introNode);
		}

		// Get rid of the <span> element to standardize the html for the
		// next dom query
		$nodes = $xpath->query('//div/span/a[@class = "image"]');
		foreach ($nodes as $a) {
			$parent = $a->parentNode;
			$grandParent = $parent->parentNode;
			$grandParent->replaceChild($a, $parent);
		}

		// Resize all resize-able images
		$nodes = $xpath->query('//div/a[@class = "image"]/img');
		$imgNum = 1;
		foreach ($nodes as $img) {
			
			$srcNode = $img->attributes->getNamedItem('src');
			$widthNode = $img->attributes->getNamedItem('width');
			$width = (int)$widthNode->nodeValue;
			$heightNode = $img->attributes->getNamedItem('height');
			$height = (int)$heightNode->nodeValue;

			$imageClasses = $img->parentNode->parentNode->parentNode->attributes->getNamedItem('class')->nodeValue;
			
			if( stristr($imageClasses, "tcenter") !== false && $width >= 500) {
				$newWidth = $device['full-image-width'];
				$newHeight = (int)round($device['full-image-width'] * $height / $width);
			}
			else {
				$newWidth = $device['max-image-width'];
				$newHeight = (int)round($device['max-image-width'] * $height / $width);
			}
			// Image: link is gone now with zooming changes!
			//$href = $a->attributes->getNamedItem('href')->nodeValue;
			//$imgName = preg_replace('@^/Image:@', '', $href);
			//$src = $srcNode->nodeValue;
			//$imgName = preg_replace('@^/images/thumb/./../([^/]+)/.*$@', '$1', $src);
			$a = $img->parentNode;
			$href = $a->attributes->getNamedItem('href')->nodeValue;
			if (!$href) {
				$onclick = $a->attributes->getNamedItem('onclick')->nodeValue;
				$onclick = preg_replace('@.*",[ ]*"@', '', $onclick);
				$onclick = preg_replace('@".*@', '', $onclick);
				$imgName = preg_replace('@.*(Image|' . $imageNsText . '):@', '', $onclick);
			} else {
				$imgName = preg_replace('@^/(Image|' . $imageNsText . '):@', '', $href);
			}
			
			$title = Title::newFromURL($imgName, NS_IMAGE);
			if (!$title) {
				$imgName = urldecode($imgName);
				$title = Title::newFromURL($imgName, NS_IMAGE);
			}

			if ($title) {
				$image = RepoGroup::singleton()->findFile($title);
				if ($image) {
					$thumb = $image->getThumbnail($newWidth, $newHeight);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$srcNode->nodeValue = $url;
					$widthNode->nodeValue = $newWidth;
					$heightNode->nodeValue = $newHeight;
					
					// change surrounding div width and height
					$div = $a->parentNode;
					$styleNode = $div->attributes->getNamedItem('style');
					if (preg_match('@^(.*width:)[0-9]+(px;\s*height:)[0-9]+(.*)$@', $styleNode->nodeValue, $m)) {
						$styleNode->nodeValue = $m[1] . $newWidth . $m[2] . $newHeight . $m[3];
					}

					// change grandparent div width too
					$grandparent = $div->parentNode;
					if ($grandparent && $grandparent->nodeName == 'div') {
						$class = $grandparent->attributes->getNamedItem('class');
						if ($class && $class->nodeValue == 'thumb tright') {
							$style = $grandparent->attributes->getNamedItem('style');
							$style->nodeValue = 'width:' . $newWidth . 'px;';
						}
						else if($class && $class->nodeValue == 'thumb tcenter'){
							$style = $grandparent->attributes->getNamedItem('style');
							$style->nodeValue = 'width:' . $newWidth . 'px;';
						}
						else if ($class && $class->nodeValue == 'thumb tleft') {
							//if its centered or on the left, give it double the width if too big
							$style = $grandparent->attributes->getNamedItem('style');
							$oldStyle = $style->nodeValue;
							$matches = array();
							preg_match('@(width:\s*)[0-9]+@', $oldStyle, $matches);
							
							if($matches[0]){
								$curSize = intval(substr($matches[0], 6)); //width: = 6
								if($newWidth*2 < $curSize){
									$existingCSS = preg_replace('@(width:\s*)[0-9]+@', 'width:'.$newWidth*2, $oldStyle);
									$style->nodeValue = $existingCSS;
								}
							}
						}
					}

					$thumb = $image->getThumbnail($device['image-zoom-width'], $device['image-zoom-height']);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$a->setAttribute('id', 'image-zoom-' . $imgNum);
					$a->setAttribute('class', 'image-zoom');
					$a->setAttribute('href', '#');
					$details = array(
						'url' => $url,
						'width' => $newWidth,
						'height' => $newHeight,
					);
					$newDiv = new DOMElement( 'div', htmlentities(json_encode($details)) );
					$a->appendChild($newDiv);
					$newDiv->setAttribute('style', 'display:none;');
					$newDiv->setAttribute('id', 'image-details-' . $imgNum);
					$imgNum++;
				}
			}
		}

		// Remove template from images, add new zoom one
		$nodes = $xpath->query('//img');
		foreach ($nodes as $node) {
			$src = ($node->attributes ? $node->attributes->getNamedItem('src') : null);
			$src = ($src ? $src->nodeValue : '');
			if (stripos($src, 'magnify-clip.png') !== false) {
				$parent = $node->parentNode;
				$parent->parentNode->removeChild($parent);
			}
		}

		// Change the width attribute from any tables with a width set.
		// This often happen around video elements.
		$nodes = $xpath->query('//table/@width');
		foreach ($nodes as $node) {
			$width = preg_replace('@px\s*$@', '', $node->nodeValue);
			if ($width > $device['screen-width'] - 20) {
				$node->nodeValue = $device['screen-width'] - 20;
			}
		}

		// Surround step content in its own div. We do this to support other features like checkmarks
		$nodes = $xpath->query('//div[@id="steps"]/ol/li');
		foreach ($nodes as $node) {
			$node->innerHTML = '<div class="step_content">' . $node->innerHTML . '</div>';
		}

		//self::walkTree($doc->documentElement, 1);
		$html = $doc->saveXML();

		$sections = array();
		$sectionsHtml = explode('<h2>', $html);
		unset($sectionsHtml[0]); // remove leftovers from intro section
		foreach ($sectionsHtml as $i => &$section) {
			$section = '<h2>' . $section;
			if (preg_match('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)@i', $section, $m)) {
				$heading = trim($m[1]);
				$section = preg_replace('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)</span>(\s|\n)*</h2>@i', '', $section);
				if (isset($sectionMap[$heading])) {
					$key = $sectionMap[$heading];
					$sections[$key] = array(
						'name' => $heading,
						'html' => $section,
					);
				}
			}
		}
		
		// Remove Video section if there is no longer a youtube video
		if (isset($sections['video'])) {
			if ( !preg_match('@<object@i', $sections['video']['html']) ) {
				unset( $sections['video'] );
			}
		}
		// Remove </body></html> from html
		if (count($sections) > 0) {
			$keys = array_keys($sections);
			$last =& $sections[ $keys[count($sections) - 1] ]['html'];
			$last = preg_replace('@</body>(\s|\n)*</html>(\s|\n)*$@', '', $last);
		}

		return array($sections, $intro, $firstImage);
	}
}

/*
* Builds the body of the article with appropriate javascript and google analytics tracking.  
* This is used primarily for the Mobile QG (MQG) tool.
*/
class MobileQGArticleBuilder extends MobileBasicArticleBuilder {

	protected function generateHeader() {
		return "";
	}

	protected function generateFooter() {
		return "";
	}


	// never run test for mobileqg articles
	protected function isStaticTestArticle() {
		return false;
	}

	// Override device options so we can turn off ads
	protected function getDevice() {
		$device = $this->deviceOpts;
		$device['show-ads'] = false;
		return $device;
	}

	protected function addJSLibs() {
		// Don't include the jquery JS here.  This will be added in the MQG special page
	}
}

class MobileMainPageBuilder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		$headerVars['showTagline'] = true;
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		global $wgLanguageCode;

		$featured = $this->getFeaturedArticles(7);
		$randomUrl = '/' . wfMsg('special-randomizer');
		$spotlight = $this->selectSpotlightFeatured($featured);
		$langUrl = '/' . wfMsg('mobile-languages-url');
		$vars = array(
			'randomUrl' => $randomUrl,
			'spotlight' => $spotlight,
			'featured' => $featured,
			'languagesUrl' => $langUrl,
			'imageOverlay' => $wgLanguageCode == 'en',
		);
		return EasyTemplate::html('main-page.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	private function selectSpotlightFeatured(&$featured) {
		$spotlight = array();
		if ($featured) {
			// grab a random article from the list without replacement
			$r = mt_rand(0, count($featured) - 1);
			$spotlight = $featured[$r];
			unset($featured[$r]);
			$featured = array_values($featured); // re-key array

			$title = Title::newFromURL(urldecode($spotlight['url']));
			if ($title && $title->getArticleID() > 0) {
				$spotlight['img'] = $this->getFeatureArticleImage($title, 290, 194);
				$spotlight['intro'] = $this->getFeaturedArticleIntro($title);
			}
		}
		return $spotlight;
	}

	private function getFeatureArticleImage(&$title, $width, $height) {
		global $wgUser;
		$skin = $wgUser->getSkin();

		// The next line was taken from:
		//   SkinWikihowskin::featuredArticlesLineWide()
		$img = $skin->getGalleryImage($title, $width, $height);
		return wfGetPad($img);
	}

	private function getFeaturedArticles($num) {
		global $IP;
		$NUM_DAYS = 15; // enough days to make sure we get $num articles

		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$featured = FeaturedArticles::getFeaturedArticles($NUM_DAYS);

		$fas = array();
		$n = 1;
		foreach($featured as $f) {
			$partUrl = preg_replace('@^http://(\w|\.)+\.wikihow\.com/@', '', $f[0]);
			$title = Title::newFromURL(urldecode($partUrl));
			if ($title) {
				$name = $title->getText();

				$fa = array(
					'name' => $name,
					'url' => $partUrl,
					'img' => $this->getFeatureArticleImage($title, 90, 54),
				);
				$fas[] = $fa;

				if (++$n > $num) break;
			}
		}

		return $fas;
	}

	private function getFeaturedArticleIntro(&$title) {
		// use public methods from the RSS feed that do the same thing
		$article = Generatefeed::getLastPatrolledRevision($title);
		$summary = Generatefeed::getArticleSummary($article, $title);
		return $summary;
	}

	protected function addCSSLibs() {
		parent::addCSSLibs();
		$this->addCSS('mwhf');
		$this->addCSS('mwhh');
	}


}

class MobileViewLanguagesBuilder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		$headerVars['css'][] = 'mwhr';
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		$vars = array('languages' => self::getLanguages());
		return EasyTemplate::html('language-select.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}

	private static function getLanguages() {
		$ccedil = htmlspecialchars_decode('&ccedil;');
		$ntilde = htmlspecialchars_decode('&ntilde;');
		$ecirc = htmlspecialchars_decode('&ecirc;');
		$langs = array(
			array(
				'code' => 'en', 
				'name' => 'English',
				'url'  => 'http://m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_england.gif',
			),
			array(
				'code' => 'es', 
				'name' => "Espa{$ntilde}ol",
				'url'  => 'http://es.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_spain.gif',
			),
			array(
				'code' => 'de', 
				'name' => 'Deutsch',
				'url'  => 'http://de.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_germany.gif',
			),
			array(
				'code' => 'pt', 
				'name' => "Portugu{$ecirc}s",
				'url'  => 'http://pt.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_portugal.gif',
			),
			array(
				'code' => 'fr', 
				'name' => "Fran${ccedil}ais",
				'url'  => 'http://fr.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_france.gif',
			),
			array(
				'code' => 'it', 
				'name' => 'Italiano',
				'url'  => 'http://it.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_italy.gif',
			),
			array(
				'code' => 'nl', 
				'name' => 'Nederlands',
				'url'  => 'http://nl.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_netherlands.gif',
			),
		);
		return $langs;
	}
}

class Mobile404Builder extends MobileHtmlBuilder {
	
	protected function generateHeader() {
		$headerVars = $this->getDefaultHeaderVars();
		return EasyTemplate::html('header.tmpl.php', $headerVars);
	}

	protected function generateBody() {
		$vars = array('mainPage' => wfMsg('mainpage'));
		return  EasyTemplate::html('not-found.tmpl.php', $vars);
	}

	protected function generateFooter() {
		$footerVars = $this->getDefaultFooterVars();	
		return EasyTemplate::html('footer.tmpl.php', $footerVars);
	}
}
