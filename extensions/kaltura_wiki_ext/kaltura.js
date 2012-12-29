/*
This file is part of the Kaltura Collaborative Media Suite which allows users
to do with audio, video, and animation what Wiki platfroms allow them to do with
text.

Copyright (C) 2006-2008  Kaltura Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
// version = 02
//alert ("kaltura.js loaded from " + document.location );

var kaltura_edit_url ="";
var kaltura_alt_for_btn = "";
// kalturaRevert - called from article page when using 'revert'
function kalturaRevert ( url , rev , txt )
{
//	alert ( "kalturaRevert ( " + url + "," +  rev + ")" );
    res = confirm (txt);//'Do you want to revert to this version?');
    if ( res ) {
        url_str = url + '&undo=' + rev;
        document.location=url_str;
        return true;
    }
    else {
        return false;
    }
}

// initModalBox called from gotoCW - to open the contribution wizard as an iFrame in the
// widget page
function kalturaInitModalBox ( url ) //, params , kshow_id , kwid )
{
    var objBody = document.getElementsByTagName("body").item(0);

    // create overlay div and hardcode some functional styles (aesthetic styles are in CSS file)
    var objOverlay = document.createElement("div");
    objOverlay.setAttribute('id','overlay');
    objBody.appendChild(objOverlay, objBody.firstChild);

    // create modalbox div, same note about styles as above
    var objModalbox = document.createElement("div");

    if (arguments.length >= 1)
    {
        className = arguments[1];
        objModalbox.setAttribute('id','modalbox');
        objModalbox.setAttribute('class',className);
        objModalbox.className = className;
    }
    else
    {
        objModalbox.setAttribute('id','modalbox');
    }


    // create content div inside objModalbox
    var objModalboxContent = document.createElement("div");
    objModalboxContent.setAttribute('id','mbContent');
    if ( url != null )
    {
        // all params are concatenated on the server's side
        objModalboxContent.innerHTML = '<iframe scrolling="no" width="680" height="360" frameborder="0" src="' + url + '&inframe=true" />';
            //+ params + '&kshow_id=' + kshow_id + '&kwid=' + kwid + '" />';
    }
    else
    {
        //objModalboxContent.innerHTML = '<iframe scrolling="no" width="680" height="360" frameborder="0" src="about:blank" />';
    }
    objModalbox.appendChild(objModalboxContent, objModalbox.firstChild);

    objBody.appendChild(objModalbox, objOverlay.nextSibling);

    return objModalboxContent;
}


function kalturaCloseModalBox ()
{
    if ( this != window.top )
    {
        window.top.kalturaCloseModalBox();
        return false;
    }

    kalturaGiveTextAreaFocus();

    // TODO - have some JS to close the modalBox without refreshing the page if there is no need
    overlay_obj = document.getElementById("overlay");
    modalbox_obj = document.getElementById("modalbox");
    modalbox2_obj = document.getElementById("modalboxEditor");
    if ( overlay_obj != null )	overlay_obj.parentNode.removeChild( overlay_obj );
    if ( modalbox_obj != null )		modalbox_obj.parentNode.removeChild( modalbox_obj );
    if ( modalbox2_obj != null )		modalbox2_obj.parentNode.removeChild( modalbox2_obj );

    return false;
}

function $kaltura_id(x){ return document.getElementById(x); }

function toggleHelp(){
    if( $kaltura_id('content_help').style.display == 'none' ){
        $kaltura_id('content_help').style.display = 'block';
        $kaltura_id('content_main').style.display = 'none';
    }
    else{
        $kaltura_id('content_help').style.display = 'none';
        $kaltura_id('content_main').style.display = 'block';
    }
}


// called to refresh tha min page from within
function kalturaRefreshTop ()
{
    if ( this != window.top )
    {
        window.top.kalturaRefreshTop();
        return false;
    }
    window.location.reload(true);
}

function kalturaTest( t )
{
    alert ( "Kaltura test ok [" + t + "]" );
}

function kalturaAddButtonsToEdit ( edit_url , path , alt_for_btn )
{
    // set the global variable to be used later in the script
    kaltura_edit_url = edit_url;
    kaltura_alt_for_btn = alt_for_btn;
//	alert ( "kalturaAddButtonsToEdit [" + path + "]" );
    addButton( path + 'btn_wiki_edit.gif', alt_for_btn , '2','3','<4>','kaltura_new_widget');
    // the button is not yet in place - hook onto the load event until the whole page is ready
    hookEvent("load", kalturaHookButton);

}

function kalturaHideModalBox ()
{
//	return kalturaCloseModalBox();

    if ( this != window.top )
    {
        window.top.kalturaHideModalBox();
        return false;
    }

    overlay_obj = document.getElementById("overlay");
    modalbox_obj = document.getElementById("modalbox");
    if ( overlay_obj ) overlay_obj.style.display = "none";
    if ( modalbox_obj ) modalbox_obj.style.display = "none";

    // must be after hiding the divs - else doesn't work for the second time onwards
    var flashMovie = $kaltura_id("kplayer");
    if ( flashMovie && flashMovie.stopMedia != undefined ) flashMovie.stopMedia();
}

function kalturaOpenPlayer ( aKshowId, aEntryId , startTime , lenTime ) //url , params , kshow_id , kwid )
{
    var objBody = document.getElementsByTagName("body").item(0);

    overlay_obj = document.getElementById("overlay");
    if ( overlay_obj == null )
    {
        // create overlay div and hardcode some functional styles (aesthetic styles are in CSS file)
        var objOverlay = document.createElement("div");
        objOverlay.setAttribute('id','overlay');
        objOverlay.setAttribute('onclick','kalturaHideModalBox();');

        objBody.appendChild(objOverlay, objBody.firstChild);
    }
    else
    {
        overlay_obj.style.display = "block";
    }

    modalbox_obj = document.getElementById("modalbox");
    if ( modalbox_obj == null )
    {
        // create modalbox div, same note about styles as above
        var objModalbox = document.createElement("div");
        objModalbox.setAttribute('id','modalbox');
        objModalbox.setAttribute('class','player');
        objModalbox.className = 'player';



        // create content div inside objModalbox
        var objModalboxContent = document.createElement("div");
        objModalboxContent.setAttribute('id','mbContent');
        html = '<a id="mbCloseBtn" class="type1" href="#" onclick="kalturaHideModalBox();  return false;"></a><div id="kplayer_container"></div>'; // the name of the div will become the name of the object
        objModalboxContent.innerHTML = html;

        objModalbox.appendChild(objModalboxContent, objModalbox.firstChild);

        objBody.appendChild(objModalbox, objOverlay.nextSibling);

        kalturaCreateDynamicSWFObject ('kplayer_container' , 'kplayer' );
//		swfobject.embedSWF( hidden_player_swf_url , 'kplayer' , hidden_player_width ,hidden_player_height , '9.0.0', false, hidden_player_flashVars , hidden_player_params );
    }
    else
    {
        modalbox_obj.style.display = "block";
    }


    insertEntry ( "-1" , aEntryId , true, startTime , lenTime);

    return false;
}

/*
function playEntry  ( aKshowId, aEntryId , startTime , lenTime )
{

    var player = $kaltura_id("kplayer_div");
    player.style.display = "block";
//	var flashMovie = $kaltura_id("kplayer_dummy");

    // give focus to the hidden-player
    insertEntry ( aKshowId, aEntryId , true, startTime , lenTime);
    return false;
}
*/

insertEntryArgs = null;
flashMovieTimeout = null;

function insertEntry( aKshowId, aEntryId , aAutoStart, startTime , lenTime ){
    if (arguments.length > 0)
    {
        insertEntryArgs = arguments;
        insertEntryArgs[0]=arguments[0];
        insertEntryArgs[1]=arguments[1];
        insertEntryArgs[2]=arguments[2];
        insertEntryArgs[3]=arguments[3];
        insertEntryArgs[4]=arguments[4];
    }

    var flashMovie = $kaltura_id("kplayer");

//alert ( "flashMovie:" + flashMovie + " flashMovie.insertMedia: " +  flashMovie.insertMedia );

    if( flashMovie && flashMovie.insertMedia != undefined ) //&& flashMovie.PauseMedia != undefined)
    {
        if (flashMovieTimeout)
        {
            clearInterval(flashMovieTimeout);
            flashMovieTimeout = null;
        }

//		flashMovie.InsertEntry(inserEnryArgs[0], insertEntryArgs[1], insertEntryArgs[2]);//, insertEntryArgs[3], insertEntryArgs[4]);
        flashMovie.insertMedia( "" + insertEntryArgs[0], "" +  insertEntryArgs[1], "" + insertEntryArgs[2] ); //insertEntryArgs[2]);//, insertEntryArgs[3], insertEntryArgs[4]);
//		flashMovie.insertMedia( "-1" , "13436" , "true" );
        return;
    }
    else if (!flashMovieTimeout)
    {
        flashMovie = null;
        flashMovieTimeout = setInterval( function(){insertEntry()}, 200 );
        return;
    }
}

var modalbox;
var hook_count = 10;
var last_caret_pos ;

function kalturaHookButton ( )
{
    var btn = document.getElementById("kaltura_new_widget");

    // find using the alt
    if ( btn == null )
    {
        var imgs = document.getElementsByTagName("img");
        for ( i=0 ; i< imgs.length ; ++i)
        {
            img =imgs[i];
            if ( img.alt == kaltura_alt_for_btn )
            {
                var btn = img;
                break;
            }
        }
    }

    if ( btn == null )
    {
        hook_count--;
        if ( hook_count <= 0 ) return;
        setTimeout( 'kalturaHookButton()' , 200 );
        return;
    }

    btn.onclick = function() {

        if (document.editform)
        {
            textarea = document.editform.wpTextbox1;
            last_caret_pos = kaltura_js_getCursorPosition ( textarea );
        }

        return kalturaOpenModalBoxBeginKaltura ( kaltura_edit_url );
/*
        modalbox = kalturaInitModalBox ( null );//, null, null);
        // have the same size of the iframe of the CW
        current_location = document.location;
        current_location = current_location.toString().split("&")[0]; // have only the first part of the location - include the first element of the query string - it's the title
        current_location = escape ( current_location );
        modalbox.innerHTML = '<iframe scrolling="no" width="680" height="360" frameborder="0" src="' + kaltura_edit_url + '&url=' + current_location + '/>';
        return false;
*/
    };

}

function kalturaOpenModalBoxBeginKaltura ( url )
{
    modalbox = kalturaInitModalBox ( null );//, null, null);
    // have the same size of the iframe of the CW
    current_location = document.location;
    current_location = current_location.toString().split("&")[0]; // have only the first part of the location - include the first element of the query string - it's the title
    current_location = escape ( current_location );
    iframe_url =  url + '&inframe=true&url=' + current_location;

    modalbox.innerHTML = '<iframe scrolling="no" width="680" height="360" frameborder="0" src="' + iframe_url + '"/>';
    return false;
}

function kalturaOpenEditor ( url , type , partner_editor_type)
{
	// if the runtime param indicate that the advanced editor should popen AND the partner allows it...
	if ( type == 2 && (partner_editor_type & 2) )
	{
		window.location = url + '&kaltura_editor=2';
		return;
	}
    modalbox = kalturaInitModalBox ( null , "editor" );//, null, null);
    // have the same size of the iframe of the CW
    iframe_url =  url + '&kaltura_editor=1';

    modalbox.innerHTML = '<iframe scrolling="no" width="890" height="546" frameborder="0" src="' + iframe_url + '"/>';
    return false;
}


// this code is run in the child window -
// be sure to user the parent's variable
function kalturaInsertWidget ( tag , pos)
{
//	alert ( "kalturaInsertWidget: " + tag );

    textarea = parent.top.document.editform.wpTextbox1;
    kaltura_js_setCursorPosition ( textarea , parent.top.last_caret_pos);

    // should be set in the parent document
    parent.top.insertTags( "" , "" , tag );
}


function kalturaGiveTextAreaFocus()
{
    if ( ! parent.top.document.editform ) return;
    textarea = parent.top.document.editform.wpTextbox1;
    if ( textarea == null ) return;
    if (  parent.top.last_caret_pos != null )
        kaltura_js_setCursorPosition ( textarea , parent.top.last_caret_pos);
    textarea.focus();
}

var kaltura_refresh_url;
function deleteEntryImpl ( entry_id, kshow_id , hash , url )
{
    kaltura_refresh_url = url;
//	alert ( "deleteEntryImpl (" + entry_id + "," + kshow_id + "," + hash + ")" );
    sajax_do_call('ajaxKalturaDeleteEntry' , [ entry_id , kshow_id , hash ] , deleteEntryRefresh );
}

function deleteEntryRefresh ( response )
{
//	alert ( "deleteEntryRefresh [" + response + "]" );
    window.location = kaltura_refresh_url;
//	kalturaRefreshTop() ;
}


/*
 * Code for getting and setting the caret location in a textarea element.
 * We're using the kaltura_ prefix only as a namespace.
 */
function kaltura_js_countTextAreaChars(text) {
    var n = 0;
    for (var i = 0; i < text.length; i++) {
        if (text.charAt(i) != '\r') {
            n++;
        }
    }
    return n;
}

function kaltura_js_CursorPos(start, end) {
    this.start = start;
    this.end = end;
}

function kaltura_js_getCursorPosition(textArea) {
    var start = 0;
    var end = 0;

    if (document.selection) { // IE?
        textArea.focus();
        var sel1 = document.selection.createRange();
        var sel2 = sel1.duplicate();
        sel2.moveToElementText(textArea);
        var selText = sel1.text;
        sel1.text = "_01!_"; // this text should be unique
        var index = sel2.text.indexOf("_01!_"); // this text should be unique and the same as the above
        start = kaltura_js_countTextAreaChars((index == -1) ? sel2.text : sel2.text.substring(0, index));
        end = kaltura_js_countTextAreaChars(selText) + start;
        sel1.moveStart("character", -5); // this is the length of the unique text
        sel1.text = selText;
    } else if (textArea.selectionStart || (textArea.selectionStart == "0")) { // Mozilla/Netscape?
        start = textArea.selectionStart;
        end = textArea.selectionEnd;
    }

    return new kaltura_js_CursorPos(start, end);

}

function kaltura_js_setCursorPosition(textArea, cursorPos) {
    if (document.selection) { // IE?
        var sel = textArea.createTextRange();
        sel.collapse(true);
        sel.moveStart("character", cursorPos.start);
        sel.moveEnd("character", cursorPos.end - cursorPos.start);
        sel.select();
    } else if (textArea.selectionStart || (textArea.selectionStart == "0")) { // Mozilla/Netscape?
        textArea.selectionStart = cursorPos.start;
        textArea.selectionEnd = cursorPos.end;
    }

//    textArea.focus();
}


/*	SWFObject v2.0 <http://code.google.com/p/swfobject/>
    Copyright (c) 2007 Geoff Stearns, Michael Williams, and Bobby van der Sluis
    This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
*/
// var swfobject=function(){var Z="undefined",P="object",B="Shockwave Flash",h="ShockwaveFlash.ShockwaveFlash",W="application/x-shockwave-flash",K="SWFObjectExprInst",G=window,g=document,N=navigator,f=[],H=[],Q=null,L=null,T=null,S=false,C=false;var a=function(){var l=typeof g.getElementById!=Z&&typeof g.getElementsByTagName!=Z&&typeof g.createElement!=Z&&typeof g.appendChild!=Z&&typeof g.replaceChild!=Z&&typeof g.removeChild!=Z&&typeof g.cloneNode!=Z,t=[0,0,0],n=null;if(typeof N.plugins!=Z&&typeof N.plugins[B]==P){n=N.plugins[B].description;if(n){n=n.replace(/^.*\s+(\S+\s+\S+$)/,"$1");t[0]=parseInt(n.replace(/^(.*)\..*$/,"$1"),10);t[1]=parseInt(n.replace(/^.*\.(.*)\s.*$/,"$1"),10);t[2]=/r/.test(n)?parseInt(n.replace(/^.*r(.*)$/,"$1"),10):0}}else{if(typeof G.ActiveXObject!=Z){var o=null,s=false;try{o=new ActiveXObject(h+".7")}catch(k){try{o=new ActiveXObject(h+".6");t=[6,0,21];o.AllowScriptAccess="always"}catch(k){if(t[0]==6){s=true}}if(!s){try{o=new ActiveXObject(h)}catch(k){}}}if(!s&&o){try{n=o.GetVariable("$version");if(n){n=n.split(" ")[1].split(",");t=[parseInt(n[0],10),parseInt(n[1],10),parseInt(n[2],10)]}}catch(k){}}}}var v=N.userAgent.toLowerCase(),j=N.platform.toLowerCase(),r=/webkit/.test(v)?parseFloat(v.replace(/^.*webkit\/(\d+(\.\d+)?).*$/,"$1")):false,i=false,q=j?/win/.test(j):/win/.test(v),m=j?/mac/.test(j):/mac/.test(v);/*@cc_on i=true;@if(@_win32)q=true;@elif(@_mac)m=true;@end@*/return{w3cdom:l,pv:t,webkit:r,ie:i,win:q,mac:m}}();var e=function(){if(!a.w3cdom){return }J(I);if(a.ie&&a.win){try{g.write("<script id=__ie_ondomload defer=true src=//:><\/script>");var i=c("__ie_ondomload");if(i){i.onreadystatechange=function(){if(this.readyState=="complete"){this.parentNode.removeChild(this);V()}}}}catch(j){}}if(a.webkit&&typeof g.readyState!=Z){Q=setInterval(function(){if(/loaded|complete/.test(g.readyState)){V()}},10)}if(typeof g.addEventListener!=Z){g.addEventListener("DOMContentLoaded",V,null)}M(V)}();function V(){if(S){return }if(a.ie&&a.win){var m=Y("span");try{var l=g.getElementsByTagName("body")[0].appendChild(m);l.parentNode.removeChild(l)}catch(n){return }}S=true;if(Q){clearInterval(Q);Q=null}var j=f.length;for(var k=0;k<j;k++){f[k]()}}function J(i){if(S){i()}else{f[f.length]=i}}function M(j){if(typeof G.addEventListener!=Z){G.addEventListener("load",j,false)}else{if(typeof g.addEventListener!=Z){g.addEventListener("load",j,false)}else{if(typeof G.attachEvent!=Z){G.attachEvent("onload",j)}else{if(typeof G.onload=="function"){var i=G.onload;G.onload=function(){i();j()}}else{G.onload=j}}}}}function I(){var l=H.length;for(var j=0;j<l;j++){var m=H[j].id;if(a.pv[0]>0){var k=c(m);if(k){H[j].width=k.getAttribute("width")?k.getAttribute("width"):"0";H[j].height=k.getAttribute("height")?k.getAttribute("height"):"0";if(O(H[j].swfVersion)){if(a.webkit&&a.webkit<312){U(k)}X(m,true)}else{if(H[j].expressInstall&&!C&&O("6.0.65")&&(a.win||a.mac)){D(H[j])}else{d(k)}}}}else{X(m,true)}}}function U(m){var k=m.getElementsByTagName(P)[0];if(k){var p=Y("embed"),r=k.attributes;if(r){var o=r.length;for(var n=0;n<o;n++){if(r[n].nodeName.toLowerCase()=="data"){p.setAttribute("src",r[n].nodeValue)}else{p.setAttribute(r[n].nodeName,r[n].nodeValue)}}}var q=k.childNodes;if(q){var s=q.length;for(var l=0;l<s;l++){if(q[l].nodeType==1&&q[l].nodeName.toLowerCase()=="param"){p.setAttribute(q[l].getAttribute("name"),q[l].getAttribute("value"))}}}m.parentNode.replaceChild(p,m)}}function F(i){if(a.ie&&a.win&&O("8.0.0")){G.attachEvent("onunload",function(){var k=c(i);if(k){for(var j in k){if(typeof k[j]=="function"){k[j]=function(){}}}k.parentNode.removeChild(k)}})}}function D(j){C=true;var o=c(j.id);if(o){if(j.altContentId){var l=c(j.altContentId);if(l){L=l;T=j.altContentId}}else{L=b(o)}if(!(/%$/.test(j.width))&&parseInt(j.width,10)<310){j.width="310"}if(!(/%$/.test(j.height))&&parseInt(j.height,10)<137){j.height="137"}g.title=g.title.slice(0,47)+" - Flash Player Installation";var n=a.ie&&a.win?"ActiveX":"PlugIn",k=g.title,m="MMredirectURL="+G.location+"&MMplayerType="+n+"&MMdoctitle="+k,p=j.id;if(a.ie&&a.win&&o.readyState!=4){var i=Y("div");p+="SWFObjectNew";i.setAttribute("id",p);o.parentNode.insertBefore(i,o);o.style.display="none";G.attachEvent("onload",function(){o.parentNode.removeChild(o)})}R({data:j.expressInstall,id:K,width:j.width,height:j.height},{flashvars:m},p)}}function d(j){if(a.ie&&a.win&&j.readyState!=4){var i=Y("div");j.parentNode.insertBefore(i,j);i.parentNode.replaceChild(b(j),i);j.style.display="none";G.attachEvent("onload",function(){j.parentNode.removeChild(j)})}else{j.parentNode.replaceChild(b(j),j)}}function b(n){var m=Y("div");if(a.win&&a.ie){m.innerHTML=n.innerHTML}else{var k=n.getElementsByTagName(P)[0];if(k){var o=k.childNodes;if(o){var j=o.length;for(var l=0;l<j;l++){if(!(o[l].nodeType==1&&o[l].nodeName.toLowerCase()=="param")&&!(o[l].nodeType==8)){m.appendChild(o[l].cloneNode(true))}}}}}return m}function R(AE,AC,q){var p,t=c(q);if(typeof AE.id==Z){AE.id=q}if(a.ie&&a.win){var AD="";for(var z in AE){if(AE[z]!=Object.prototype[z]){if(z=="data"){AC.movie=AE[z]}else{if(z.toLowerCase()=="styleclass"){AD+=' class="'+AE[z]+'"'}else{if(z!="classid"){AD+=" "+z+'="'+AE[z]+'"'}}}}}var AB="";for(var y in AC){if(AC[y]!=Object.prototype[y]){AB+='<param name="'+y+'" value="'+AC[y]+'" />'}}t.outerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'+AD+">"+AB+"</object>";F(AE.id);p=c(AE.id)}else{if(a.webkit&&a.webkit<312){var AA=Y("embed");AA.setAttribute("type",W);for(var x in AE){if(AE[x]!=Object.prototype[x]){if(x=="data"){AA.setAttribute("src",AE[x])}else{if(x.toLowerCase()=="styleclass"){AA.setAttribute("class",AE[x])}else{if(x!="classid"){AA.setAttribute(x,AE[x])}}}}}for(var w in AC){if(AC[w]!=Object.prototype[w]){if(w!="movie"){AA.setAttribute(w,AC[w])}}}t.parentNode.replaceChild(AA,t);p=AA}else{var s=Y(P);s.setAttribute("type",W);for(var v in AE){if(AE[v]!=Object.prototype[v]){if(v.toLowerCase()=="styleclass"){s.setAttribute("class",AE[v])}else{if(v!="classid"){s.setAttribute(v,AE[v])}}}}for(var u in AC){if(AC[u]!=Object.prototype[u]&&u!="movie"){E(s,u,AC[u])}}t.parentNode.replaceChild(s,t);p=s}}return p}function E(k,i,j){var l=Y("param");l.setAttribute("name",i);l.setAttribute("value",j);k.appendChild(l)}function c(i){return g.getElementById(i)}function Y(i){return g.createElement(i)}function O(k){var j=a.pv,i=k.split(".");i[0]=parseInt(i[0],10);i[1]=parseInt(i[1],10);i[2]=parseInt(i[2],10);return(j[0]>i[0]||(j[0]==i[0]&&j[1]>i[1])||(j[0]==i[0]&&j[1]==i[1]&&j[2]>=i[2]))?true:false}function A(m,j){if(a.ie&&a.mac){return }var l=g.getElementsByTagName("head")[0],k=Y("style");k.setAttribute("type","text/css");k.setAttribute("media","screen");if(!(a.ie&&a.win)&&typeof g.createTextNode!=Z){k.appendChild(g.createTextNode(m+" {"+j+"}"))}l.appendChild(k);if(a.ie&&a.win&&typeof g.styleSheets!=Z&&g.styleSheets.length>0){var i=g.styleSheets[g.styleSheets.length-1];if(typeof i.addRule==P){i.addRule(m,j)}}}function X(k,i){var j=i?"visible":"hidden";if(S){c(k).style.visibility=j}else{A("#"+k,"visibility:"+j)}}return{registerObject:function(l,i,k){if(!a.w3cdom||!l||!i){return }var j={};j.id=l;j.swfVersion=i;j.expressInstall=k?k:false;H[H.length]=j;X(l,false)},getObjectById:function(l){var i=null;if(a.w3cdom&&S){var j=c(l);if(j){var k=j.getElementsByTagName(P)[0];if(!k||(k&&typeof j.SetVariable!=Z)){i=j}else{if(typeof k.SetVariable!=Z){i=k}}}}return i},embedSWF:function(n,u,r,t,j,m,k,p,s){if(!a.w3cdom||!n||!u||!r||!t||!j){return }r+="";t+="";if(O(j)){X(u,false);var q=(typeof s==P)?s:{};q.data=n;q.width=r;q.height=t;var o=(typeof p==P)?p:{};if(typeof k==P){for(var l in k){if(k[l]!=Object.prototype[l]){if(typeof o.flashvars!=Z){o.flashvars+="&"+l+"="+k[l]}else{o.flashvars=l+"="+k[l]}}}}J(function(){R(q,o,u);if(q.id==u){X(u,true)}})}else{if(m&&!C&&O("6.0.65")&&(a.win||a.mac)){X(u,false);J(function(){var i={};i.id=i.altContentId=u;i.width=r;i.height=t;i.expressInstall=m;D(i)})}}},getFlashPlayerVersion:function(){return{major:a.pv[0],minor:a.pv[1],release:a.pv[2]}},hasFlashPlayerVersion:O,createSWF:function(k,j,i){if(a.w3cdom&&S){return R(k,j,i)}else{return undefined}},createCSS:function(j,i){if(a.w3cdom){A(j,i)}},addDomLoadEvent:J,addLoadEvent:M,getQueryParamValue:function(m){var l=g.location.search||g.location.hash;if(m==null){return l}if(l){var k=l.substring(1).split("&");for(var j=0;j<k.length;j++){if(k[j].substring(0,k[j].indexOf("="))==m){return k[j].substring((k[j].indexOf("=")+1))}}}return""},expressInstallCallback:function(){if(C&&L){var i=c(K);if(i){i.parentNode.replaceChild(L,i);if(T){X(T,true);if(a.ie&&a.win){L.style.display="block"}}L=null;T=null;C=false}}}}}();

/**
 * SWFObject v1.5: Flash Player detection and embed - http://blog.deconcept.com/swfobject/
 *
 * SWFObject is (c) 2007 Geoff Stearns and is released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 */
if(typeof deconcept=="undefined"){var deconcept=new Object();}if(typeof deconcept.util=="undefined"){deconcept.util=new Object();}if(typeof deconcept.SWFObjectUtil=="undefined"){deconcept.SWFObjectUtil=new Object();}deconcept.SWFObject=function(_1,id,w,h,_5,c,_7,_8,_9,_a){if(!document.getElementById){return;}this.DETECT_KEY=_a?_a:"detectflash";this.skipDetect=deconcept.util.getRequestParameter(this.DETECT_KEY);this.params=new Object();this.variables=new Object();this.attributes=new Array();if(_1){this.setAttribute("swf",_1);}if(id){this.setAttribute("id",id);}if(w){this.setAttribute("width",w);}if(h){this.setAttribute("height",h);}if(_5){this.setAttribute("version",new deconcept.PlayerVersion(_5.toString().split(".")));}this.installedVer=deconcept.SWFObjectUtil.getPlayerVersion();if(!window.opera&&document.all&&this.installedVer.major>7){deconcept.SWFObject.doPrepUnload=true;}if(c){this.addParam("bgcolor",c);}var q=_7?_7:"high";this.addParam("quality",q);this.setAttribute("useExpressInstall",false);this.setAttribute("doExpressInstall",false);var _c=(_8)?_8:window.location;this.setAttribute("xiRedirectUrl",_c);this.setAttribute("redirectUrl","");if(_9){this.setAttribute("redirectUrl",_9);}};deconcept.SWFObject.prototype={useExpressInstall:function(_d){this.xiSWFPath=!_d?"expressinstall.swf":_d;this.setAttribute("useExpressInstall",true);},setAttribute:function(_e,_f){this.attributes[_e]=_f;},getAttribute:function(_10){return this.attributes[_10];},addParam:function(_11,_12){this.params[_11]=_12;},getParams:function(){return this.params;},addVariable:function(_13,_14){this.variables[_13]=_14;},getVariable:function(_15){return this.variables[_15];},getVariables:function(){return this.variables;},getVariablePairs:function(){var _16=new Array();var key;var _18=this.getVariables();for(key in _18){_16[_16.length]=key+"="+_18[key];}return _16;},getSWFHTML:function(){var _19="";if(navigator.plugins&&navigator.mimeTypes&&navigator.mimeTypes.length){if(this.getAttribute("doExpressInstall")){this.addVariable("MMplayerType","PlugIn");this.setAttribute("swf",this.xiSWFPath);}_19="<embed type=\"application/x-shockwave-flash\" src=\""+this.getAttribute("swf")+"\" width=\""+this.getAttribute("width")+"\" height=\""+this.getAttribute("height")+"\" style=\""+this.getAttribute("style")+"\"";_19+=" id=\""+this.getAttribute("id")+"\" name=\""+this.getAttribute("id")+"\" ";var _1a=this.getParams();for(var key in _1a){_19+=[key]+"=\""+_1a[key]+"\" ";}var _1c=this.getVariablePairs().join("&");if(_1c.length>0){_19+="flashvars=\""+_1c+"\"";}_19+="/>";}else{if(this.getAttribute("doExpressInstall")){this.addVariable("MMplayerType","ActiveX");this.setAttribute("swf",this.xiSWFPath);}_19="<object id=\""+this.getAttribute("id")+"\" classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" width=\""+this.getAttribute("width")+"\" height=\""+this.getAttribute("height")+"\" style=\""+this.getAttribute("style")+"\">";_19+="<param name=\"movie\" value=\""+this.getAttribute("swf")+"\" />";var _1d=this.getParams();for(var key in _1d){_19+="<param name=\""+key+"\" value=\""+_1d[key]+"\" />";}var _1f=this.getVariablePairs().join("&");if(_1f.length>0){_19+="<param name=\"flashvars\" value=\""+_1f+"\" />";}_19+="</object>";}return _19;},write:function(_20){if(this.getAttribute("useExpressInstall")){var _21=new deconcept.PlayerVersion([6,0,65]);if(this.installedVer.versionIsValid(_21)&&!this.installedVer.versionIsValid(this.getAttribute("version"))){this.setAttribute("doExpressInstall",true);this.addVariable("MMredirectURL",escape(this.getAttribute("xiRedirectUrl")));document.title=document.title.slice(0,47)+" - Flash Player Installation";this.addVariable("MMdoctitle",document.title);}}if(this.skipDetect||this.getAttribute("doExpressInstall")||this.installedVer.versionIsValid(this.getAttribute("version"))){var n=(typeof _20=="string")?document.getElementById(_20):_20;n.innerHTML=this.getSWFHTML();return true;}else{if(this.getAttribute("redirectUrl")!=""){document.location.replace(this.getAttribute("redirectUrl"));}}return false;}};deconcept.SWFObjectUtil.getPlayerVersion=function(){var _23=new deconcept.PlayerVersion([0,0,0]);if(navigator.plugins&&navigator.mimeTypes.length){var x=navigator.plugins["Shockwave Flash"];if(x&&x.description){_23=new deconcept.PlayerVersion(x.description.replace(/([a-zA-Z]|\s)+/,"").replace(/(\s+r|\s+b[0-9]+)/,".").split("."));}}else{if(navigator.userAgent&&navigator.userAgent.indexOf("Windows CE")>=0){var axo=1;var _26=3;while(axo){try{_26++;axo=new ActiveXObject("ShockwaveFlash.ShockwaveFlash."+_26);_23=new deconcept.PlayerVersion([_26,0,0]);}catch(e){axo=null;}}}else{try{var axo=new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");}catch(e){try{var axo=new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");_23=new deconcept.PlayerVersion([6,0,21]);axo.AllowScriptAccess="always";}catch(e){if(_23.major==6){return _23;}}try{axo=new ActiveXObject("ShockwaveFlash.ShockwaveFlash");}catch(e){}}if(axo!=null){_23=new deconcept.PlayerVersion(axo.GetVariable("$version").split(" ")[1].split(","));}}}return _23;};deconcept.PlayerVersion=function(_29){this.major=_29[0]!=null?parseInt(_29[0]):0;this.minor=_29[1]!=null?parseInt(_29[1]):0;this.rev=_29[2]!=null?parseInt(_29[2]):0;};deconcept.PlayerVersion.prototype.versionIsValid=function(fv){if(this.major<fv.major){return false;}if(this.major>fv.major){return true;}if(this.minor<fv.minor){return false;}if(this.minor>fv.minor){return true;}if(this.rev<fv.rev){return false;}return true;};deconcept.util={getRequestParameter:function(_2b){var q=document.location.search||document.location.hash;if(_2b==null){return q;}if(q){var _2d=q.substring(1).split("&");for(var i=0;i<_2d.length;i++){if(_2d[i].substring(0,_2d[i].indexOf("="))==_2b){return _2d[i].substring((_2d[i].indexOf("=")+1));}}}return "";}};deconcept.SWFObjectUtil.cleanupSWFs=function(){var _2f=document.getElementsByTagName("OBJECT");for(var i=_2f.length-1;i>=0;i--){_2f[i].style.display="none";for(var x in _2f[i]){if(typeof _2f[i][x]=="function"){_2f[i][x]=function(){};}}}};if(deconcept.SWFObject.doPrepUnload){if(!deconcept.unloadSet){deconcept.SWFObjectUtil.prepUnload=function(){__flash_unloadHandler=function(){};__flash_savedUnloadHandler=function(){};window.attachEvent("onunload",deconcept.SWFObjectUtil.cleanupSWFs);};window.attachEvent("onbeforeunload",deconcept.SWFObjectUtil.prepUnload);deconcept.unloadSet=true;}}if(!document.getElementById&&document.all){document.getElementById=function(id){return document.all[id];};}var getQueryParamValue=deconcept.util.getRequestParameter;var FlashObject=deconcept.SWFObject;var SWFObject=deconcept.SWFObject;