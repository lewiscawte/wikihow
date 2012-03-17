<?
$messages = array();

$messages['en'] = 
        array(
			'createpage_congratulations' => 'Congratulations - Your Article is Published',
			'createpage' => 'Create a Page',
			'createpage_instructions' => 'Enter the title of wikiHow you wish to create and hit submit:',
			'createpage_boxes' => "<div class='cpbox'>
		<h3>I know what I want to write about</h3>
		Enter your article's title below and click on Submit. <br/>
		Be sure to phase it on the form of a \"how-to\" (e.g. How to <b>Walk a Dog</b>)
		<form method='GET' onsubmit='return checkform()' name='createform'>
		How to <input maxLength='256' size='60%' name='target' value=''>
		<br/>
		<input type='submit' value='Submit'>
		</form>
		</div>

		<div class='cpbox'>
		<h3>I want article topic suggestions</h3>
		Enter any keywords and we will suggest some unwritten titles for you to write <br/>
		<form method='POST' onsubmit='return checkform()' name='createform_topics'>
		<input type='text' name='q' size='50'>
		<br/>
		<input type='submit' value='Submit'>
		</form>
		</div>

        <div class='cpbox'>
        <h3>I have other content I want to share</h3>
		Have an existing PDF, MS Word Doc, or already published web page you want to publish on wikiHow. Just email the file or URLs to us and we'll post it for you: Email <a href='mailto:wiki@wikihow.com'>wiki@wikihow.com</a>
        </div>
	",
		'createpage_nomatches' => 'Sorry, we had no matches for those keywords. Please try again.',
		'createpage_matches' => 'Your search returned the following matches:',
		'createpage_tryagain' => "Didn't find what you were looking for? Try another search here:",

		'managesuggestions' => "Manage suggestions", 
		'managesuggestions_boxes' => "<div class='cpbox'>
        <h3>Search for existing suggestions to delete</h3>
        <form method='POST' onsubmit='return checkform()' name='createform_topics'>
        <input type='text' name='q' size='50'>
        <br/>
        <input type='submit' value='Submit'>
        </form>
        </div>
		<div class='cpbox'>
        <h3>Add new suggestions</h3>
        <form method='POST' name='managesuggestions_add'>
		<textarea name='new_suggestions' style='width:500px; height:100px;'></textarea><br/>
		<input type='checkbox' name='formatted'> My suggestions are already formatted<br/>
        <input type='submit' value='Add'>
        </form>
        </div>
        <div class='cpbox'>
        <h3>Delete suggestions</h3>
        <form method='POST' name='managesuggestions_delete'>
        <textarea name='remove_suggestions' style='width:500px; height:100px;'></textarea><br/>
        <input type='submit' value='Delete'>
        </form>
        </div>
",
		'managesuggestions_log_add' => '$1 added a suggestion for "$2"',
		'managesuggestions_log_remove' => '$1 removed the suggestion for "$2"',
		'createpage_fromsuggestions' => "<div class='cpbox'>
        <h3>Create a page</h3>
				<form action='$2' method='GET'>
				<input type='hidden' name='action' value='edit'/>
				<input type='hidden' name='suggestion' value='1'/>
				<input type='text' style='width: 300px;' name='title' value=\"$1\">
				<input type='submit' value='Create page'>
				</form>
			</div>
		",
		'cp_loading' => 'Loading...',
		);
