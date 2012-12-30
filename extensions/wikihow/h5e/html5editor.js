
// Avoid errors if debug stmts are left in
if (typeof console == 'undefined') console = {};
if (typeof console.log == 'undefined') console.log = function() {};

// Create WH container obj if necessary
if (typeof WH == 'undefined') var WH = {};

// wikiHow's HTML 5 editor
WH.h5e = (function ($) {

	var DIALOG_ZINDEX = 1000000;
	var DRAFTS_TIMER_SECS = 60; // seconds
	var CDN_BASE = 'http://pad1.whstatic.com';

	var mDraftToken = null,
		mDraftID = null,
		mEditTime = null,
		mEditToken = null,
		mDraftsInit = false,
		mSaveDraftTimer = null,
		mOldDraftId = 0,
		mDraftDirty = false;

	/**
	 * Re-numbers steps after a new step has been inserted, etc.
	 */
	function renumberSteps() {
		var steps = $('ol.steps_list_2', '#steps');
		steps.each(function(i, list) {
			var divs = $('div.step_num', list);
			var step = 1;
			divs.each( function(i, div) {
				$(div).html(step);
				step++;
			});
		});
	}

	/**
	 * Make the actual step numbers non-editable.
	 */
	function makeStepNumbersNonEditable() {
		$('div.step_num').attr('contenteditable', false);
	}

	/**
	 * Move the keyboard cursor to the start of a specified node (or at the
	 * specified position if one is present).
	 */
	function setCursorNode(node, position) {
		var range = document.createRange();
		if (typeof position == 'undefined') {
			range.selectNodeContents(node);
		} else {
			range.setStart(node, position);
			range.setEnd(node, position);
		}
		var select = window.getSelection();
		select.removeAllRanges();
		select.addRange(range);
	}

	/**
	 * User has hit 'Enter' key, so we want to create a new list item or new
	 * step.
	 */
	function createNewStepOnEnter(evt) {
		// if we're inside a UL element, don't create a new step
		if (!isStepsCursorTopLevel()) {
			return true;
		}

		var li = getCursorLi();

		var stepFilling = settings['non-empty-steps'] ? '&nbsp;' : '';
		var newstep_tmpl = '<li class="steps_li"><div class="step_num" contenteditable="false">1</div><b>' + stepFilling + '</b><div class="clearall"></div>';
		li.after(newstep_tmpl);
		var newli = li.next();

		if (li.hasClass('final_li')) {
			li.removeClass('final_li');
			newli.addClass('final_li');
		}

		var first = $('b', newli).first();
		if (first.length) {
			var node = first[0];
			setCursorNode(node, 0);
		}

		renumberSteps();

		return false;
	}

	/**
	 * Test whether two text nodes are equal.  Returns true iff they are equal.
	 */
	function textNodesEqual(a, b) {
		return a && b &&
			typeof a.nodeType != 'undefined' &&
			typeof b.nodeType != 'undefined' &&
			a.nodeType == b.nodeType &&
			a.textContent == b.textContent;
	}

	/**
	 * In the steps section, get the step where the keyboard cursor is
	 * currently placed.
	 */
	function getCursorLi(anchor) {
		if (typeof anchor == 'undefined') {
			var select = window.getSelection();
			anchor = select.anchorNode;
		}
		anchor = $(anchor)
		var currentLi = anchor.parentsUntil('#steps ol').last();
		if (!currentLi.length && anchor.is('li')) {
			currentLi = anchor;
		}
		return currentLi;
	}

	/**
	 * Return the text node where the keyboard cursor is currently placed.
	 */
	function getCursorTextNode() {
		var select = window.getSelection();
		var anchor = select.anchorNode;
		if (anchor.nodeName == '#text') {
			return anchor;
		} else {
			var firstTextNode = $(anchor).textNodes(true).first();
			if (firstTextNode.length) {
				return firstTextNode[0];
			} else {
				return anchor.nextSibling;
			}
		}
	}

	/**
	 * Remove the current step if the backspace key is hit and the keyboard
	 * cursor is at the start of a step.
	 */
	function removeStepOnBackspace(evt) {
//console.log('xx');
		var select = document.getSelection();
		if (select.length > 0) {
			var selSteps = getSelectionSteps();
			if (selSteps['start'] != selSteps['end']) {
				// need to re-number steps AFTER delete event has propagated
				window.setTimeout(function() {
					renumberSteps();
				}, 250);
			}

			// propagate 'delete'
			return true;
		}

		if (!isCursorAtListItemStart()) {
			// if we're in the middle of the step, don't remove step, just
			// backspace
//console.log('truthy');
			return true;
		}
//console.log('here');

		// get current li under cursor
		var currentli = getCursorLi();
		var prevli = currentli.prev();

		if (!prevli.length) {
			// if we're at the start of the first li, don't do anything
			return false;
		}

		// position cursor at preview li
		var textNodes = prevli.textNodes(true);
		if (textNodes.length >= 2) {
			// position cursor at end of last text node of previous li
			var node = textNodes.last().get(0);
			var position = node.length;
			setCursorNode(node, position);
		} else {
			var first = $('b', prevli).first();
			if (first.length) {
				var node = first[0];
				setCursorNode(node, 0);
			} else {
				// do something if the li has no text nodes
			}
		}

		$('.clearall', prevli).remove();
		removeBoldFromNode(currentli);

		if (currentli.hasClass('final_li')) {
			prevli.addClass('final_li');
		}

		// store the rest of the content of li which the cursor is on,
		// then remove it
		$('div.step_num', currentli).remove();
		var extraStepContents = currentli.contents();
		extraStepContents = extraStepContents.filter(function(i) {
			return !$(this).is('br');
		});
		prevli.append(extraStepContents);
		currentli.remove();

		renumberSteps();

		evt.preventDefault();
		return true;
	}

	/**
	 * Do single-level check for any bold nodes, replace them inline with their
	 * children.
	 */
	function removeBoldFromNode(li) {
		for (var i = 0; i < 2; i++) {
			$('b', li).each(function() {
				var child = $(this);
				var contents = child.contents();
				child.replaceWith(contents);
			});
		}
	}

	/**
	 * Check to see whether a keyboard event was a "special" key like the
	 * arrow keys or Esc
	 */
	function isEditingKeyEvent(evt) {
		// assume it's an edit key to start
		var ret = true;

		// in FF, when some special key is used, such as Esc, evt.which == 0
		if (evt.which == 0) {
			// Delete key
			if (typeof evt.keyCode != 'undefined' || evt.keyCode != 46) {
				ret = false;
			}
		}

		// in Chrome, evt.which is set sometimes when it's not set in FF,
		// such as for Esc, alt-key (by itself), ctrl key and arrow keys
		if (evt.which == 17 || evt.which == 18 ||
			evt.which == 27 || evt.which == 37 ||
			evt.which == 38 || evt.which == 39 ||
			evt.which == 40)
		{
			ret = false;
		}

		// when the Mac command key is pushed, metaKey is set
		if (typeof evt.ctrlKey != 'undefined' && !evt.ctrlKey &&
			typeof evt.altKey != 'undefined' && !evt.altKey &&
			typeof evt.metaKey != 'undefined' && evt.metaKey)
		{
			// except if paste, undo or cut are being used
			if (evt.which != 118 &&
				evt.which != 120 &&
				evt.which != 122)
			{
				ret = false;
			}
		}

		// ignore ctrl keys that are pushed
		if (typeof evt.ctrlKey != 'undefined' && evt.ctrlKey &&
			typeof evt.altKey != 'undefined' && !evt.altKey)
		{
			// Ctrl-y pastes on Mac
			if (evt.which != 89) {
				ret = false;
			}
		}


		return ret;
	}

	/**
	 * Tracks keystrokes and inserts new step numbers when a user presses enter,
	 * or deletes them on backspace.  This method is only called for certain
	 * keystrokes for efficiency.
	 */
	function onKeystroke(evt) {
		var key = evt.which;
		var propagate = true;

		if (isEditingKeyEvent(evt)) {
			// if array key was not hit, mark document changed so user
			// gets a warning when leaving page without saving
			setPageDirty();
		} else {
			onCursorCheck();
		}

		// for Chrome -- certain events need keyup and keypress
//console.log('x',evt.type,evt.which,evt);
		if (evt.type == 'keyup') return;
//		if (evt.type == 'keyup' && key != 8) return;
//		if (evt.type == 'keypress' && key == 8) return false;
//console.log('y',evt.type,evt.which,evt);

		if (key == 13 && typeof evt.isSimulated == 'undefined') { // 'Enter' key
			if (getCurrentCursorSection() == 'steps') {
				propagate = createNewStepOnEnter(evt);
			}
			onCursorCheck();
		} else if (key == 8) { // 'Backspace' key
			if (getCurrentCursorSection() == 'steps') {
				propagate = removeStepOnBackspace(evt);
			}

			// check if user deleted all steps -- recreate first one if they
			// did
			window.setTimeout(function() {
				var html = $('#steps').html();
				// check if steps are empty, after backspace event propagation
				if (html == '' || html == '<br>') {
					var inSteps = getCurrentCursorSection() == 'steps';
					var tmpl = '<ol class="steps_list_2"><li class="steps_li final_li"><div class="step_num" contenteditable="false">1</div><b id="h5e-new-first-step">' + wfMsg('h5e-first-step') + '</b></li></ol>';
					$('#steps').html(tmpl);
					var firstStep = $('#h5e-new-first-step');
					if (inSteps) {
						var len = wfMsg('h5e-first-step').length;
						loadCursorPos({'node': firstStep.contents().get(0), 'offset': len});
					}

					firstStep.attr('id', '');
				}
			}, 250);

			onCursorCheck();
		} else {
			var chr = String.fromCharCode(key);
			if (chr == '.' || chr == '?' || chr == ':' || chr == '!') {
				if (getCurrentCursorSection() == 'steps' &&
					document.queryCommandState('bold'))
				{
					document.execCommand('bold', false, '');
				}
			}
		}

		return propagate;
	}

	/**
	 * Used to check whether the cursor has italics, is indented, etc, to
	 * update the toolbar icons as the user clicks on different text to edit.
	 */
	var cursorHasItalics = false,
		cursorHasIndent = false;
	function onCursorCheck() {
		var italics = document.queryCommandState('italic');
		if (italics && !cursorHasItalics) {
			cursorHasItalics = true;
			$('#h5e-toolbar-italics').addClass('h5e-active');
		} else if (!italics && cursorHasItalics) {
			cursorHasItalics = false;
			$('#h5e-toolbar-italics').removeClass('h5e-active');
		}

		var select = window.getSelection();
		var parentLast = $(select.anchorNode).parentsUntil('ul').last();
		if (!parentLast.length || !parentLast.is('html')) {
			var indented = true;
		} else {
			var indented = false;
		}
		if (indented && !cursorHasIndent) {
			cursorHasIndent = true;
			$('#h5e-toolbar-outdent').removeClass('h5e-disabled');
		} else if (!indented && cursorHasIndent) {
			cursorHasIndent = false;
			$('#h5e-toolbar-outdent').addClass('h5e-disabled');
		}
	}

	/**
	 * Check whether the current li element is in fact a steps-level element.
	 * Returns true iff it is.
	 */
	function isStepsCursorTopLevel() {
		var select = window.getSelection();
		var anchor = $(select.anchorNode);
		var parents = anchor.parentsUntil('#bodycontents * ol');
		var gotUL = false;
		parents.each(function(i, node) {
			if ($(node).is('ul')) {
				gotUL = true;
				return false;
			}
		});
		if (gotUL && anchor.is('li') && parents.length == 2 && $(parents[0]).is('ul')) {
			return true;
		} else {
			return !gotUL;
		}
	}

	/**
	 * Determine's the high-level section where the cursor currently resides.
	 *
	 * @return the section, as a word, where the cursor currently resides.
	 *	 e.g. 'intro', 'steps', 'tips', etc.
	 */
	function getCurrentCursorSection() {
		var select = window.getSelection();
		var anchor = $(select.anchorNode);
		var parents = anchor.parentsUntil('#bodycontents');
		if (parents.length) {
			var sectionDiv = parents.last();
		} else {
			var sectionDiv = anchor;
		}
		var id = sectionDiv.attr('id');
		if (id !== '') {
			return id;
		} else {
			return 'intro';
		}
	}

	/**
	 * Returns true if and only if the keyboard cursor is at the start of a
	 * top level li in a step.	Used so that we can collapse 2 steps into one
	 * when user hits backspace at the start of a step.
	 */
	function isCursorAtListItemStart() {
		var select = window.getSelection();
		var li = getCursorLi();
		//if (select.anchorNode == li.get(0) && select.anchorOffset == 1) {
		//	return true;
		//} else {
			var clone = li.clone();
			$('div.step_num', clone).remove();
			var firstTextNodes = clone.textNodes(true).first();
			if (firstTextNodes.length) {
				var firstTextNode = firstTextNodes[0];
				var cursorTextNode = getCursorTextNode();
				if (textNodesEqual(firstTextNode, cursorTextNode)) {
					return select.anchorOffset == 0;
				} else {
					return false;
				}
			} else {
				return true;
			}
		//}
	}

	/**
	 * Returns true if and only if the start of the selection is on
	 * one step and the end of the selection is on another.
	 */
	function getSelectionSteps() {
		var select = window.getSelection();
		var start = getCursorLi(select.anchorNode).get(0);
		var end = getCursorLi(select.focusNode).get(0);
		return {'start' : start, 'end' : end};
	}

	/**
	 * Brings the cursor back to the section we're editing after a toolbar
	 * button was pressed.
	 */
	function focusCurrentSection(currentNode) {
		var node = !currentNode ? getCursorNode() : currentNode;
//console.log('xqq',node, 'ce',isNodeContentEditable(node),currentNode);
		if (!isNodeContentEditable(node)) return;

		var parents = $(node).parentsUntil('#bodycontents');
		// note: the 'parents' set is empty when cursor is in intro -- use
		// current node instead
		var focusNode = parents.length ? parents.last() : $(node);
		if (settings['needs-section-focus']) {
			focusNode.focus();
		}
	}

	function isNodeContentEditable(node) {
		node = $(node);
		var editable = node.attr('contentEditable');
		if (editable == 'inherit') {
			var parents = node.parents();
			for (var i = 0; i < parents.length; i++) {
				var parentEdit = $(parents[i]).attr('contentEditable');
				if (parentEdit != 'inherit') {
					editable = parentEdit;
					break;
				}
			}
		}
		return editable == 'true';
	}

	/**
	 * Move the keyboard cursor to the start of a section specified.  Make
	 * sure the browser view scrolls to that section if it's not there.
	 */
	function focusSection(section) {
		var sectionDiv, firstText;
		if (section == 'intro') {
			sectionDiv = $('#bodycontents .article_inner:first');
			// TODO: need to pull out first real non-blank text node (ignoring
			// all div.mwimg)
			firstText = $('p', sectionDiv).textNodes(true).first();
		} else if (section == 'relatedwikihows') {
			sectionDiv = [];
		} else if (section == 'steps') {
			sectionDiv = $('#steps');
			var div = $('#steps ol.steps_list_2 li');
			div = div.children().not('div.mwimg').not('div.step_num');
			firstText = div.textNodes(true).first();
		} else {
			sectionDiv = $('#' + section);
			var div = $('ul li', sectionDiv);
			if (!div.length) {
				div = sectionDiv;
			}
			firstText = div.textNodes(true).first();
		}
		if (sectionDiv.length) {
			if (settings['needs-section-focus']) {
				sectionDiv.focus();
			}
			if (firstText.length) {
				setCursorNode(firstText[0], 0);
			}
		}
	}

	/**
	 * Return the current cursor position in the document, so that it can be
	 * saved and loaded later.
	 *
	 * @return A tuple to be loaded with the loadCursorPos() function.
	 */
	function saveCursorPos() {
		var sel = window.getSelection();
		var savedNode = sel.anchorNode;
		var savedOffset = sel.anchorOffset;
		var range = savedNode ? sel.getRangeAt(0) : null;
		if (range && range.startOffset == range.endOffset) {
			range = null;
		}
		return { 'node': savedNode, 'offset': savedOffset, 'range': range };
	}

	/**
	 * Load the current cursor position from a save tuple.
	 *
	 * @param pos A tuple which was saved with saveCursorPos()
	 */
	function loadCursorPos(pos, callback) {
		var func = function() {
			if (pos && pos['node']) {
				setCursorNode(pos['node'], pos['offset']);
				if (pos['range']) {
					var sel = window.getSelection();
					sel.addRange(pos['range']);
				}
			}

			if (callback) callback();
		};
		if (settings['needs-delay-after-dialog']) {
			window.setTimeout(func, 0);
		} else {
			func();
		}
	}


	/**
	 * Toolbar add link button clicked.  Converts selected text into a link,
	 * or inserts a new link with default text if no text is selected.
	 */
	function tbAddLink() {
		focusCurrentSection();

		var cursorText = document.getSelection() + ''; // cast to string
		showEditLinkDialog('add', cursorText, '',
			function(text, link) { // when 'Change' button is clicked
				var title = getArticleFromLink(link);
				var html = '<a href="' + link + '" title="' + title + '">' + text + '</a>';
				if (cursorText == '') html += ' ';
				document.execCommand('inserthtml', false, html);
			}
		);

	}

	/**
	 * Stores the position of the cursor before entering the Image Upload
	 * dialog.
	 */
	var preEIUCursorLi = null,
		preEIUCursorNode = null,
		preEIUSection = null;

	/**
	 * Store the position of the cursor and call the Image Upload dialog.
	 */
	function tbAddImage(preAddCallback) {
		focusCurrentSection();
		preEIUSection = getCurrentCursorSection();
		preEIUCursorLi = getCursorLi();
		preEIUCursorNode = getCursorNode();

		// pushed-in look
		$('#h5e-toolbar-img').addClass('h5e-active');

		easyImageUpload.setCompletionCallback(
			// this callback is called by the image upload dialog finishes
			function (success, details) {
				$('#h5e-toolbar-img').removeClass('h5e-active');

				if (success) {
					if (typeof preAddCallback == 'function') preAddCallback();
					var html = generateImageHtml(details);
					insertImageHtml(details, html);
				}
			}
		);
		easyImageUpload.doEIUModal();
	}

	/**
	 * Converts the html encodable entities in a string into a string.	For
	 * example, converts the string "Bob & Linda" into "Bob &amp; Linda".
	 */
	function htmlEntitiesEncode(str) {
		return $('<div/>').text(str).html();
	}

	/**
	 * Builds the HTML for the image to be inserted from the details of a
	 * wikitext image tag.
	 *
	 * @param details all of the image tag
	 * @return the html to be inserted
	 */
	function generateImageHtml(details) {
		var width = details['chosen-width'];
		var height = details['chosen-height'];
		var encCaption = htmlEntitiesEncode(details['caption']);
		var isThumb = encCaption != '';
		var encFilename = htmlEntitiesEncode(encodeURIComponent(details['filename']));
		var encTag = htmlEntitiesEncode(details['tag']);
		var rlayout = details['layout'] == 'right';
		if (isThumb) {
			var ltag1 = '<div style="width: ' + (parseInt(width, 10) + 2) + 'px;" class="thumb ' + (rlayout ? 'tright' : 'tnone') + '">';
			var rtag1 = '</div>';
			var ltag2 = '';
			var rtag2 = '';
		} else {
			var ltag1 = '<div class="' + (rlayout ? 'floatright' : 'floatnone') + '">';
			var rtag1 = '</div>';
			var ltag2 = '<span>';
			var rtag2 = '</span>';
		}
		var captionHtml = encCaption ? '<a title="Enlarge" class="internal" href="/Image:' + encFilename + '"><img width="16" height="16" alt="" src="' + CDN_BASE + '/skins/common/images/magnify-clip.png"></a> <span contenteditable="true" class="caption">' + encCaption + '</span>' : '';
		var html = '<div contenteditable="false" class="mwimg">' + ltag1 + '<div style="width: ' + width + 'px; height: ' + height + 'px;" class="rounders">' + ltag2 + '<a title="' + encCaption + '" class="image" href="/Image:' + encFilename + '">' + details['html'] + '</a>' + rtag2 + '<div class="corner top_left"></div><div class="corner top_right"></div><div class="corner bottom_left"></div><div class="corner bottom_right"></div></div>' + captionHtml + '<input type="hidden" name="h5e_image" value="' + encTag + '" />' + rtag1 + '</div>';

		return html;
	}

	/**
	 * Inserts an image into the DOM, using the image details (the layout) and
	 * the previously generated html for the image.
	 *
	 * @note globals used: preEIUSection, preEIUCursorLi, preEIUCursorNode
	 */
	function insertImageHtml(details, html) {
		setPageDirty();
		var newHtml = $(html);

		// for the "steps" section
		if (preEIUSection == 'steps') {
			var currentli = preEIUCursorLi;

			if (details['layout'] == 'center') {
				var lastdiv = $('div.clearall', currentli).last();
				if (lastdiv.length) {
					lastdiv.before(newHtml);
				} else {
					currentli.append(newHtml);
				}
			} else {
				var currentli = preEIUCursorLi;
				var thisStep = $('div.step_num', currentli).first();
				thisStep.after(newHtml);
			}

		} else {
			var node = $(preEIUCursorNode);
			var container = null;
			if (node.is('li')) {
				container = node;
			} else {
				var parents = node.parentsUntil('#bodycontents');
				parents.each( function(i, node) {
					var par = $(node);
					if (par.is('li')) {
						container = par;
					}
				});
				if (!container) {
					container = parents.last();
				}
			}

			if (details['layout'] == 'center') {
				container.append(newHtml);
			} else {
				container.prepend(newHtml);
			}
		}

		$('.rounders', newHtml).mouseenter(imageHoverIn);
		$('a.internal', newHtml).click(function() { return false; });
	}

	/**
	 * Called when toolbar indent button is used.
	 */
	function tbIndent() {
		focusCurrentSection();
		setPageDirty();

		// get the existing list for this section, if there is one
		var currentSection = getCurrentCursorSection();
		if (currentSection != 'intro') {
			var appendNode = getCursorLi();
			var existingList = $('ul', appendNode).first();
		} else {
			var node = $( getCursorNode() );
			var parents = node.parentsUntil('#bodycontents * ul');
			if (parents.length) {
				var rootNode = $(parents).last();
				if (rootNode.is('li')) {
					var existingList = rootNode.parent();
				} else {
					var existingList = [];
				}
			} else {
				var existingList = node.parent();
			}

			var parents = node.parentsUntil('#bodycontents');
			var appendNode = $(parents).last();
		}

		// create a new list node
		var newItem = $('<li></li>');
		var newList = $('<ul></ul>');
		newList.append(newItem);

		// if there's an existing list in the section, we want to append to
		// this list rather than creating a new <ul> after it
		if (existingList.length) {
			var node = $( getCursorNode() );
			var li, parentLi = node.parentsUntil('ul').last();
			if (parentLi.length) {
				li = parentLi.first();
			} else {
				li = node;
			}
			// are we already inside a list?
			if (li.is('li')) {
				// if so, add bullet point to that list
				li.append(newList);
			} else {
				// if not, append to top level bullets
				existingList.append(newItem);
			}
		} else {
			$(appendNode).append(newList);
		}
		setCursorNode(newItem[0]);

		onCursorCheck();
	}

	/**
	 * Set the page being edited as dirty so that a browser warning pops
	 * up about the doc not being saved before the user leaves (via
	 * closing the window, a refresh, following a link, hitting back, etc).
	 */
	function setPageDirty() {
		if (!window.onbeforeunload) {
			$('#h5e-discard-changes')
				.add('#h5e-toolbar-publish')
				.removeClass('h5e-disabled');
			window.onbeforeunload = function() {
				return wfMsg('h5e-changes-to-be-discarded');
			};
		}

		// For new articles, we mark the current step or list item
		if (settings['create-new-article']) {
			var cursorNode = getCursorNode();
			if (cursorNode) {
				cursorNode = $(cursorNode);
				if (cursorNode.hasClass('h5e-first-unchanged')) {
					cursorNode.removeClass('h5e-first-unchanged');
					cursorNode.parents().removeClass('h5e-first-unchanged');
				}
			}
		}

		// Draft needs to be saved now, so we present to the user the option
		// of saving it now
		if (mDraftsInit && !mDraftDirty) {
			var tmpl = '<a href="#">$1</a>';
			mDraftDirty = true;
			$('#h5e-toolbar-savedraft')
				.html( wfTemplate(tmpl, wfMsg('h5e-savedraft')) )
				.unbind('click')
				.click(function() {
					saveDraft();
					return false;
				});
		}
	}

	/**
	 * Set the page being edited as clean, so there's no browser warning.
	 */
	function setPageClean() {
		$('#h5e-discard-changes')
			.add('#h5e-toolbar-publish')
			.addClass('h5e-disabled');
		window.onbeforeunload = null;
	}

	/**
	 * Check to see whether we've flagged the page being edited as dirty.
	 */
	function isPageDirty() {
		// cast to boolean
		return !!window.onbeforeunload;
	}

	/**
	 * Toolbar italics button
	 */
	function tbAddItalics() {
		focusCurrentSection();
		setPageDirty();
		document.execCommand('italic', false, '');
		onCursorCheck();
	}

	/**
	 * Toolbar "outdent" button
	 */
	function tbOutdent() {
		focusCurrentSection();
		setPageDirty();

		if (!isStepsCursorTopLevel()) {
			document.execCommand('outdent', false, '');
			onCursorCheck();
		}
	}

	/**
	 * Toolbar add a reference button was clicked.
	 */
	function tbAddReference() {
		focusCurrentSection();
		setPageDirty();

		var tmpl = '<a id="h5e-new-ref" class="h5e-button edit-reference" href="#">' + wfMsg('h5e-edit-ref') + '</a>';
		var refhtml = wfTemplate(tmpl, wfMsg('h5e-ref'));
		document.execCommand('inserthtml', false, refhtml);
		var newref = $('#h5e-new-ref');
		prepEditRefNode(newref, '');
		newref.attr('id', '');
		newref.click();
	}

	/**
	 * Pull the original reference text out of the button of the article's
	 * reference list.
	 */
	function getOrigRefText(refnode) {
		var refid = $('a', refnode).attr('href').replace(/^#/, '');
		var li = $('li#' + refid).clone();
		$('a:first', li).remove();
		var reftext = '';
		var specialFlattenRef = function(i,n) {
			if (n.nodeName == '#text') {
				reftext += n.textContent;
			} else if ($(n).is('a')) {
				reftext += $(n).attr('href');
			} else {
				$(n).contents().each(specialFlattenRef);
			}
		};
		li.contents().each(specialFlattenRef);
		reftext = $.trim(reftext);
		return reftext;
	}

	/**
	 * Add click listeners, etc, to add/edit reference dialog
	 */
	function prepEditRefNode(newref, reftext) {
		newref.data('editref', reftext);

		newref.click(function() {
			var button = this;
			var reftext = $(button).data('editref');
			$('#ref-edit')
				.val(reftext)
				.unbind('keypress')
				.keypress(function(evt) {
					if (evt.which == 13) { // 'Enter' pressed
						$('#ref-edit-change').click();
						return false;
					}
				});


			$('#ref-edit-change')
				.unbind('click')
				.click(function() {
					setPageDirty();
					$(button).data('editref', $('#ref-edit').val() );
					$('#edit-ref-dialog').dialog('close');
				});

			$('#ref-edit-cancel')
				.unbind('click')
				.click(function() {
					$('#edit-ref-dialog').dialog('close');
					return false;
				});

			var pos = saveCursorPos();
			$('#edit-ref-dialog').dialog({
				width: 400,
				minWidth: 400,
				modal: true,
				zIndex: DIALOG_ZINDEX,
				open: function() {
					$('#h5e-toolbar-ref').addClass('h5e-active');

					// Set correct dialog title
					if ($('#ref-edit').val() == '') {
						var title = wfMsg('h5e-add-reference');
						var button = wfMsg('h5e-add');
					} else {
						var title = wfMsg('h5e-edit-reference');
						var button = wfMsg('h5e-change');
					}
					$('#edit-ref-dialog').dialog('option', 'title', title);
					$('#ref-edit-change').val(button);

					$('#ref-edit').focus();
				},
				close: function(evt, ui) {
					$('#h5e-toolbar-ref').removeClass('h5e-active');

					focusCurrentSection(pos['node']);
					loadCursorPos(pos);
					if ($.trim( $(button).data('editref') ) == '') {
						$(button).remove();
					}
				}
			});

			return false;
		});

		newref.attr('contenteditable', 'false');
	}

	/**
	 * Toolbar button to show Related wikiHows dialog.
	 */
	function tbRelatedWikihows() {
		$('.h5e-related-sortable li').remove();
		var related = loadRelatedWikihows();
		$(related).each(function() {
			var title = this;
			var node = createRelatedWikihowSortableNode(title);
			$('.h5e-related-sortable').append(node);
		});
		$('.h5e-related-sortable')
			.sortable()
			.disableSelection();
		$('#related-wh-dialog').dialog({
			width: 500,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			open: function() {
				$('#h5e-toolbar-related').addClass('h5e-active');

				// remove any previous completedivs
				clearAutocompleteResults();

				// google-style auto-complete for related
				InstallAC(
					document['h5e-ac'],
					document['h5e-ac']['h5e-related-new'],
					document['h5e-ac']['h5e-related-add'],
					"/Special:TitleSearch?lim=10",
					"en");

				// customize ac results
				$('#completeDiv')
					.addClass('h5e-auto-complete');

				$('#h5e-related-new').focus();
			},
			close: function(evt, ui) {
				$('#h5e-toolbar-related').removeClass('h5e-active');

				clearAutocompleteResults();
			}
		});
	}

	/**
	 * In the Related wikiHows dialog, we use a jQuery UI sortable element
	 * to be able to add new related wikihows.	This method creates a list
	 * item for that sortable list.
	 */
	function createRelatedWikihowSortableNode(title) {
		var tmpl = '<li class="h5e-related-li"><span class="related-wh-title">$1</span><div class="trash-icon"><a href="#"><img src="' + CDN_BASE + '/skins/WikiHow/images/tiny_trash.gif" /></div></li>';
		var howto = wfMsg('howto', title);
		var shortened = getArticleDisplay(howto, 40);
		var node = $( wfTemplate(tmpl, shortened) );
		$('.related-wh-title', node).attr('title', howto);
		$('a', node).click(function() {
			var li = $(this).parentsUntil('li').last().parent();
			li.remove();
		});
		node.data('title', title);
		return node;
	}

	/**
	 * Return the possible wikihow sections
	 */
	function getSections() {
		var sections = [
			{'key': 'ingredients', 'name': wfMsg('Ingredients'), 'editable': true},
			{'key': 'steps', 'name': wfMsg('Steps'), 'editable': false},
			{'key': 'video', 'name': wfMsg('Video'), 'editable': false},
			{'key': 'tips', 'name': wfMsg('Tips'), 'editable': true},
			{'key': 'warnings', 'name': wfMsg('Warnings'), 'editable': true},
			{'key': 'thingsyoullneed', 'name': wfMsg('thingsyoullneed'), 'editable': true},
			{'key': 'relatedwikihows', 'name': wfMsg('relatedwikihows'), 'editable': false},
			{'key': 'sources', 'name': wfMsg('sourcescitations'), 'editable': true}
		];
		return sections;
	}

	/**
	 * Convert the names returned by getSections() to id's that match
	 * those in the <a id="..." name="..."></a> article anchor elements.
	 */
	function sectionNameToID(name) {
		name = name.replace(/ /g, '_');
		name = name.replace(/'/g, '.27');
		return name;
	}

	/**
	 * Brings up the section add pop up
	 */
	function tbAddSection() {

		$('#h5e-sections-dialog').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			open: function() {
				$('#h5e-toolbar-section').addClass('h5e-active');
			},
			close: function(evt, ui) {
				$('#h5e-toolbar-section').removeClass('h5e-active');
				focusCurrentSection();
			}
		});
		var div = $('#h5e-sections').html('');
		var sections = getSections();
		$(sections).each(function(i, section) {
			var id = 'h5e-sections-' + section['key'];
			var isSteps = section['key'] == 'steps';
			var disabled = !section['editable'] ? ' disabled="disabled"' : '';
			var sectionPresent = $('#' + section['key']).length > 0;
			var checked = sectionPresent ? ' checked="checked"' : '';
			var input = $('<input type="checkbox" id="' + id + '" name="sections" value="' + section['key'] + '"' + disabled + checked + ' /> <label for="' + id + '">'  + section['name'] + '</label><br/>');
			input.appendTo(div);

			if (isSteps) {
				var amDiv = $('<div id="h5e-sections-am" class="h5e-alternate-methods"></div>');
				amDiv.appendTo(div);

				drawAlternateMethodsHTML();
			}
		});
	}

	/**
	 * Get the list of sections to add or remove from the section edit
	 * dialog.
	 */
	function getSectionsToAddOrRemove() {
		var toRemove = [];
		var toAdd = [];
		var firstNewSection = null;

		var sections = getSections();
		$(sections).each(function(i, section) {
			var sectionNode = $('#' + section['key']);
			var sectionPresent = sectionNode.length > 0;
			var id = 'h5e-sections-' + section['key'];
			var checked = $('#' + id + ':checked').val();
			if (sectionPresent && !checked) {
				toRemove.push( section['key'] );
			} else if (!sectionPresent && checked) {
				if (!firstNewSection) {
					firstNewSection = section['key'];
				}
				toAdd.push( section['key'] );
			}
		});

		return {
			'add': toAdd,
			'remove': toRemove,
			'first': firstNewSection
		};
	}

	/**
	 * A list of content of removed sections, so the content can be retrieved
	 * and re-inserted into the DOM if the user re-adds the section and the
	 * page hasn't been reloaded.
	 */
	var removedSections = {};

	/**
	 * Inserts the new section into the HTML, deletes removed ones
	 */
	function addOrRemoveSections(toAdd, toRemove, saveRemoved) {
		var sections = getSections();
		saveRemoved = typeof saveRemoved == 'undefined' ? true : saveRemoved; // defaul true
		$(sections).each(function(i, section) {
			if ($.inArray(section['key'], toRemove) >= 0) {
				// Remove a section, put it in the list of removed section
				var node = $('#' + section['key']);
				var nodes = node
					.add( node.prev() )
					.add( node.nextUntil('h2') )
					.detach();
				if (saveRemoved && nodes.length) {
					removedSections[ section['key'] ] = nodes;
				}
			} else if ($.inArray(section['key'], toAdd) >= 0) {
				// Add a new section
				if (typeof removedSections[ section['key'] ] == 'undefined') {
					var defaultContent = section['key'] != 'relatedwikihows' ? '<ul><li>' + wfMsg('h5e-new-section') + '</li></ul>' : '<ul></ul>';
					var addSection = $('<h2><span>' + section['name'] + '</span></h2><div id="' + section['key'] + '" class="article_inner editable" contenteditable="true">' + defaultContent + '</div>');
				} else {
					var addSection = removedSections[ section['key'] ];
				}
				var foundKey = false, foundSibling = false;
				$(sections).each(function(j, sectionj) {
					if (sectionj['key'] == section['key']) {
						foundKey = true;
					} else if (foundKey && !foundSibling) {
						var node = $('#' + sectionj['key']);
						if (node.length) {
							foundSibling = true;
							if (node.prev().is('h2')) {
								node = node.prev();
							}
							node.before(addSection);
						}
					}
				});
				if (!foundSibling) {
					$('#bodycontents').append(addSection);
				}
			}
		});
	}

	/**
	 * Re-draw the alternate methods for the section change dialog, based on
	 * which alternate methods are in the DOM.
	 */
	function drawAlternateMethodsHTML() {
		var div = $('#h5e-sections-am');
		div.html('');

		// get list of alternate methods
		var methods = $('#steps h3');

		// do html for alternate methods
		methods.each(function(i, method) {
			var id = 'h5e-am-' + i;
			var methodName = $(method).children().first().html();
			var checked = ' checked="checked"';
			var input = $('<input type="checkbox" id="' + id + '" name="sections" value="' + i + '"' + checked + ' /> <label for="' + id + '">' + methodName + '</label><br/>');
			input.appendTo(div);
		});

		var id = 'h5e-sections-add-method';
		var link = $('<a href="#" id="' + id + '">' + wfMsg('h5e-new-alternate-method') + '</a><br/>');
		link.appendTo(div);

		$('#' + id).click(function () {
			$('#h5e-am-name').val('');
			$('#h5e-am-dialog').dialog({
				width: 400,
				minWidth: 400,
				modal: true,
				zIndex: DIALOG_ZINDEX
			});
			$('#h5e-am-name').focus();
			return false;
		});
	}

	/**
	 * Delete all the alternate methods that were unselected in the Section
	 * change dialog.
	 */
	function deleteUnselectedAlternateMethods() {
		var methods = $('#steps h3');
		methods.each(function(i, method) {
			var id = 'h5e-am-' + i;
			var checked = $('#' + id + ':checked').val();
			if (!checked) {
				// Remove this alternate method
				var jmethod = $(method);
				jmethod.nextUntil('#steps h3').remove();
				jmethod.remove();
			}
		});
	}

	/**
	 * Add a new blank alternate method to the DOM
	 */
	function addAlternateMethod(name) {
		var methods = $('#steps h3');
		var escName = name.replace(/ /g, '_');
		var html = $('<p><a name="' + escName + '" id="' + escName + '"></a></p><h3><span>' + name + '</span></h3><ol class="steps_list_2"><li class="steps_li final_li"><div contenteditable="false" class="step_num">1</div><b>' + wfMsg('h5e-new-method') + '</b><div class="clearall"></div></li></ol>');
		$('#steps').append(html);
	}

	/**
	 * Get the edit summary from the given selector (representing a unique
	 * html input element).
	 */
	function getEditSummary(selector) {
		var editSummary = $(selector).val();
		editSummary = $.trim(editSummary);
		if (editSummary == wfMsg('h5e-enter-edit-summary') ||
			editSummary == wfMsg('h5e-edit-summary-examples'))
		{
			editSummary = '';
		}
		return editSummary;
	}

	/**
	 * Call to the server to save the html we've edited.  Call the method
	 * onFinish after save is complete.
	 */
	function saveArticle(onFinish) {
		var contents = $('#bodycontents');
		var editSummary = getEditSummary('#h5e-edit-summary-pre');
		var data = contents.html();
		window.clearTimeout(mSaveDraftTimer);
		$.post('/Special:Html5editor',
			{ eaction: 'publish-html',
			  target: wgPageName,
			  summary: editSummary,
			  edittoken: mEditToken,
			  edittime: mEditTime,
			  html: data },
			function (result) {
				if (result && !result['error']) {
					contents.html(result['html']);
				} else if (!result) {
					result = { 'error': wfMsg('h5e-server-connection-error') };
				}
				if (onFinish) onFinish(result['error']);
			},
			'json'
		);

		return false;
	}

	function addNewArticleFeatures() {
		$('.h5e-first-add-image').click(function () {
			tbAddImage(function () {
				$('.h5e-first-add-image').remove();
			});
			return false;
		});
		$('#bodycontents h2').each(function() {
			var heading = $('span', this).text();
			if (heading != wfMsg('Steps') && heading != wfMsg('relatedwikihows')) {
				$(this).append('<span class="h5e-first-remove-section"><a href="#" class="h5e-first-remove-section-a">' + wfMsg('h5e-remove-section') + '</a></span>');
			}
		});
		$('.h5e-first-remove-section').click(function() {
			setPageDirty();
			removeSectionConfirm(this);
			return false;
		});

		// default edit summary
		$('#h5e-edit-summary-pre')
			.removeClass('h5e-example-text')
			.val(wfMsg('h5e-create-new-article'));
	}

	function removeNewArticleFeatures() {
		$('.h5e-first-unchanged').remove();
		$('#bodycontents h2').each(function() {
			var h2 = $(this), next = h2.next();
			if (!next.length || next.is('h2')) h2.remove();
		});
	}

	/**
	 * Brings up the section removal confirmation
	 */
	function removeSectionConfirm(obj) {
		var removeID = $(obj).parent().next().attr('id');

		$('#h5e-sections-confirm').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			close: function(evt, ui) {
				focusCurrentSection();
			}
		});

		//listen for the answer
		$('#h5e-sections-confirm-remove').click(function() {
			addOrRemoveSections([], [removeID], false);
			$('#h5e-sections-confirm').dialog('close');
			return false;
		});
		$('#h5e-sections-confirm-cancel').click(function() {
			$('#h5e-sections-confirm').dialog('close');
			return false;
		});
	}

	function newArticleChecks() {
		var bodyCopy = $('#bodycontents').clone();
		$('.h5e-first-unchanged', bodyCopy).remove();
		$('h2', bodyCopy).each(function() {
			var h2 = $(this), next = h2.next();
			if (!next.length || next.is('h2')) h2.remove();
		});
		var intro = bodyCopy.children().first();
		var steps = $('#steps', bodyCopy);
		$('h2', bodyCopy).remove();

		var text = {
			intro: intro.text(),
			steps: steps.text(),
			full: bodyCopy.text()
		};
		for (var i in text) {
			if (text.hasOwnProperty(i)) {
				text[i] = $.trim( text[i].replace(/(\s|\n)+/g, ' ') );
			}
		}

		var countWords = function(text) {
			text = $.trim(text);
			if (text) {
				var words = text.split(' ');
				return words.length;
			} else {
				return 0;
			}
		};

		var warnURLParams = '';
		var introWords = countWords(text['intro']);
		if (introWords <= 4) {
			warnURLParams = '?warn=intro&words=' + introWords;
		} else {
			var allWords = countWords(text['full']);
			if (allWords <= 100) {
				warnURLParams = '?warn=words&words=' + allWords;
			} else {
				var upCount = text['full'].match(/[A-Z]/g).length;
				var lowCount = text['full'].match(/[a-z]/g).length;
				var puncCount = text['full'].match(/[-!.,?]/g).length;
				var ratio = upCount / (upCount + lowCount);
				if (ratio >= 0.10) {
					var rounded = Math.round(ratio*1000)/1000;
					warnURLParams = '?warn=caps&ratio=' + rounded;
				} else if (puncCount <= 10) {
					warnURLParams = '?warn=sentences&sen=' + puncCount;
				}
			}
		}

		if (warnURLParams) {
			$('#dialog-box').html('');
			$('#dialog-box').load('/Special:CreatepageWarn' + warnURLParams, function() {
				$('#dialog-box input')
					.attr('onclick', '')
					.unbind('click')
					.click(function() {
						clickshare(28);
						$('#dialog-box').dialog('close');
						return false;
					});
				$('#dialog-box a').last()
					.attr('onclick', '')
					.unbind('click')
					.click(function() {
						clickshare(29);
						$('#dialog-box').dialog('close');
						stopEditing(true, true);
						return false;
					});
			});
			$('#dialog-box').dialog({
				width: 600,
				modal: true,
				zIndex: DIALOG_ZINDEX,
				title: wfMsg('warning')
			});
			return false;
		} else {
			return true;
		}
	}

	function postNewArticlePrompt() {
		var isAnon = !!wgUserID;
		var dialogWidth = isAnon ? 750 : 560;
		$('#dialog-box').html('');
		$('#dialog-box').load('/Special:CreatepageFinished');
		$('#dialog-box').dialog({
			width: dialogWidth,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			title: wfMsg('congrats-article-published')
		});
	}

	var TOOLBAR_HEIGHT_PIXELS = 63;

	function slideWholePage(direction) {
		// add or remove pixels to the top of the first div on the page
		// so that it doesn't feel like the edit bar is covering anything
		// that can't be found any longer
		var topMargin = $('#header').css('margin-top');
		topMargin = parseInt(topMargin.replace(/px/), 10);
		if (direction == 'down' && topMargin < TOOLBAR_HEIGHT_PIXELS ||
			direction == 'up' && topMargin > -TOOLBAR_HEIGHT_PIXELS)
		{
			var sign = direction == 'down' ? '+' : '-';
			$('#header').animate({'margin-top': sign + '=' + TOOLBAR_HEIGHT_PIXELS + 'px'}, 'slow');
		}
	}

	function hideTemplates() {
		var templates = $('.template');

		var vids = templates.filter(function() {
			return $('object', this).length > 0;
		});
		var nonVids = templates.not(vids);

		var tmpl = '<div class="h5e-hidden-video"><p>' + wfMsg('h5e-hidden-video') + '</p></div>';
		vids
			.addClass('opaque')
			.before(tmpl);

		$('.h5e-hidden-video')
			.css('width',vids.width())
			.css('height',vids.height());

		var tmpl = '<div class="h5e-hidden-template"><span>' + wfMsg('h5e-hidden-template') + '</span></div>';
		nonVids
			.fadeOut()
			.after(tmpl);

		$('.h5e-hidden-video p')
			.attr('contenteditable', false);
	}

	function showTemplates() {
		$('.h5e-hidden-video')
			.add('.h5e-hidden-template')
			.remove();
		$('.template').removeClass('opaque');
		$('.template').fadeIn();
	}

	function slideToolbar(direction, func) {
		if (direction != 'up' && direction != 'down')
			throw 'bad param: direction';

		if (!func) func = function() {};


		if (direction == 'down') {
			$('#h5e-editing-toolbar')
				.css('top', '-' + TOOLBAR_HEIGHT_PIXELS + 'px')
				.show()
				.animate(
					{'top': '+=' + TOOLBAR_HEIGHT_PIXELS + 'px'},
					{ duration: 'slow',
					  complete: func }
				);
		} else {
			$('#h5e-editing-toolbar')
				.css('top', '0')
				.animate(
					{'top': '-=' + TOOLBAR_HEIGHT_PIXELS + 'px'},
					{ duration: 'slow',
					  complete: function() { $(this).hide(); func() } }
				);
		}
		slideWholePage(direction);
	}

	function removeAds() {
		gHideAds = true;

		$('.wh_ad').fadeOut();

		// hide Meebo if it's on the page
		if (typeof Meebo != 'undefined') {
			Meebo('hide');
		}

		// We do a big hack to stop Meebo and Google messing with our page if
		// we're anonymous and trying to edit a new article.  We get strange
		// warnings and a blank page sometimes otherwise (from a post-dom-ready
		// document.write() call).
		document.write = function() {};
	}

	function highlightArticle() {
		//highlight article in blue
		$('.article_top').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/module_caps_hl.png)');
		$('.article_bottom').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/module_caps_hl.png)');
		$('#article').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/article_bgs_hl.png)');
		$('#last_question').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/article_bgs_hl.png)');

		//grey stuff out
		$('#header').addClass('opaque');
		$('#sidebar').addClass('opaque');
		$('#breadcrumb li').addClass('opaque');
		$('#originators').addClass('opaque');
		$('#article_info').addClass('opaque');
		$('#share_icons').addClass('opaque');
		$('#end_options').addClass('opaque');
		$('#embed_this').addClass('opaque');
		$('#last_question p').addClass('opaque');
		$('#page_rating').addClass('opaque');
		$('#footer_shell').addClass('opaque');

		//ribbons
		$('#article_tools_header h2').wrapInner("<span class='opaque'>");
		$('#article_info_header').wrapInner("<span class='opaque'>");

		//if ($('#video').length > 0) $('#video').addClass('opaque');
	}

	function unhighlightArticle() {
		//removed blue highlight from article
		$('.article_top').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/module_caps.png)');
		$('.article_bottom').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/module_caps.png)');
		$('#article').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/article_bgs.png)');
		$('#last_question').css('background-image', 'url(' + CDN_BASE + '/skins/WikiHow/images/article_bgs.png)');

		//remove grey
		$('#header').removeClass('opaque');
		$('#sidebar').removeClass('opaque');
		$('#breadcrumb li').removeClass('opaque');
		$('#originators').removeClass('opaque');
		$('#article_info').removeClass('opaque');
		$('#share_icons').removeClass('opaque');
		$('#end_options').removeClass('opaque');
		$('#embed_this').removeClass('opaque');
		$('#last_question p').removeClass('opaque');
		$('#page_rating').removeClass('opaque');
		$('#footer_shell').removeClass('opaque');

		//ribbons are special
		$temp_span = $('#article_tools_header h2 span');
		if ($temp_span.length > 0) $('#article_tools_header h2').text($temp_span.html());
		$temp_span = $('#article_info_header span');
		if ($temp_span.length > 0) $('#article_info_header').text($temp_span.html());
	}

	/**
	 * Method called when we enter HTML5 editing mode.
	 */
	function startEditing(section, postEditFunc) {

		// do we have to load a draft? 
		var draft = window.location.search.match(/draft=[0-9]+/);
		if (draft) {
			var draftid = draft[0].replace("draft=", "");
			loadDraft(draftid);
		}

		// make all of the editable sections contenteditable=true, hide
		// templates, and set up key handler for the steps function
		$('.editable')
			.attr('contenteditable', true)
			.addClass('h5e-std-editing-outline');

		$('img, .mwimg, #video')
			.attr('contenteditable', false);
		$('.caption').attr('contenteditable', true);

		var tmpl = '<a class="h5e-adv-link" href="/index.php?title=' + wgPageName + '&action=edit&advanced=true">' + wfMsg('h5e-switch-advanced') + '</a>';
		$('.edit_article_button').after(tmpl);

		removeAds();

		$('#st_wrap').fadeOut();
		hideTemplates();
		$('#toc').fadeOut();
		$("h2").each(function() {
			if ($(this).html() == "Contents") $(this).fadeOut();
		});

		$('.h5e-tb-save-wrapper').css('display', 'none');
		$('.h5e-tb-function-wrapper').css('display', 'block');

		$('#h5e-edit-summary-pre, #h5e-edit-summary-post')
			.blur();

		slideToolbar('down');

		// highlight article in blue
		highlightArticle();

		var howtoTitle = wfMsg('howto', wgTitle);
		var msg = settings['create-new-article'] ? wfMsg('h5e-creating-title') : wfMsg('h5e-editing-title');
		var editingTitle = wfTemplate(msg, howtoTitle);
		$('.firstHeading').html(editingTitle);

		// listen to keystrokes in the html5 editing areas
		$('#bodycontents').keypress(onKeystroke);
		$('#bodycontents').click(onCursorCheck);
		$('#bodycontents').bind('keypress click', tooltipLinkListener);

		if (settings['monitor-keyup-events']) {
			$('#bodycontents').keyup(onKeystroke);
		}

		hideEditListeners();

		$('.twitter-share-button, .like_button').hide();

		$('a.internal').click(function() { return false; });

		// Stop the RC widget from updating and scrolling
		rcStop();

		$('#relatedwikihows').attr('contenteditable', false);

		// Add a new related wikihows section if it didn't exist before
		if (!$('#relatedwikihows').length) {
			addOrRemoveSections(['relatedwikihows'], []);
		}

		var relatedWikihows = loadRelatedWikihows();
		saveRelatedWikihowsInactive(relatedWikihows);

		if (!settings['create-new-article']) {
			$('.rounders').css('border', '10px solid #FFF');
			$('.rounders').css('z-index', 1000000);
			$('.rounders').mouseenter(imageHoverIn);
			$('#h5e-edit-summary-pre').show();
		} else {
			addNewArticleFeatures();
			$('#h5e-edit-summary-pre').hide();
		}

		$('.step_num').click(function() {
			var next = $(this).next();
			if (next.length) {
				setCursorNode(next[0], 0);
			}
			return false;
		});

		// replace all references with an "edit reference" link
		$('sup.reference').each(function(i, refnode) {
			var reftext = getOrigRefText(refnode);
			var tmpl = '<a id="h5e-new-ref" class="h5e-button edit-reference" href="#">' + wfMsg('h5e-edit-ref') + '</a>';
			var newref = $(tmpl);
			$(refnode).replaceWith(newref);
			prepEditRefNode(newref, reftext);
		});

		var msg = wfMsg('h5e-references-removed');
		var html = $('<div class="sources-removed" contenteditable="false">' + msg + '</div>');
		$('ol.references').replaceWith(html);

		var sections = getSections();
		$(sections).each(function() {
			var section = this['name'];
			var id = sectionNameToID(section);
			$('a#'+id).remove();
		});

		// grab the js variables from the server
		$.post('/Special:Html5editor',
			{ eaction: 'get-vars',
			  target: wgPageName
			},
			function (result) {
				mDraftToken = result['drafttoken'];
				mEditToken	= result['edittoken'];
				mEditTime	= result['edittime'];
				mOldDraftId = result['olddraftid'];
				if (mOldDraftId > 0) {
					var saveDraftLink = $('#h5e-toolbar-savedraft');
					if (saveDraftLink.html() == '') {
						var tmpl = '<a href="#">$1</a>';
						saveDraftLink
							.html( wfTemplate(tmpl, wfMsg('h5e-loaddraft')) )
							.unbind('click')
							.click(function() {
								loadDraftConfirm(mOldDraftId);
								return false;
							});
					}
				}
			},
			'json'
		);

		if (wgUserID && !mDraftsInit) {
			mSaveDraftTimer = window.setInterval(saveDraft, DRAFTS_TIMER_SECS * 1000);
			mDraftsInit = true;
		}
		// just in case the user navigates away from the page
		setPageClean();

		// in case we are doing manual revert
		if (window.location.search.indexOf('oldid') >= 0) {
			setPageDirty();
		}

		if (section && section != 'relatedwikihows') {
			focusSection(section);
		}

		if (postEditFunc) {
			postEditFunc();
		}

		$('#bodycontents').attr('contenteditable', 'true');
	}

	/**
	 * When an editing conflict occurs...
	 */
	function showConflictWarning() {
		$("#h5e-message-console")
			.html("Ooops! Someone (Tderouin) has just edited and saved this page. Do you want to: <br/><a href=''>Save a draft</a>? .... or .... <a href=''>Save the page anyway?</a> ... or ... <a href=''>Continue editing</a>?")
			.show("slow", "swing");
	}

	/**
	 * Displays a dialog to the user after they click the "load draft" link
	 */
	function loadDraftConfirm(id) {
		$('#h5e-loaddraft-confirm').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			close: function(evt, ui) {
				focusCurrentSection();
			}
		});

		// listen for the answer from the user
		$('#h5e-loaddraft-confirm-load').click(function() {
			// discard any article changes, no prompt
			setPageClean();

			loadDraft(id);
			$('#h5e-loaddraft-confirm').dialog('close');
			return false;
		});
		$('#h5e-loaddraft-confirm-cancel').click(function() {
			$('#h5e-loaddraft-confirm').dialog('close');
			return false;
		});

	}

	/**
	 * Loads the draft for the article from the server
	 *
	 * @param id The draft ID -- a number
	 */
	function loadDraft(id) {
		var contents = $('#bodycontents');
		$.post('/Special:Html5editor',
			{ eaction: 'load-draft',
			  userid : wgUserID,
			  target: wgPageName,
			  draftid: id
			},
			function (result) {
				if (!result['error']) {
					contents.html(result['html']);
				}
			},
			'json'
		);
	}

	/**
	 * Save the draft for the article to the server
	 */
	function saveDraft() {
		// only save the draft if the page has been changed
		if (!isPageDirty() || !mDraftDirty) {
			return;
		}

		if (mSaveDraftTimer) {
			window.clearInterval(mSaveDraftTimer);
			mSaveDraftTimer = null;
		}
		var editSummary = getEditSummary('#h5e-edit-summary-pre');
		var contents = $('#bodycontents');
		var data = contents.html();
		var tmpl = '<span class="h5e-nonlink">$1</span>';
		var saveDraftLink = $('#h5e-toolbar-savedraft');
		saveDraftLink
			.html( wfTemplate(tmpl, wfMsg('h5e-saving-lc')) )
			.unbind('click')
			.click( function() { return false; } );
		$.post('/Special:Html5editor',
			{ eaction: 'save-draft',
			  target: wgPageName,
			  summary: editSummary,
			  edittoken: mEditToken,
			  drafttoken: mDraftToken,
			  edittime: mEditTime,
			  draftid: mDraftID,
			  editsummary: editSummary,
			  html: data },
			function (result) {
				if (!result['error']) {
					mDraftDirty = false;
					contents.html(result['html']);
				}
				mSaveDraftTimer = window.setInterval(saveDraft, DRAFTS_TIMER_SECS * 1000);	
				mDraftID = result['draftid'];
				if ($('span', saveDraftLink).html() == wfMsg('h5e-saving-lc')) {
					saveDraftLink
						.html( wfTemplate(tmpl, wfMsg('h5e-draftsaved')) );
				}
			},
			'json'
		);
	}

	/**
	 * Unhide a fixed position div and display it centered vertically and
	 * horizontally in the browser.
	 */
	function displayCenterFixedDiv(jqDiv) {
		if (jqDiv.length) {
			var w = jqDiv.width();
			var h = jqDiv.height();
			var winw = window.innerWidth;
			var winh = window.innerHeight;
			var nleft = Math.round(winw / 2 - w / 2);
			var ntop = Math.round(winh / 2 - h / 2);
			jqDiv
				.css({
					'display': 'block',
					'top': ntop,
					'left': nleft
				});
		}
	}

	/**
	 * Method called when we either exit HTML5 editing mode or when we
	 * first go to the page (while not in editing mode).
	 */
	function stopEditing(saveIt, overrideChecks) {
		if (saveIt) {
			if (settings['create-new-article']) {
				overrideChecks = typeof overrideChecks != 'undefined' ? overrideChecks : false;
				if (!overrideChecks && !newArticleChecks()) {
					return;
				}
			}

			var savingNotice = $('.h5e-saving-notice');
			displayCenterFixedDiv(savingNotice);
		}

		$('#bodycontents')
			.attr('contenteditable', 'false')
			.unbind('keypress keyup click');
		$('.editable').attr('contenteditable', false);

		$('.h5e-adv-link').remove();

		showTemplates();
		$('#st_wrap').fadeIn();

		$('.wh_ad').fadeIn();

		// un-highlight article
		unhighlightArticle();

		$('.twitter-share-button, .like_button').show();

		var howtoTitle = wfMsg('howto', wgTitle);
		$('.firstHeading').html(howtoTitle);

		// present when creating a new article
		if (settings['create-new-article']) {
			$('.h5e-first-add-image').unbind('click');
			$('.h5e-first-remove-section').remove();
		}
		else {
			$('.rounders').css('border', 'none');
			$('.rounders').css('z-index', 0);
		}

		var relatedWikihows = loadRelatedWikihows();
		saveRelatedWikihowsActive(relatedWikihows);

		// remove related wh section if there aren't any related wikihows
		if (!$(relatedWikihows).length) {
			addOrRemoveSections([], ['relatedwikihows']);
		}

		removeImageHoverListeners();
		$('#h5e-mwimg-mouseover').hide();

		// replace all "edit reference" links with real references
		$('.edit-reference').each(function(i, ref) {
			var reftext = $(ref).data('editref');
			var refhtml = $('<sup><a href="#" onclick="return false;">[ref]</a></sup><input type="hidden" id="h5e-ref-' + i + '" value="' + reftext + '"/>');
			$(ref).replaceWith(refhtml);
		});

		attachEditListeners();

		if (saveIt) {
			removeNewArticleFeatures();
			
			var timer = null;
			var onPublish = function(error) {
				if (timer) {
					clearTimeout(timer);
					timer = null;
				} else {
					return;
				}

				$('.h5e-saving-notice').hide();

				if (!error) {
					setPageClean();
				} else {
					$('#dialog-box').html(error);
					$('#dialog-box').dialog({
						width: 250,
						minWidth: 250,
						modal: true,
						zIndex: DIALOG_ZINDEX,
						title: wfMsg('h5e-error')
					});
					slideToolbar('up');
				}

				attachEditListeners();

				if (!error && settings['create-new-article']) {
					postNewArticlePrompt();


					// If we were creating an article, after saving it then 
					// editing it again, on this edit we don't want to add 
					// the article creation features
					settings['create-new-article'] = false;
				}
			};

			// if REST call lasts more than 16 seconds, we fail with an error
			// message and ignore the result.  we chose 16 seconds because
			// other stuff happens at 15 seconds.
			timer = window.setTimeout(function () {
				onPublish(wfMsg('h5e-publish-timeout'));
			}, 16000);
			saveArticle(onPublish);

			var editSummary = getEditSummary('#h5e-edit-summary-pre');
			if (!editSummary) {
				$('.h5e-tb-function-wrapper').fadeOut('fast', function() {
					$('.h5e-tb-save-wrapper').fadeIn();
				});

				// If the user hasn't started entering an edit summary or
				// put focus on the edit summary, hide the box
				window.setTimeout(function () {
					if ($('#h5e-edit-summary-post').val() == wfMsg('h5e-edit-summary-examples')
						&& $('#h5e-edit-summary-post').hasClass('h5e-example-text')
						&& $('#h5e-editing-toolbar:visible').length)
					{
						slideToolbar('up');
					}
				}, 15000);
			} else {
				slideToolbar('up', function() {
					$('#h5e-edit-summary-pre')
						.add('#h5e-edit-summary-post')
						.val('');
				});
			}
		}

	}

	/**
	 * Get DOM node where the cursor currently lies in the html5-edited
	 * document.
	 */
	function getCursorNode() {
		if (window.getSelection) { // should work in webkit/ff
			var node = window.getSelection().anchorNode;
			var startNode = (node && node.nodeName == "#text" ? node.parentNode : node);
			return startNode;
		} else {
			return null;
		}
	}

	/**
	 * Change a site link like "/Article-Name" to an article name like
	 * "Article Name"
	 */
	function getArticleFromLink(url) {
		url = url
			.replace(/^http:\/\/([^\/]*)(wikihow|wikidiy)\.com\//i, '');
		if (!url.match(/^http:\/\//)) {
			url = url
				.replace(/^\//, '')
				.replace(/-/g, ' ');
			return decodeURIComponent(url);
		} else {
			return url;
		}
	}

	/**
	 * Change an article name like "Article Name" to a site link like
	 * "/Article-Name"
	 */
	function getLinkFromArticle(article) {
		if (!article.match(/http:\/\//)) {
			return '/' + encodeURIComponent(article.replace(/ /g, '-'));
		} else {
			return article;
		}
	}

	/**
	 * Shorten the display of a link from something like this:
	 *
	 * This is a really really really really really really long link name
	 *
	 * to this:
	 *
	 * This is a really real...ng link name
	 */
	function getArticleDisplay(articleName, numChars) {
		if (!numChars) numChars = 45;
		articleName = articleName.replace(/^http:\/\//, '');
		if (articleName.length > numChars) {
			var start = Math.round(2*numChars / 3);
			var end = Math.round(1*numChars / 3);
			var re = new RegExp('^(.{' + start + '}).*(.{' + end + '})$');
			var m = articleName.match(re);
			return m[1] + '...' + m[2];
		} else {
			return articleName;
		}
	}

	/**
	 * Add click listeners to the editing toolbar elements.
	 */
	function tbClickListeners() {

		$('#h5e-toolbar-a').click(tbAddLink);
		$('#h5e-toolbar-img').click(tbAddImage);
		$('#h5e-toolbar-italics').click(tbAddItalics);
		$('#h5e-toolbar-indent').click(tbIndent);
		$('#h5e-toolbar-outdent').click(function() {
			//exit if button is disabled
			if ($(this).hasClass("h5e-disabled")) return;
			tbOutdent();
		});
		$('#h5e-toolbar-section').click(tbAddSection);
		$('#h5e-toolbar-ref').click(tbAddReference);
		$('#h5e-toolbar-related').click(tbRelatedWikihows);

		$('#h5e-toolbar-publish').click(function() {
			//exit if button is disabled
			if ($(this).hasClass("h5e-disabled")) return;

			focusCurrentSection();
			stopEditing(true);
			return false;
		});

		$('.h5e-toolbar-cancel').click(function() {
			//exit if button is disabled
			if ($(this).hasClass("h5e-disabled")) return;

			window.clearTimeout(mSaveDraftTimer);

			if (settings['create-new-article']) {
				window.location.href = '/Special:CreatePage';
			} else if (isPageDirty()) {
				// force a refresh
				window.location.href = window.location.href;
			} else {
				stopEditing(false);
				slideToolbar('up');
			}
			return false;
		});

		$('#h5e-edit-summary-pre').blur(function() {
			if ($('#h5e-edit-summary-pre').val() == '') {
				$('#h5e-edit-summary-pre')
					.val( wfMsg('h5e-enter-edit-summary') )
					.addClass('h5e-example-text');
			}
		});
		$('#h5e-edit-summary-pre').focus(function() {
			if ($('#h5e-edit-summary-pre').val() == wfMsg('h5e-enter-edit-summary')) {
				$('#h5e-edit-summary-pre')
					.removeClass('h5e-example-text')
					.val('');
			}
		});
		$('#h5e-edit-summary-pre').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' pressed
				$('#h5e-toolbar-publish').click();
				return false;
			}
		});

		$('#h5e-edit-summary-post').blur(function() {
			if ($('#h5e-edit-summary-post').val() == '') {
				$('#h5e-edit-summary-post')
					.val( wfMsg('h5e-edit-summary-examples') )
					.addClass('h5e-example-text');
			}
		});
		$('#h5e-edit-summary-post').focus(function() {
			if ($('#h5e-edit-summary-post').val() == wfMsg('h5e-edit-summary-examples')) {
				$('#h5e-edit-summary-post')
					.removeClass('h5e-example-text')
					.val('');
			}
		});

		$('#h5e-edit-summary-save').click(function() {
			var postSaveSummaryFunc = function() {
				slideToolbar('up', function() {
					$('#h5e-edit-summary-pre')
						.add('#h5e-edit-summary-post')
						.val('');
				});
			};

			var editSummary = getEditSummary('#h5e-edit-summary-post');
			if (editSummary)
			{
				$.post('/Special:Html5editor',
					{ eaction: 'save-summary',
					  target: wgPageName,
					  summary: editSummary
					},
					postSaveSummaryFunc
				);
			} else {
				postSaveSummaryFunc();
			}
		});
		$('#h5e-edit-summary-post').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' pressed
				$('#h5e-edit-summary-save').click();
				return false;
			}
		});
	}

	/**
	 * Store the last image div we hovered over, to be used by the
	 * imageHoverIn() and imageHoverOut() methods.
	 */
	var mouseHoverDiv = null;

	/**
	 * Called when we mouse over an image in editing mode.
	 */
	function imageHoverIn() {
		// Unbind this event since our mouse will be over the new opaque
		// div instead of the img one (causes flickering otherwise)
		$(this).unbind('mouseenter');
		mouseHoverDiv = this;

		// Add the opaque div over top of the image our mouse is over
		var h = $(this).outerHeight();
		var w = $(this).outerWidth();
		var offset = $(this).offset();

		// note: Chrome doesn't like the jquery offset() setter (the offsets
		// get mangled), so we set top and left using css instead
		$('#h5e-mwimg-mouseover')
			.css({
				'top': Math.round(offset['top']) + 'px',
				'left': Math.round(offset['left']) + 'px'
			})
			.show()
			.height(h)
			.width(w)
			.mouseleave(imageHoverOut);

		$('#h5e-mwimg-mouseover div')
			.addClass('h5e-img-mouseover')
			.show()
			.height(h)
			.width(w);

		// Bind to the "Remove Image" link or icon in the opaque div
		$('#h5e-mwimg-mouseover a')
			.css('margin-top', (h/3)*-2)
			.css('margin-left', (w-87)/2)
			.show()
			.click(showRemoveImageConfirm);

		// Bind to the remove confirmation div
		$('#h5e-mwimg-mouseover-confirm')
			.css('margin-top', (h/3)*-2)
			.css('margin-left', (w-178)/2)
			.hide();
	}

	/**
	 * Called when a mouse is no longer over the image or opaque div that's
	 * over top the image.
	 */
	function imageHoverOut() {
		$(this).unbind('mouseleave');
		$(mouseHoverDiv).mouseenter(imageHoverIn);
		$('#h5e-mwimg-mouseover').fadeOut('fast');
	}

	/**
	 * Show "remove image" confirmation: YES | NO
	 */
	function showRemoveImageConfirm() {
		$('#h5e-mwimg-mouseover a').fadeOut('fast');
		$('#h5e-mwimg-mouseover-confirm').fadeIn('fast');
		return false;
	}

	/**
	 * Show remove image link instead of confirmation
	 */
	function showRemoveImageLink() {
		$('#h5e-mwimg-mouseover-confirm').fadeOut('fast');
		$('#h5e-mwimg-mouseover a').fadeIn('fast');
		return false;
	}

	/**
	 * Remove an image from the DOM
	 */
	function removeImage() {
		setPageDirty();

		var rmDiv = $(mouseHoverDiv);
		var chain = rmDiv.parentsUntil('.mwimg');
		// make sure there is actually a parent with class mwimg
		if (chain.length < 3) {
			rmDiv = chain.last().parent();
		}
		rmDiv.remove();
		$('#h5e-mwimg-mouseover').css('display', 'none');
		return false;
	}

	/**
	 * Remove image editing listeners
	 */
	function removeImageHoverListeners() {
		$('.rounders').unbind('mouseenter');
	}

	function clearAutocompleteResults() {
		$('#completeDiv').remove();
		$('#completionFrame').remove();
	}

	function loadRelatedWikihows() {
		var related = [];
		var selection = $('#relatedwikihows ul li a');
		if (selection.length == 0) {
			selection = $('#relatedwikihows ul li');
		}
		selection.each(function() {
			var title = $(this).data('title');
			if (!title) {
				var title = $(this).attr('title');
			}
			related.push(title);
		});
		return related;
	}

	function saveRelatedWikihowsActive(articles) {
		// Clear out whatever is currently there
		$('#relatedwikihows ul li').remove();
		$('#relatedwikihows .h5e-rel-wh-edit').remove();

		var ul = $('#relatedwikihows ul');
		$(articles).each(function() {
			var title = this;
			var howto = wfMsg('howto', title);
			var href = getLinkFromArticle(title);
			var tmpl = '<li><a title="$1" href="$2">$3</a></li>';
			var html = wfTemplate(tmpl, title.replace(/"/g, '&quot;'), href, howto);
			ul.append( $(html) );
		});
	}

	function saveRelatedWikihowsInactive(articles) {
		// Clear out whatever is currently there
		$('#relatedwikihows ul li').remove();
		$('#relatedwikihows .h5e-rel-wh-edit').remove();

		var ul = $('#relatedwikihows ul');
		$(articles).each(function() {
			var title = this;
			var howto = wfMsg('howto', title);
			var tmpl = '<li><span class="h5e-rel-wh-disabled">$1</span></li>';
			var html = wfTemplate(tmpl, howto);
			var node = $(html);
			node.data('title', title);
			ul.append( node );
		});

		var tmpl_A = '<div class="h5e-rel-wh-edit"><input type="button" class="h5e-button button64 h5e-input-button" value="$1" /></div>';
		var tmpl_B = '<div class="h5e-rel-wh-edit h5e-rel-wh-add"><input type="button" class="h5e-button button64 h5e-input-button" value="$1" /></div>';
		var tmpl = articles.length > 0 ? tmpl_A : tmpl_B;
		var msg = articles.length > 0 ? wfMsg('h5e-rel-wh-edit') : wfMsg('h5e-rel-wh-add');
		var html = wfTemplate(tmpl, msg);
		ul.before(html);

		$('.h5e-rel-wh-edit input').click(function() {
			tbRelatedWikihows();
			return false;
		});
	}

	var saveLink = false;

	/**
	 * Show edit/add link dialog
	 */
	function showEditLinkDialog(action, linkText, href, onSaveFunc) {
		saveLink = false;
		var pos = saveCursorPos();

		$('#h5e-link-text').val(linkText);
		var article = getArticleFromLink(href);
		$('#h5e-link-article').val(article);

		var isExternal = article.match(/^http:\/\//);
		$('#h5e-link-article').attr('disabled', isExternal);
		var showHide = isExternal ? 'inline' : 'none';
		var msg = isExternal ? wfMsg('h5e-external-link-editing-disabled') : '';
		$('.h5e-external-link-editing-disabled span')
			.css('display', showHide)
			.html(msg);

		if (action == 'change' && !isExternal) {
			var title = wfMsg('h5e-edit-link');
		} else if (action == 'change' && isExternal) {
			var title = wfMsg('h5e-edit-link-external');
		} else if (action == 'add') {
			var title = wfMsg('h5e-add-link');
		}

		$('#h5e-link-dialog').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			zIndex: DIALOG_ZINDEX,
			open: function() {
				if (!isExternal) {
					// remove any previous completedivs
					clearAutocompleteResults();

					// google-style auto-complete for links
					InstallAC(
						document['h5e-ac-link'],
						document['h5e-ac-link']['h5e-link-article'],
						"",
						"/Special:TitleSearch?lim=10",
						"en");

					// customize ac results
					$('#completeDiv')
						.addClass('h5e-auto-complete');
				}

				$('#h5e-link-dialog').dialog('option', 'title', title);
			},
			close: function(evt, ui) {
				clearAutocompleteResults();

				focusCurrentSection(pos['node']);
				loadCursorPos(pos, function() {
					if (saveLink) {
						var text = $('#h5e-link-text').val();
						var article = $('#h5e-link-article').val();
						var link = getLinkFromArticle(article);

						if (onSaveFunc) {
							var func = function() {
								onSaveFunc(text, link);
							};
							if (settings['needs-delay-after-dialog']) {
								window.setTimeout(func, 0);
							} else {
								func();
							}
						}
					}
				});
			}
		});

		if (linkText == '' || isExternal) {
			$('#h5e-link-text').focus();
		} else {
			$('#h5e-link-article').focus();
		}

	}

	/**
	 * Given a DOM node, returns the href of it if it's an <a> tag or has any
	 * immediate parents that are an <a> tag.
	 *
	 * @return the href value or '' if none exists
	 */
	function getAnchorNode(node) {
		var anchor = node;
		if (!node.is('a')) {
			anchor = node.parents('a').last();
		}
		if (anchor.length && !anchor.hasClass('h5e-no-edit-tooltip')) {
			return anchor;
		} else {
			return null;
		}
	}

	var tooltipCurrentLinkNode;
	// Show change link bar when key or mouse onto a link
	var tooltipOldAnchorEditLink = '';

	/**
	 * Called in edit mode when you move to or click on a link, to show the
	 * link tooltip that allows editing.
	 */
	function tooltipLinkListener() {
		var startNode = $(getCursorNode());
		tooltipCurrentLinkNode = getAnchorNode(startNode);
		var newShowLink = tooltipCurrentLinkNode ? tooltipCurrentLinkNode.attr('href') : '';

		// check to see if tooltipOldAnchorEditLink has changed, modify the
		// hrefs and css only if it has
		if (newShowLink !== tooltipOldAnchorEditLink) {
			tooltipOldAnchorEditLink = newShowLink;
			var editLink = $('.h5e-edit-link-options-over');
			if (newShowLink) {
				editLink
					.css({
						top: startNode.offset().top - editLink.height() - 25,
						left: startNode.offset().left
					})
					.show()
					.data('node', tooltipCurrentLinkNode);
				var href = newShowLink;
				var article = getArticleFromLink(href);
				var linkDisplay = $('#h5e-editlink-display');
				linkDisplay.text( getArticleDisplay(article) );
				linkDisplay.attr('title', article);
				linkDisplay.attr('target', '_blank');
				linkDisplay.attr('href', href);
				var innerWidth = $('.h5e-edit-link-inner').width() + 31;
				editLink.width(innerWidth + 5);
			} else {
				editLink.fadeOut('fast');
			}
		}
	}

	/**
	 * Add all the listeners to buttons in our editing dialogs.
	 */
	function dialogClickListeners() {
		$('.h5e-edit-link-options-over').hide();

		// Edit link pop-in, called when a user changes a link in the text
		// that's selected
		$('#h5e-editlink-change').click(function() {
			tooltipOldAnchorEditLink = '';
			var editLink = $('.h5e-edit-link-options-over');
			editLink.hide();

			var href = tooltipCurrentLinkNode.attr('href');
			var text = tooltipCurrentLinkNode.text();
			showEditLinkDialog('change', text, href,
				function(text, link) { // when 'Change' button is clicked
					// replace current link and text
					tooltipCurrentLinkNode.attr('href', link);
					tooltipCurrentLinkNode.attr('title', getArticleFromLink(text));
					var startNode = editLink.data('node');
					startNode.text(text);
				}
			);
			return false;
		});

		// Edit link pop-in, when the user chooses to remove the link
		// from some text
		$('#h5e-editlink-remove').click(function() {
			setPageDirty();

			var text = tooltipCurrentLinkNode.text();
			tooltipCurrentLinkNode.replaceWith('<span>'+text+'</span>');

			tooltipOldAnchorEditLink = '';
			$('.h5e-edit-link-options-over').fadeOut('fast');
			return false;
		});

		// Edit link pop-in, when the user clicks cancel (to close pop-in)
		$('#h5e-editlink-cancel').click(function() {
			tooltipOldAnchorEditLink = '';
			$('.h5e-edit-link-options-over').fadeOut('fast');
			return false;
		});

		// Change link dialog, when user clicks Change button
		$('#h5e-link-change').click(function() {
			setPageDirty();

			$('#h5e-link-article').removeClass('h5e-url-warning');
			var article = $.trim( $('#h5e-link-article').val() );
			if (!$('#h5e-link-article').attr('disabled') &&
				article.indexOf('/') >= 0 && article.indexOf(' ') < 0)
			{
				$('#h5e-link-article')
					.addClass('h5e-url-warning')
					.focus();

				var msg = wfMsg('h5e-external-links-warning');
				$('.h5e-external-link-editing-disabled span')
					.css('display', 'inline')
					.html(msg);

				return false;
			} else {
				saveLink = true;
				$('#h5e-link-dialog').dialog('close');
			}
		});

		// Change link dialog, when user clicks Cancel button
		$('#h5e-link-cancel').click(function() {
			$('#h5e-link-dialog').dialog('close');
			return false;
		});

		$('#h5e-link-preview').click(function() {
			var article = $('#h5e-link-article').val();
			var link = getLinkFromArticle(article);
			$('#h5e-link-preview').attr('href', link);
		});

		$('#h5e-link-article').keypress(function(evt) {
			$('#h5e-link-article').removeClass('h5e-url-warning');
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-link-change').click();
				return false;
			}
		});

		$('.h5e-link-external-help a').click(function() {

			$('#h5e-external-url-msg-dialog').dialog({
				width: 250,
				minWidth: 250,
				zIndex: DIALOG_ZINDEX,
				modal: true
			});

			return false;
		});

		$('#h5e-external-url-msg-dialog input').click(function() {
			$('#h5e-external-url-msg-dialog').dialog('close');
			return false;
		});

		// Section dialog, user clicks change
		$('#h5e-sections-change').click(function () {
			setPageDirty();
			var sectionsDiff = getSectionsToAddOrRemove();
			var firstNewSection = sectionsDiff['first'];
			addOrRemoveSections(sectionsDiff['add'], sectionsDiff['remove']);
			deleteUnselectedAlternateMethods();
			if (firstNewSection) {
				focusSection(firstNewSection);
			}
			$('#h5e-sections-dialog').dialog('close');
		});

		// Section dialog, user clicks cancel
		$('#h5e-sections-cancel').click(function() {
			$('#h5e-sections-dialog').dialog('close');
			return false;
		});

		// Section dialog -> Alternate method add, user clicks add
		$('#h5e-am-add').click(function() {
			addAlternateMethod($('#h5e-am-name').val());
			drawAlternateMethodsHTML();
			$('#h5e-am-dialog').dialog('close');
			return false;
		});

		$('#h5e-am-name').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-am-add').click();
				return false;
			}
		});

		// Section dialog -> Alternate method add, user clicks cancel
		$('#h5e-am-cancel').click(function() {
			$('#h5e-am-dialog').dialog('close');
			return false;
		});

		// Edit related wikihows dialog, Done button
		$('#h5e-related-done').click(function() {
			setPageDirty();

			// save links to related wikihows section after dialog Done
			var related = [];
			$('.h5e-related-sortable li').each(function() {
				var title = $(this).data('title');
				related.push(title);
			});
			saveRelatedWikihowsInactive(related);
			$('#related-wh-dialog').dialog('close');
			return false;
		});

		$('#h5e-related-cancel').click(function() {
			$('#related-wh-dialog').dialog('close');
			return false;
		});

		$('#h5e-related-add').click(function() {
			var title = $('#h5e-related-new').val();
			title = $.trim(title);
			if (title != '') {
				var node = createRelatedWikihowSortableNode(title);
				$('.h5e-related-sortable').append(node);
			}
			$('#h5e-related-new')
				.val('')
				.focus();
			return false;
		});

		$('#h5e-related-new').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-related-add').click();
				return false;
			}
		});

		$('.related-wh-overlay-edit button').click(function() {
			tbRelatedWikihows();
			return false;
		});

		$('.h5e-mwimg-confirm-no').click(function() {
			showRemoveImageLink();
			return false;
		});

		$('.h5e-mwimg-confirm-yes').click(function() {
			removeImage();
			return false;
		});

	}

	/**
	 * Add click handlers to the DOM relating to HTML5 editing.
	 */
	function attachClickListeners() {
		stopEditing(false);

		tbClickListeners();
		dialogClickListeners();
	}

	function attachEditListeners() {
		$('.edit_article_button')
			.unbind('click')
			.fadeIn()
			.click(function() {
				startEditing('intro');
				return false;
			});

		$('.editsectionbutton')
			.css('opacity', '1') // hack to make chrome fade work for all cases
			.unbind('click')
			.fadeIn()
			.click(function() {
				var id = $(this).parent().next().attr('id');
				startEditing(id, function() {
					if (id == 'relatedwikihows') {
						tbRelatedWikihows();
					}
				});
				return false;
			});

		$('#tab_edit')
			.unbind('click')
			.removeClass('on')
			.click(function () {
				startEditing('intro');
				return false;
			});
		$('#tab_article').addClass('on');
	}

	function hideEditListeners(callback) {
		$('.edit_article_button').fadeOut(400, function() {
			if (callback) {
				try {
					callback();
				} catch(e) {
					console.log('caught infinite error: ', e);
				}
				return false;
			}
		});
		$('.editsectionbutton').fadeOut();

		$('#tab_edit')
			.unbind('click')
			.addClass('on')
			.attr('style', '')
			.click(function() {
				return false;
			});
		$('#tab_article').removeClass('on');
	}

	/**
	 * Add hover and mousedown handlers to the DOM relating to HTML5 editing.
	 */
	function addButtonListeners() {
		$('.h5e-button').click(
			function (event) {
				event.preventDefault();
			}
		);
	}

	/**
	 * Check if the browser can handle html5 editing on wikihow.
	 */
	function isHtml5EditingCompatible() {
		// We determine this by browser rather than by feature because we use
		// so many features that this is easier.
		var webkit = isHtml5EditWebkitReady();
		var firefox = isHtml5EditFirefoxReady();
		return webkit || firefox;
	}

	/**
	 * Check if the browser is WebKit and can handle html5 editing on wikihow.
	 */
	function isHtml5EditWebkitReady() {
		var webkit = typeof $.browser['webkit'] != 'undefined' && $.browser['webkit'] && parseInt($.browser['version'], 10) >= 500; // Safari or Chrome
		return webkit;
	}

	/**
	 * Check if the browser is Firefox and can handle html5 editing on wikihow.
	 */
	function isHtml5EditFirefoxReady() {
		var firefox = false;
		if (typeof $.browser['mozilla'] != 'undefined' && $.browser['mozilla']) {
			var m = $.browser['version'].match(/^([^A-Za-z]*)/); // should be 1.9 or higher
			if (m && m[0]) {
				var ver = parseInt(m[0].replace(/\./, ''), 10);
				firefox = ver >= 19; // FF3 or better
			}
		}
		return firefox;
	}

	/**
	 * The settings returned by getEditSettingsForBrowser()
	 */
	var settings = {};

	/**
	 * Determine the browser settings/configuration that will customize how
	 * certain actions are performed in Javascript.
	 */
	function getEditSettingsForBrowser() {
		var config = {};
		var isWebkit = isHtml5EditWebkitReady();
		var isFirefox = isHtml5EditFirefoxReady();

		// Firefox needs an article section focus before changing the cursor
		// position.  Webkit doesn't.
		config['needs-section-focus'] = isFirefox;

		// Webkit browsers deal with the dialogclose event inconsistently,
		// based on the event source.  This timeout is needed if the
		// dialog was closed by pressing Esc or hitting an <input> element.
		config['needs-delay-after-dialog'] = isWebkit;

		var createNewArticle = !!document.location.href.match(/[?&]create-new-article=true/);
		config['create-new-article'] = !wgArticleExists && createNewArticle;

		var startEditing = !!document.location.href.match(/[?&]h5e=true/);
		config['start-editing'] = startEditing;

		// Chrome on Mac doesn't see Paste or Backspace events with .keypress,
		// so this is necessary
		config['monitor-keyup-events'] = isWebkit;

		// Chrome on Mac doesn't allow you to position the cursor in an empty
		// <b> tag.  FF doesn't do well with an extra space here or there.
		config['non-empty-steps'] = isWebkit;

		return config;
	}

	/**
	 * Look at the wgRestrictionEdit and wgUserGroups variables to
	 * figure out whether we can edit this article.
	 */
	function isArticleEditAllowed() {
		var groups = {};
		var editAllowed = wgRestrictionEdit.length == 0;
		$(wgUserGroups).each(function () {
			groups[this] = true;
		});
		$(wgRestrictionEdit).each(function () {
			if (groups[this]) editAllowed = true;
		});
		return editAllowed;
	}

	// returns the public interface singleton
	return {

		/**
		 * Initialize the html5 editor.  Called when page is loaded.
		 * startEditing() is the method called when editing actually starts.
		 *
		 * @public
		 */
		init: function() {
			// kill the temporary listener added in edit-bootstrap.tmpl.php

			// do we have to load a draft? 
			var isdraft = !!window.location.search.match(/draft=[0-9]+/);

			$('.editsectionbutton, .edit_article_button, #tab_edit')
				.unbind('click');

			var isArticle = wgIsArticle 
				&& wgNamespaceNumber == 0
				&& ($('.noarticletext').length == 0 || isdraft > 0)
			;

			if (isHtml5EditingCompatible()
				&& isArticle
				&& !wgForceAdvancedEditor
				&& isArticleEditAllowed())
			{

				settings = getEditSettingsForBrowser();
				makeStepNumbersNonEditable();

				attachClickListeners();
				attachEditListeners();

				addButtonListeners();

				var autoEditNow = settings['create-new-article'] || settings['start-editing'];

				if (whH5EClickedEditButton) {
					var button = whH5EClickedEditButton;
					whH5EClickedEditButton = null;
					if (!autoEditNow) {
						$(button).click();
					}
				}

				if (autoEditNow) {
					hideEditListeners(function () {
						startEditing('intro');
					});
				}
			} else {
				// insert an ad for firefox or chrome here
				$('.editsectionbutton, .edit_article_button, #tab_edit')
					.click(function () {
						var link = $(this).attr('href');
						window.location.href = link;
					});
			}
		}

	};


})(jQuery); // exec anonymous function and return resulting class

// HTML5 init is run on the DOM ready event
jQuery(WH.h5e.init);

