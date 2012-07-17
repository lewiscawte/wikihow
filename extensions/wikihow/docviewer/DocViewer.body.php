<?

class DocViewer extends UnlistedSpecialPage {
	
	private static $pdf_array = array(
		'AssignmentofCopyright' => 	array(
										'Assignment of Copyright',
										'https://www.docracy.com/sign/usedoc?docId=3835&versionNum=4',
										'Copyright-Photographs'),
		'BillofSale' 			=>	array(
										'Bill of Sale',
										'https://www.docracy.com/sign/usedoc?docId=82&versionNum=2',
										'Sell-a-Used-Car'),
		'LivingWill'			=>	array(
										'Living Will',
										'https://www.docracy.com/sign/usedoc?docId=80&versionNum=2',
										'Write-a-Living-Will'),
		'NonDisclosureAgreement'	=>	array(
										'NDA',
										'https://www.docracy.com/sign/usedoc?docId=27&versionNum=5',
										'Understand-the-Structure-of-a-Simple-Non-Disclosure-Agreement'),
		'PowerofAttorney'		=>	array(
										'Power of Attorney',
										'https://www.docracy.com/sign/usedoc?docId=78&versionNum=5',
										'Notarize-a-Power-of-Attorney'),
		'ResidentialSublease'	=>	array(
										'Residential Sublease Agreement',
										'https://www.docracy.com/sign/usedoc?docId=4743&versionNum=4',
										'Write-a-Sublease-Contract'),
		'StatutoryWill'			=>	array(
										'Last Will',
										'https://www.docracy.com/sign/usedoc?docId=79&versionNum=3',
										'Write-Your-Own-Last-Will-and-Testament'),
		'SubleaseAgreement'		=>	array(
										'Commercial Sublease Agreement',
										'https://www.docracy.com/sign/usedoc?docId=83&versionNum=4',
										'Write-a-Sublease-Contract'),
		'TermsofService'		=>	array(
										'Terms of Service',
										'https://www.docracy.com/sign/usedoc?docId=14&versionNum=10',
										'Make-Terms-and-Conditions-and-Privacy-Policies-for-a-Business')
	);

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'DocViewer' );
	}
	
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}
	
	/**
	 * Display the HTML for this special page
	 */
	private function displayContainer($pdf) {
		
		if ($pdf) {
			$pdf_name = '';
			$docracy_link = '';
			$article_title = '';
		
			//grab the details from that hacky array
			foreach (self::$pdf_array as $pdffile => $details) {
				if ($pdffile == $pdf) {
					$pdf_name = $details[0];
					$docracy_link = $details[1];
					$article_title = $details[2];
					break;
				}
			}
		
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array(
				'pdf_name' => $pdf,
				'download_text' => wfMsg('dv-download-doc'),
				'click_back' => wfMsg('dv-click-back') . ' <a href="/'.$article_title.'">'.str_replace('-',' ',$article_title).'</a>',
				'docracy_link' => $docracy_link
			));
			$html = $tmpl->execute('docviewer.tmpl.php');
		}
		else {
			//no PDF name?  Boo...
			$html = '<p>'.wfMsg('dv-no-pdf-err').'</p>';
		}
		
		return $html;
	}	
	
	/**
	 * EXECUTE
	 **/
	function execute ($par = '') {
		global $wgOut, $wgRequest, $wgHooks;
		
		wfLoadExtensionMessages('DocViewer');
		
		//no side bar
		$wgHooks['ShowSideBar'][] = array('DocViewer::removeSideBarCallback');
		
		//page title
		$wgOut->setHTMLTitle( wfMsg('pagetitle', wfMsg('dv-html-title').': '.$par) );

		$wgOut->addScript(HtmlSnips::makeUrlTags('css', array('docviewer.css'), 'extensions/wikihow/docviewer', false));
		$html = $this->displayContainer($par);
		$wgOut->addHTML($html);
	}
	
}