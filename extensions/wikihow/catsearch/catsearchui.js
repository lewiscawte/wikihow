$(function() {
	var ci_url = 'http://diy:diy267@jordan.wikidiy.com/Special:CategoryInterests';

	function addInterest(message, id) {
		$.get(ci_url, {a: 'add', cat: id}, function(data) {
			var urlDiv = $("<div/>").text(id).addClass("csui_hidden")
			var closeSpan = $("<span/>").text("X").addClass("csui_close");
			$( "<div/>" ).text(message).append(closeSpan).append(urlDiv).addClass("csui_category ui-widget-content").prependTo( "#categories" );
			//$( "#categories" ).attr( "scrollTop", 0 );
		});
	}
	
	function isDup(id) {
		var isDup = false;
		$("#categories").children().each(function(i, cat) {
			if ($(cat).children('div:first').html() == id) {
				isDup = true;
				return false;
			}
		});

		return isDup;
	}

	$(".csui_close").live('click', function(e) {
		var interestDiv = $(this).parent();
		var interest = $(this).parent().children('div:first').text();
		$.get(ci_url, {a: 'remove', cat: interest}, function(data) {
			$(interestDiv).hide();
		});
	});

	$("#csui_interests").autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: "http://www.wikihow.com/Special:CatSearch",
				dataType: "jsonp",
				data: {
					q: request.term
				},
				success: function( data ) {
					response( $.map( data.results, function( item ) {
						return {
							label: item.label,
							value: item.url
						}
					}));
				}
			});
		},
		minLength: 3,
		select: function( event, ui ) {
			$("#csui_interests").removeClass("ui-autocomplete-loading");
			if(!isDup(ui.item.value)) {
				addInterest(ui.item.label, ui.item.value);
				return false;
			}
			return false;
		},
		focus: function(event, ui) { 
			$('#csui_interests').val(ui.item.label); 
			return false;
		},
	});
});
