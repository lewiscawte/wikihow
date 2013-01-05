<?php
/*
 * Import meta description titles from Chris
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");

//ArticleMetaInfo::processArticleDescriptionList('../x/meta-intro.csv', ArticleMetaInfo::DESC_STYLE_INTRO);
ArticleMetaInfo::processArticleDescriptionList('../x/meta-step1.csv', ArticleMetaInfo::DESC_STYLE_STEP1);
//ArticleMetaInfo::processArticleDescriptionList('../x/meta-control.csv', ArticleMetaInfo::DESC_STYLE_ORIGINAL);
