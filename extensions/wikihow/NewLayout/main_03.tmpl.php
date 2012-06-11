<div id="main">
	<?php
		
			$sidebar = '';

		// INTL: load mediawiki messages for sidebar expand and collapse for later use in sidebar boxes
		$langKeys = array('navlist_collapse', 'navlist_expand');
		echo WikiHow_i18n::genJSMsgs($langKeys);
	?>
    <div id="article_shell" class="<?= $sidebar ?>">
		<? 
			echo wikihowAds::getSetup();
			
			
			$recipe_hdr = '';
		?>
		
        <div id="article" <?=$recipe_hdr?>>

		
        	<div id="article_header">
				
				<div id="a_tabs">
					<a href="<? if ($wgTitle->isTalkPage()) echo $wgTitle->getSubjectPage()->getFullURL(); else echo $wgTitle->getFullURL(); ?>"
						id="tab_article" title="Article" <?php if (!MWNamespace::isTalk($wgTitle->getNamespace()) && $action != "edit" && $action != "history") echo 'class="on"'; ?> onmousedown="button_click(this);">read</a>
					
					
					<? 
							$talklink = $wgTitle->getTalkPage()->getLocalURL();
					?>
					<span id="gatDiscussionTab"><a href="<? echo $talklink; ?>"  <?php if ($wgTitle->isTalkPage() && $action != "edit" && $action != "history") echo 'class="on"'; ?> id="tab_discuss" title="<?= $msg ?>" onmousedown="button_click(this);">talk</a></span>
				
				<a href="<?=$wgTitle->escapeLocalURL($sk->editUrlOptions())?>" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">edit</a>

			   </div><!--end article_tabs-->
				
				
			<?=$introImage?>
			<h1 class='firstHeading'><a href="<?=$wgTitle->getFullURL()?>"><?= wfMsg('howto', $wgTitle->getText())?></a></h1>
    		<?=$sk->getAuthorHeader();?>
			
			<?=$share?>
			
			</div><!--end article_inner-->

			
	<?=$fbLinked?>
			<div id='bodycontents'>

			<? if ($showFBBar) { 
				echo "<div class='fb_bar_outer'>I want to do this: <div id='fb_action_wants_to' class='fb_bar_img'></div></div>";
			} ?>
			

		    <?= $article ?>
			</div>
			<?=$suggested_titles?>
			<? if (!$show_ad_section) {
    			echo "<div id='lower_ads'>{$bottom_ads}</div>";
			 }
				if ($show_ad_section) {
					echo $ad_section;
				}
			?>
			<?= $bottom_site_notice ?>
	 		<? if ($wgTitle->isTalkPage()) {
				if ($wgTitle->getFullURL() != $wgUser->getUserPage()->getTalkPage()->getFullURL()) { ?>
 				<div class="article_inner">
 				<?Postcomment::getForm(); ?>
 				</div>
				<? } else { ?>
						<a name='postcomment'></a>
						<a name='post'></a>
			<? 		}
				} ?>


	<?
		
			$catlinks = $sk->getCategoryLinks(false);
			$authors = $sk->getAuthorFooter();
			if ($authors != "" || is_array($this->data['language_urls']) || $catlinks != "") {
	?>
<h2 class="section_head non_edit" id="article_info_header"><?= wfMsg('article_info') ?><div class="kiwi_tab"></div></h2>
    <div id="article_info" class="article_inner">
		<?=$fa?>
        <p><?=$sk->getLastEdited();?></p>
		<p>
			<?echo wfMsg('categories') . ":<br/>{$catlinks}"; ?>

        </p>
			<p><?=$authors?></p>

        <?php if (is_array($this->data['language_urls'])) { ?>
        <p>
            <?php $this->msg('otherlanguages') ?><br /><?php
                $links = array();
                foreach($this->data['language_urls'] as $langlink) {
					$linkText = $langlink['text'];
					preg_match("@interwiki-(..)@", $langlink['class'], $langCode);
					if (!empty($langCode[1])) {
						$sk = $wgUser->getSkin();
						$linkText = $sk->getInterWikiLinkText($linkText, $langCode[1]);
					}
                    $links[] = htmlspecialchars(trim($langlink['language'])) . '&nbsp;<span><a href="' .  htmlspecialchars($langlink['href']) . '">' .  $linkText . "</a><span>";
                }
                echo implode("&#44;&nbsp;", $links);
            ?>
        </p>
        <? } ?>
    </div><!--end article_info-->
	<? 		}
		
		
	?>
<div id='article_tools_header'>
<h2 class="section_head"><?= wfMsg('article_tools') ?></h2>
</div> <!-- article_tools_header -->
	<div class="article_inner">
	    <ul id="end_options">
	        <li id="endop_discuss"><a href="<?echo $talklink;?>" id="gatDiscussionFooter">Talk</a></li>
	        <li id="endop_edit"><a href="<?echo $wgTitle->getEditUrl();?>" id="gatEditFooter"><?echo wfMsg('edit');?></a></li>
			<li id="endop_email"><a href="#" onclick="return emailLink();" id="gatSharingEmail"><?=wfMsg('at_email')?></a></li>
			<li id="endop_print"><a href="<?echo $wgTitle->getLocalUrl('printable=yes');?>" id="gatPrintView">Print article</a></li>
			<? if($wgUser->getID() > 0): ?>
				<? if ($wgTitle->userIsWatching()) { ?>
					<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=unwatch');?>"><?=wfMsg('at_remove_watch')?></a></li>
				<? } else { ?>
					<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=watch');?>"><?=wfMsg('at_watch')?></a></li>
				<? } ?>
			<? endif; ?>
	        
			<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
	        	<li id="endop_fanmail"><a href="/Special:ThankAuthors?target=<?echo $wgTitle->getPrefixedURL();?>" id="gatThankAuthors">Send fanmail to author</a></li>
			<? } ?>
	    </ul>


		<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
			<div id="embed_this" class="clearall"><span>+</span> <a href="/Special:Republish/<?= $wgTitle->getDBKey() ?>" id="gatSharingEmbedding" rel="nofollow"><?=wfMsg('at_embed')?></a></div>
		<? } ?>
			
		<? if (!$wgIsDomainTest && ($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_CATEGORY)) { ?>
		<div id="share_icons">
		    <div><?=wfMsg('at_share')?></div>
		    <span id="gatSharingTwitter" ><a onclick="javascript:share_article('twitter');" id="share_twitter"></a></span>
		    <span id="gatSharingStumbleupon"> <a onclick="javascript:share_article('stumbleupon');" id="share_stumbleupon"></a></span>
		    <span id="gatSharingFacebook"> <a onclick="javascript:share_article('facebook');" id="share_facebook"></a></span>
		    <span id="gatSharingBlogger"> <a onclick="javascript:share_article('blogger');" id="share_blogger"></a></span>
		    <span id="gatSharingGoogleBookmarks"> <a onclick="javascript:share_article('google');" id="share_google"></a></span>
		    <? 
				if(class_exists('WikihowShare'))
					echo WikihowShare::getBottomShareButtons();
			?>
		    <br class="clearall" />
		</div><!--end share_icons-->
		<? } ?>
		
		<?php if( $wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getNamespace() == NS_MAIN) {
			echo wikihowAds::getAdUnitPlaceholder(7);
		} ?>
	</div><!--end article_inner-->
	<div id="last_question">
			<?=$userstats;?>
            <p>Thanks to all authors for creating a page that has been read<br />
				
			<span><?=$wgLang->formatNum( $wgArticle->getCount() )?> times.</span> </p>
				


			<div id='page_rating'>
			<?echo RateArticle::showForm();?>
           	</div>
            <p></p>
   </div>  <!--end last_question-->
</div> <!-- article -->
    
	


</div>  <!--end article_shell-->

    <div id="sidebar">
        <div id="randomizer" class="sidebox_shell">
            <!--<a href="/Special:Createpage" class="button button136" style="float: left;" id="gatWriteAnArticle" onmouseover="button_swap(this);" onmouseout="button_unswap(this);"><?=wfMsg('writearticle');?></a>-->
            <a href="/Special:Randomizer" id="gatRandom" accesskey='x'>View a Random Article</a>
			<? if (class_exists('Randomizer') && Randomizer::DEBUG && $wgTitle && $wgTitle->getNamespace() == NS_MAIN && $wgTitle->getArticleId()): ?>
				<?= Randomizer::getReason($wgTitle) ?>
			<? endif; ?>
		</div><!--end top_links-->
		
		<?php
			if($wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getText() != 'Userlogin' && $wgTitle->getNamespace() == NS_MAIN){
				//comment out next line to turn off HHM ad
				if (wikihowAds::isMtv() && ($wgLanguageCode =='en'))
					echo wikihowAds::getMtv();
				elseif ( wikihowAds::isHHM() && ($wgLanguageCode =='en'))
					echo wikihowAds::getHhmAd();
				else
					echo wikihowAds::getAdUnitPlaceholder(4);
			}
			//<!-- <a href="#"><img src="/skins/WikiHow/images/imgad.jpg" /></a> -->
		?>
	<?
		$likeDiv = "";

		if (class_exists('CTALinks') && CTALinks::isArticlePageTarget()) {
			$fb_wikiHow_iframe = <<<EOHTML
<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2Fwikihow&amp;layout=standard&amp;show_faces=false&amp;width=215&amp;action=like&amp;colorscheme=light&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:215px; height:40px;" allowTransparency="true"></iframe>
EOHTML;
			$likeDivBottom = $wgUser->getID() > 0 ? "_bottom" : "";
			$cdnBase = wfGetPad('');
			$likeDiv = <<<EOHTML
				<div id="fb_sidebar_shell$likeDivBottom">
					<div><img class="module_cap" alt="" src="{$cdnBase}/skins/WikiHow/images/fblike/LikeOffWhite_Top.png"></div>
					<div id="fb_sidebar">
						<span id ="fb_icon"><img src="{$cdnBase}/skins/WikiHow/images/fblike/facebook_icon.png"></span>
						<div id="follow_facebook"><span><a href="http://www.facebook.com/wikiHow">Follow wikiHow</a></span> on facebook</div>
						<div id="fb_sidebar_content"></div>
					</div>
					<div><img class="module_cap" alt="" src="$cdnBase/skins/WikiHow/images/fblike/LikeOffWhite_Bottom.png"></div>
				</div>
EOHTML;

			//$likeDiv = "";
			if ($wgUser->getId() == 0 || $wgRequest->getVal('likeDiv')) {
				echo $likeDiv;
				echo wfMsg('like_test', $likeDivBottom);
			}
		}
	?>
		<?if ($mpWorldwide !== "") { ?>
			<?=$mpWorldwide;?>
		<? }  ?>

				<!--
				<div class="sidebox_shell">
					<div class='sidebar_top'></div>
					<div id="side_fb_timeline" class="sidebox">
					</div>
					<div class='sidebar_bottom_fold'></div>
				</div>
				-->
				<!--end sidebox_shell-->
	<?
			$related_articles = $sk->getRelatedArticlesBox($this);
            //disable custom link units
			//  if ($wgUser->getID() == 0 && $wgTitle->getNamespace() == NS_MAIN && !$isMainPage)
            //if ($related_articles != "")
				//$related_articles .= WikiHowTemplate::getAdUnitPlaceholder(2, true);
			if ($related_articles != "") {
	?>
				<div class="sidebox_shell">
					<div id="side_related_articles" class="sidebox">
						<?=$related_articles?>
					</div><!--end side_related_articles-->
				</div><!--end sidebox_shell-->
				<?
			}

			echo wikihowAds::getAdUnitPlaceholder(2, true);

	?>

         <!-- Sidebar Widgets -->
		<? foreach ($sk->mSidebarWidgets as $sbWidget) { ?>
  	      <?= $sbWidget ?>
		<? } ?>
         <!-- END Sidebar Widgets -->

		<? if ($wgUser->getID() > 0) echo $navigation; ?>


	<div class="sidebox_shell">
        <div id="side_featured_articles" class="sidebox">
				<?= $sk->getFeaturedArticlesBox(4, 4) ?>
        </div>
	</div>

    <?php if ($showRCWidget) { ?>
	<div class="sidebox_shell" id="side_rc_widget">
        <div id="side_recent_changes" class="sidebox">
            <? RCWidget::showWidget(); ?>
			<p class="bottom_link">
			<? if ($wgUser->getID() > 0) { ?>
            	<?= wfMsg('welcome', $wgUser->getName(), $wgUser->getUserPage()->getLocalURL()); ?>
			<? } else { ?>
            	<a href="/Special:Userlogin" id="gatWidgetBottom"><?=wfMsg('rcwidget_join_in')?></a>
			<? } ?>
			<a href="" id="play_pause_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" onclick="rcTransport(this); return false;" ></a>
            </p>
        </div><!--end side_recent_changes-->
	</div><!--end sidebox_shell-->
	<?php } ?>

	<?php if (class_exists('FeaturedContributor') && $wgTitle->getNamespace() == NS_MAIN && !$isMainPage) { ?>
	<div class="sidebox_shell">
        <div id="side_featured_contributor" class="sidebox">
        <?  FeaturedContributor::showWidget();  ?>
			<? if ($wgUser->getID() == 0) { ?>
        <p class="bottom_link">
           <a href="/Special:Userlogin" id="gatFCWidgetBottom" onclick='gatTrack("Browsing","Feat_contrib_cta","Feat_contrib_wgt");'><? echo wfMsg('fc_action') ?></a>
        </p>
			<? } ?>
        </div><!--end side_featured_contributor-->
	</div><!--end sidebox_shell-->
	<?php } ?>

	<?
	if (class_exists('CTALinks') && CTALinks::isArticlePageTarget() && CTALinks::isLoggedIn()) {
		echo $likeDiv;
		echo wfMsg('like_test', $likeDivBottom);
	}
	?>
	<div class="sidebox_shell">
        <div class="sidebox">
			<? FollowWidget::showWidget(); ?>
        </div>
	</div>
	</div>
	</div>
</div><!--end main-->