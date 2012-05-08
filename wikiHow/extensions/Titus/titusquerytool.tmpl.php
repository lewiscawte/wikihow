<link rel="stylesheet" type="text/css" href="/extensions/wikihow/titus/jquery.sqlbuilder.css" />
<style type="text/css">
.urls {
	margin-top: 5px;
	height: 300px;
}
</style>

<script type="text/javascript">

$(document).ready(function() { 

    $('.sqlbuild').sqlquerybuilder({ 
		fields: <?=$dbfields?>,
        showgroup:false,
        showcolumn:true,
        showsort:false,
		showwhere:true
    }); 

    $('.fetch').click(function(){
		var data = {
			'sql' : $('.sqlbuild').getSQBClause('all'),
			'urls': $('.urls').val(),
			'page-filter': $('input[name=page-filter]:checked').val(),
			'csvtype' : $('input[name=csvtype]:checked').val()
		};
		$.download('/' + wgPageName, data);           
    
		return false;
    }); 


    $('#getsql').click(function(){
     	alert($('.sqlbuild').getSQBClause('all')); 
     	return false;
    }); 
	
	$('input[value=all]').click(function() {
		$('.urls').slideUp('fast');
	});

	$('input[value=urls]').click(function() {
		$('.urls').slideDown('slow');
	});

    
});


</script>


<div id=sqlreport>
<div class="sqlbuild"></div>
</div>
<div style="margin-top: 10px">
		<input type="radio" name="page-filter" value="all"> Across All Pages
		<input type="radio" name="page-filter" value="urls" checked="checked"> Given the following URLs
</div>
<textarea class="urls" rows="500" name="urls" id="urls"></textarea>
<div style="margin-top: 10px">
<button class="fetch" style="padding: 5px;" value="CSV">Gimme</button>
<a id='getsql' href='#'>SQL</a>
</div>

