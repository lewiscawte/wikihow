<?php
/*
 * Change all meta descriptions
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");

ArticleMetaInfo::reprocessAllArticles(ArticleMetaInfo::DESC_STYLE_INTRO);

