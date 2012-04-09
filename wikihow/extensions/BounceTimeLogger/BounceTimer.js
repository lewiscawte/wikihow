/**

DEPRECATED DEPRECATED DEPRECATED

Moved to: skins/common/stu.js

**/
	var odds = 250;
	var rand = Math.floor(Math.random()*odds);
	var whLoggerEnable = (rand==1 && wgNamespaceNumber==0 && wgAction=="view");

	var whStartTime = false;
	var whLoadTime = false;
	var id = Math.floor(Math.random() * 100000);
	var whLoggerUrl='/Special:BounceTimeLogger?v=5&id='+id+'&time=';
	var whDuration = 0;
	var whC = (console && console.log);

	function whGetTime(){
		var date = new Date();
		return date.getTime();
	}

	function whBounceTime(e) {
		if (!whLoggerEnable) return;  //do nothing if whStartTime wasn't set

		var viewTime = -1;
		if (whStartTime){ 
			//whStartTime may not be set if window was blurred, then close
			//without being brought to the foreground
			viewTime = (whGetTime() - whStartTime);
			whDuration = whDuration + viewTime;
		}
		whStartTime = false;

		//total = total time the page was in foreground
		//open  = total time the page was open
		//unload= time since last time page wasn't focused
		var loggerUrl = whLoggerUrl + (whDuration/1000) + '&page='+wgPageName + '&evt=total';
		loggerUrl += '&time3=' + ((whGetTime()-whLoadTime)/1000)+ '&evt3=open';
		if (viewTime>0){
			loggerUrl += '&time2=' + (viewTime/1000) + '&evt2=unload';
		}
		$.ajax({url:loggerUrl,async:false});
	}

	function whUnload(){ whBounceTime('unload'); }
	
	function whBlur(){ 
		if (!whLoggerEnable) return;
		var viewTime = whGetTime() - whStartTime;
		whDuration += viewTime; 
		whStartTime = false;
		var loggerUrl = whLoggerUrl + (viewTime/1000) + '&page='+wgPageName + '&evt=blur';
		$.ajax({url:loggerUrl,async:false});
	}

	if (whLoggerEnable){
		whStartTime = whGetTime(); 
		whLoadTime = whGetTime();
		$(window).unload(whUnload);
		$(window).focus(function(){if(whLoggerEnable)whStartTime=whGetTime();});
		$(window).blur(whBlur);
	} 
