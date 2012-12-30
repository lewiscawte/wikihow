<?php

	
class Awards extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Awards' );
	}

	function execute ($par ) {
		global $wgOut;
		
		$wgOut->setSquidMaxage( 3600 );

/*

		$wgOut->addHTML("

<style>
.floatright {
        float: right;
        margin-bottom: 0px;
        margin-top: 0px;
        padding: 5px 5px;
}
div.award
{
width:600px;
height:400px;
margin:0px;
line-height:150%;
}
div.award2
{
position:relative;
width:600px;
height:200px;
top:325px;
margin:0px;
line-height:150%;
}
div.header,div.footer
{
padding:0.5em;
color:white;
background-color:gray;
clear:left;
}
h1.header
{
padding:0;
margin:0;
text-align:center;
text-size:13px;
}
div.right
{
float:right;
width:220;
margin:0;
padding:1em;
}
div.left
{
position:absolute;
float:left;
width:220;
margin:0;
padding:1em;
}
div.content
{
margin-left:235px;
border-left:1px solid gray;
padding:1em;
}

</style>

*/
		$wgOut->addHTML("

<style>
div.content
{
border-left:1px solid gray;
padding:1em;
}
</style>
<h1 class=\"firstHeading\">Vote here to help wikiHow win this award </a></h1>

<table valign=\"top\">
<tr><td width=\"220\">
<iframe width=\"210\" marginheight=\"0\" marginwidth=\"0\" frameborder=\"0\" height=\"390\" src=\"http://mashable.polldaddy.com/widget/x2.aspx?f=f&c=20&cn=230\"></iframe> <noscript><a href=\"http://mashable.com/2008/11/19/openwebawards-voting-1/\">Mashable Open Web Awards</a></noscript>

</td>
<td valign=\"top\">
<div class=\"content\">
<strong>Open web award for best wiki</strong>
<ul>
<li>Enter email address</li>
<li>Vote once per day until Dec 15th</li>
</ul>
</div>
</td>
</tr>

</table>

		");


	}
}

				
