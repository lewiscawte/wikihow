function pollTwitter()
{
	var twitter_id = $('#tweets_ticker li:first').attr("id").split('_');
	
	$.post( 'http://mark.wikidiy.com/Special:TwitterReplier', {
		action: 'latest',
		returnType: 'json',
		lastTwitterId: twitter_id[1]
	}, function( tweets ) {
		var tw = $.parseJSON( tweets );
		if( tw.tweets != null && tw.tweets.length > 0 ) {
			$("#new_tweets").show();
			$("#new_tweets").html( tw.tweets.length + ' new tweets ... Click to see!' ).effect( 'highlight');
			
			// first unbind any previous click
			$("#new_tweets").unbind( 'click' );
			
			// rebind click
			$("#new_tweets").click( function() {
				
				$("#tweets_ticker").prepend( tw.html );
				$("#new_tweets").hide();
			})
		}
	})
		
	t = setTimeout( "pollTwitter()", 20000 );
}


function wfMsg( key )
{
	var msg;
	$.ajaxSetup({
		async:false
	});
	$.post( 'http://mark.wikidiy.com/Special:TwitterReplier', {
		action: 'displayMessage', 
		key: key
	}, function(response){
		msg = response
	})
	$.ajaxSetup({
		async:true
	});
	
	displayDialogue( 'Error', msg );
	//return msg;
}

function displayDialogue( title, msg, width )
{
	if( isNaN( width ) ) {
		width = 400;
	}
	
	$("#dialog-box").html( '<p>'+msg+'</p>' );
	$("#dialog-box").dialog({
		width: width,
		modal: true,
		title: title
	});
}

function displayAuthentication( response )
{
	var obj = $.parseJSON( response );
	
	$("#authorizationContainer").dialog({
		width: 471,
		height: 263,
		modal: true
	});
	
	$("#authorizationContainer a").attr("href", obj.authorizationUrl );
}

function randomizeMessage( user, title, url )
{
	var msg = Array();
	var i = Math.floor( Math.random() * 3 );
	
	user = $.trim( user );
	title = $.trim( title );
	url = $.trim( url );
	
	msg[0] = '@' + user + ' maybe his will help, ' + title + ' ' + url;
	msg[1] = '@' + user + ' try these tips from @wikihow ' + title + ' ' + url;
	msg[2] = '@' + user + ' let me know if the @wikihow on ' + title + ' helps you. ' + url;
	
	return msg[i];
}


$(document).ready( function(){
	var t;
	var responded;
	var extensionUrl = '/Special:TwitterReplier';

	t = setTimeout( "pollTwitter()", 20000 );
	
	$('#twitter_poll').click( function(e) {
		pollTwitter();
		
		e.preventDefault();
	})
	
	$("#twitter_retrieve").click( function(e) {
		$.post( extensionUrl, {
			action: 'retrieve'
		}, function(response) {
			//alert( response );
			});
		
		e.preventDefault();
	})
	
	// handles default text for search input
	$("input[name='trSearch']").focus( function(e){
		if( $(this).val() == 'Search for' ) {
			$(this).val('');
		}
	})
	
	// handles default text for search input
	$("input[name='trSearch']").blur( function(e){
		if( $(this).val() == '' ) {
			$(this).val('Search for');
		}
	});
	
	// hitting enter onsearch box
	$("input[name='trSearch']").keydown(function(e){
		if( e.keyCode == '13' ) {
			$("#suggestedTitles tbody").html( '<tr><td>&nbsp;</td><td>Searching WikiHow</td></tr>' );
			
			$.post( extensionUrl, {
				action:'searchWikihow', 
				tweet: $(this).val()
			}, function( response ) {
				$("#reply_tweet").val( '' );
				$("#suggestedTitles tbody").html( response );
			})
			
			e.preventDefault();
		}
	})
	
	// create suggested tweet
	$(".suggested_article").live('click', function() {
		
		if( $(this).is(":checked" ) ) {
			var article = $.parseJSON( $(this).val() );
			var user = $(".reply_to_user").html();
			var msg = randomizeMessage(user, article.title, article.url);
									
			$("#reply_tweet").val( msg );
			if( $("#reply_tweet").val().length > 140 ) {
				$("#over_limit").html( (140 - $("#reply_tweet").val().length ) );
			}
			else {
				$("#over_limit").html( '' );
			}
		}
	})
	
	// tweets hover
	$("#tweets_ticker li").live('mouseover mouseout', function(e){
		if( e.type == 'mouseover' ) {
			$(this).css("background-color","#efeeeb");
		}
		else {
			if( !$(this).hasClass( 'locked' ) ) {
				$(this).css("background-color","white" );
			}
		}
	});
	
	//closes reply container
	$("#close a").click( function() {
		// hide reply container
		$("#reply_content").hide();
		$("#instructions").show();
		// remove locked class
		var liId  = $(".locked").attr("id"); 
		
		// unlock tweet
		var eTweetId = liId.split('_');
		
		unlockTweet( eTweetId[1] );
		
		// change background color to white
		$("#"+liId).css( 'background-color', 'white' );
	})
	
	// clicking on a tweet
	$("#tweets_ticker li").live( 'click', function() {
		var liId = $(this).attr("id");
		
		$(this).css("background-color","#efeeeb" );
		$("#over_limit").html("");
		
		$("#"+liId).addClass( 'locked' );
		
		var eTweet = liId.split('_');
		tweetId = eTweet[1];
		
		unlockTweet( responded, tweetId );
//		if( responded > 0 ) {
//			unlockTweet( responded, tweetId );
//			$.ajaxSetup({
//				async:false
//			});
//					
//			unlockTweets();
//					
//			$.ajaxSetup({
//				async:true
//			});
//		}
		
//		$.post( extensionUrl, {
//			action: 'lockTweet',
//			tweetId: tweetId
//		}, function( response ) {
//					
//			var json = $.parseJSON( response );
//
//			if( json.lock ) {
//				responded =  tweetId;
//				$("#reply_tweet").val( '' );
//				$("input[name='trSearch']").val('Search for');
//				$("#reply_container .reply_to_user").html( handle );
//				$("#instructions").hide();
//				$("#reply_content").show();
//				$("#reply_status_id").html( tweetId );
//				$("#suggestedTitles tbody").html('');
//				/* SUGGESTED TITLES CODE
//					$("#suggestedTitles tbody").html( '<tr><td>&nbsp;</td><td>Searching WikiHow</td></tr>' );
//
//					$.post( extensionUrl, {
//						action: 'searchWikihow',
//						searchCategoryId: searchCategoryId,
//						tweet: $("#twitter_" + tweetId + "_" + searchCategoryId + " span .tweet").html()
//					}, function( response ){
//
//						$("#suggestedTitles tbody").html( response );
//						$("input[name=trSearch]").val( $("#searchTerms").html() );
//					})
//				*/
//			}
//			else {
//				$("#"+liId).removeClass( 'locked' );
//				displayDialogue( 'Error', 'Someone is already responding to this tweet' );
//			}
//		})
	})
	
	// reply to user 
	$("#reply").click( function(e) {
		$.post( extensionUrl, {
			action: 'authenticate'
		}, function (response ){
			if( response.length > 0 ) {
				displayAuthentication( response );
			}
			else {
				var replyTweet = $("#reply_tweet").val();
				var replyStatusId = $("#reply_status_id").html();
				
				if( replyTweet.length > 0 && replyTweet.length < 141 ) {
					$.post( extensionUrl, {
						action: 'reply', 
						tweet: replyTweet, 
						replyStatusId: replyStatusId
					}, function( response ) {
						if( response.length == 0 ) {
							// TODO: move hiding rpely container into function
							$("#reply_content").hide();
							$("#instructions").show();
							$("#twitter_" + replyStatusId ).hide();
							$("#suggestedTitles tbody").html('');
							
							var profileImage = $("#profileImage").html();
							var screenName = $("#screenName").html();
							
							$.post( extensionUrl, {
								action:'displayTweet', 
								replyTweet: replyTweet, 
								profileImage: profileImage, 
								screenName: screenName
							}, function( response ) {
								displayDialogue( "Success", "<p>You've tweeted the following: </p>" + response, 500 );
							})
							
						}
						else {
							var respObj = $.parseJSON( response );
							displayDialogue( 'Error', 'An error occurred: ' + respObj.error );
						}
						
					})
				}
				else if( replyTweet.length > 140 ) {
					wfMsg( 'js-error-reply-too-long' );
				}
				else {
					wfMsg( 'js-error-reply-too-short' );
				}
			}
		})
		e.preventDefault();
	})
   
	// TODO: remove once we no longer need stream compare
	$('input[name="streamSubmit"]').click( function() {
		var keywords = $('input[name=keywords]').val();
		var numResults = $("select[name=numResults]").val();
		var inboxType = $("select[name=inboxType]").val();
	   
		$("#streamLeft").html( 'Loading ...' );
		$("#streamRight").html( 'Loading ...' );
		if( keywords.length > 0 ) {
			$.post(extensionUrl, {
				action: 'searchInbox', 
				keywords: keywords, 
				inboxType: inboxType, 
				numResults: numResults
			}, function( response ) {
				$("#streamLeft").html( response );
			})
			
			$.post(extensionUrl, {
				action:'searchTwitter', 
				keywords: keywords, 
				numResults: numResults
			}, function( response ){
				$("#streamRight").html( response );
			})
		}
		else {
			displayDialogue( 'Error', 'Please enter keywords');
		}
	})
	
	// HANDLE CLOSES WINDOW
	$(window).unload( function() {
//		if( responded.length > 0 ) {
//			$.ajaxSetup({
//				async:false
//			});
//			
//			unlockTweets();
//			
//			$.ajaxSetup({
//				async:true
//			});
//		}
	})
	
	function unlockTweets()
	{
		if( responded.length > 0 ) {
			for( i = 0; i < responded.length; i++ ) {
//				$("#twitter_" + responded[i]).removeClass( 'locked');
//				
//				$("#twitter_" + responded[i]).css( 'background-color', 'white' );
				
				unlockTweet( responded[i] );

				responded.splice( i, 1);
			}
		}
	}
	
	function lockTweet( tweetId )
	{
		var handle = $.trim( $("#twitter_" + tweetId + " span .twitter_handle").html() );
		
		$.post( extensionUrl, {
			action: 'lockTweet',
			tweetId: tweetId
		}, function( response ) {
					
			var json = $.parseJSON( response );

			if( json.lock ) {
				responded =  tweetId;
				$("#reply_tweet").val( '' );
				$("input[name='trSearch']").val('Search for');
				$("#reply_container .reply_to_user").html( handle );
				$("#instructions").hide();
				$("#reply_content").show();
				$("#reply_status_id").html( tweetId );
				$("#suggestedTitles tbody").html('');
				/* SUGGESTED TITLES CODE
					$("#suggestedTitles tbody").html( '<tr><td>&nbsp;</td><td>Searching WikiHow</td></tr>' );

					$.post( extensionUrl, {
						action: 'searchWikihow',
						searchCategoryId: searchCategoryId,
						tweet: $("#twitter_" + tweetId + "_" + searchCategoryId + " span .tweet").html()
					}, function( response ){

						$("#suggestedTitles tbody").html( response );
						$("input[name=trSearch]").val( $("#searchTerms").html() );
					})
				*/
			}
			else {
				$("#twitter_" + tweetId ).removeClass( 'locked' );
				displayDialogue( 'Error', 'Someone is already responding to this tweet' );
			}
		})
	}
	
	function unlockTweet( tweetId, newTweetId )
	{
		if( tweetId && tweetId.length > 0 ) {
			$.post( extensionUrl, {
				action: 'unlockTweet', 
				tweetId: tweetId
			}, function( json ) {
				response = $.parseJSON( json );
				if( response.unlock ) {
					
					$("#twitter_" + tweetId ).removeClass( 'locked');
					$("#twitter_" + tweetId ).css( 'background-color', 'white' );

					//lockTweet( newTweetId );
				}
				else {
					if( !response.unlock ) {
						displayDialogue( 'Error', 'Unable to unlock tweet: ' + tweetId + "\n Server response:" + json );
					}
				}
			})
		}
		//else {
			lockTweet( newTweetId );
		//}
	}

})