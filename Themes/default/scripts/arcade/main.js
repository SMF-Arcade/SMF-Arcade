// SMF Arcade 2.6 Alpha; arcade.js
// Handles ajax calls needed by arcade

var ajax_wait = false;
var ajaxCallBack;

function arcadeAjaxSend(url, values, callback)
{
	// Wait a second
	if (ajax_wait)
	{
		setTimeout(arcadeAjaxSend(url, values, callback), 1000);

		return;
	}

	ajax_indicator(true);
	ajaxCallBack = callback;
	ajax_wait = true;

	sendXMLDocument(url, values, onAjaxDone);
}

function arcadeHtmlDoc(url, values, callback, extra)
{
	if (!window.XMLHttpRequest)
		return false;

	var htmlDoc = new window.XMLHttpRequest();
	if (typeof(callback) != "undefined")
	{
		htmlDoc.onreadystatechange = function ()
		{
			if (htmlDoc.readyState != 4)
				return;

			if (htmlDoc.responseText != null && htmlDoc.status == 200)
				callback(htmlDoc.responseText, extra);
			else
				callback(false, extra);
		};
	}
	htmlDoc.open('POST', url, true);
	if (typeof(htmlDoc.setRequestHeader) != "undefined")
		htmlDoc.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	htmlDoc.send(values);

	return true;
}

function onAjaxDone(XMLDoc)
{
	ajax_indicator(false);
	ajax_wait = false;

	// Call the callback
	ajaxCallBack(XMLDoc);
}

// Rating
var rate_url = smf_scripturl + "?action=arcade;sa=rate;xml";

function arcade_rate(rating, game)
{
	var post = new Array();

	waiting = true;
	post[0] = "game=" + parseInt(game);
	post[1] = "rate=" + parseInt(rating);

	arcadeAjaxSend(rate_url, post.join("&"), onArcadeRate);
}

function onArcadeRate(XMLDoc)
{
	var rating = XMLDoc.getElementsByTagName("rating")[0].firstChild.nodeValue;
	var i = 0;

	for (i = 1; i <= 5; i++)
	{
		if (i <= rating)
			document.getElementById('imgrate' + i).src = smf_images_url + '/arcade_star.gif';
		else
			document.getElementById('imgrate' + i).src = smf_images_url + '/arcade_star2.gif';
	}
}

// Favorite
var imgfav = '';
var favorite_url = smf_scripturl + "?action=arcade;sa=favorite;xml"

function arcade_favorite(game)
{
	var post = new Array();

	waiting = true;

	post[0] = "game=" + parseInt(game);
	imgfav = 'favgame' + parseInt(game);

	ajax_indicator(true);

	arcadeAjaxSend(favorite_url, post.join("&"), onArcadeFavorite);
}

function onArcadeFavorite(XMLDoc)
{
	if (parseInt(XMLDoc.getElementsByTagName("state")[0].firstChild.nodeValue) == 0)
		document.getElementById(imgfav).src = smf_images_url + '/favorite.gif';

	else
		document.getElementById(imgfav).src = smf_images_url + '/favorite2.gif';
}

// Comment
var editing = false;
var editscore = 0;
var comment_url = smf_scripturl + "?action=arcade;sa=highscore;xml"

function arcadeCommentEdit(score, game, save)
{
	var divComment = "comment" + parseInt(score);
	var divEdit = "edit" + parseInt(score);
	var editLink = "editlink" + parseInt(score);

	editscore = score;

	if (editing || save == 1)
		arcadeCommentSave(score, game);

	editing = true;

	document.getElementById(divComment).style.display = 'none';
	document.getElementById(editLink).style.display = 'none';
	document.getElementById(divEdit).style.display = 'block';
}

function arcadeCommentSave(score, game)
{
	var post = new Array();
	var textbox = "c"  + parseInt(score);
	var comment = document.getElementById(textbox).value;

	post[0] = "game=" + parseInt(game);
	post[1] = "score=" + parseInt(score);
	post[2] = "csave=true";
	post[3] = "new_comment=" + escape((comment.replace(/&#/g, "&#38;#"))).replace(/\+/g, "%2B");

	arcadeAjaxSend(comment_url, post.join("&"), onArcadeCommentSave);
}

function onArcadeCommentSave(XMLDoc)
{
	editing = false;

	var divComment = "comment" + parseInt(editscore);
	var divEdit = "edit" + parseInt(editscore);
	var editLink = "editlink" + parseInt(editscore);

	setInnerHTML(document.getElementById(divComment), XMLDoc.getElementsByTagName("comment")[0].firstChild.nodeValue);
	document.getElementById(divComment).style.display = 'block';
	document.getElementById(editLink).style.display = 'inline';
	document.getElementById(divEdit).style.display = 'none';
}

// Search
function gameSuggest(sessionID, textID)
{
	var lastSearch = "", lastDirtySearch = "";
	var textHandle = document.getElementById(textID);
	var suggestDivHandle = document.getElementById('suggest_' + textID);
	var selectedDiv = false;
	var cache = [];
	var displayData = [];
	var maxDisplayQuantity = 15;
	var positionComplete = false;
	var onAddCallback = false;
	this.registerCallback = registerCallback;

	xmlRequestHandle = null;

	function init()
	{
		if (!window.XMLHttpRequest)
			return false;

		createEventListener(textHandle);
		textHandle.addEventListener('keyup', autoSuggestUpdate, false);
		textHandle.addEventListener('change', autoSuggestUpdate, false);
		textHandle.addEventListener('blur', autoSuggestHide, false);
		textHandle.addEventListener('focus', autoSuggestUpdate, false);

		return true;
	}

	function registerCallback(callbackType, callbackFunction)
	{
		if (callbackType == 'add')
			onAddCallback = callbackFunction;
	}

	function postitionDiv()
	{
		// Only do it once.
		if (positionComplete)
			return true;

		positionComplete = true;

		// Put the div under the text box.
		var parentPos = smf_itemPos(textHandle);

		suggestDivHandle.style.left = parentPos[0] + 'px';
		suggestDivHandle.style.top = (parentPos[1] + textHandle.offsetHeight) + 'px';
		suggestDivHandle.style.width = textHandle.style.width;

		return true;
	}

	function itemClicked(ev)
	{
		if (!ev)
			ev = window.event;

		if (ev.srcElement)
			curElement = ev.srcElement;
		else if (ev.memberid)
			curElement = ev;
		else
			curElement = this;

		if (document.getElementById('suggest_template_' + textID))
		{

		}

		return true;
	}

	function autoSuggestHide()
	{
		// Delay to allow events to propogate through....
		hideTimer = setTimeout(function()
			{
				suggestDivHandle.style.visibility = 'hidden';
			}, 250
		);
	}

	function autoSuggestShow()
	{
		postitionDiv();

		suggestDivHandle.style.visibility = 'visible';
	}

	function populateDiv(results)
	{
		while (suggestDivHandle.childNodes[0])
		{
			suggestDivHandle.removeChild(suggestDivHandle.childNodes[0]);
		}

		if (typeof(results) == 'undefined')
		{
			displayData = [];
			return false;
		}

		var newDisplayData = [];
		for (i = 0; i < (results.length > maxDisplayQuantity ? maxDisplayQuantity : results.length); i++)
		{
			// Create the sub element
			newDivHandle = document.createElement('div');
			newDivHandle.className = 'game_suggest_item';
			newDivHandle.style.width = textHandle.style.width;

			newGamelink = document.createElement('a');
			newGamelink.href = smf_prepareScriptUrl(smf_scripturl) + 'action=arcade;game=' + (results[i]['id']) + '';
			newGamelink.innerHTML = results[i]['name'];

			newDivHandle.appendChild(newGamelink);

			suggestDivHandle.appendChild(newDivHandle);

			newDisplayData[i] = newDivHandle;
		}

		displayData = newDisplayData;

		return true;
	}

	function onSuggestionReceived(oXMLDoc)
	{
		if (xmlRequestHandle.readyState != 4)
			return true;

		var games = oXMLDoc.getElementsByTagName('game');
		cache = [];
		for (var i = 0; i < games.length; i++)
		{
			cache[i] = new Array(2);
			cache[i]['id'] = games[i].getAttribute('id');
			cache[i]['name'] = games[i].childNodes[0].nodeValue;
		}

		populateDiv(cache);

		if (games.length == 0)
			autoSuggestHide();
		else
			autoSuggestShow();

		return true;
	}

	function autoSuggestUpdate()
	{
		if (isEmptyText(textHandle))
		{
			autoSuggestHide();

			return true;
		}

		if (textHandle.value == lastDirtySearch)
			return true;
		lastDirtySearch = textHandle.value;

		var searchString = textHandle.value;

		realLastSearch = lastSearch;
		lastSearch = searchString;

		if (searchString == "" || searchString.length < 3)
			return true;
		else if (searchString.substr(0, realLastSearch.length) == realLastSearch)
		{
			// Instead of hitting the server again, just narrow down the results...
			var newcache = [], j = 0;
			var lowercaseSearch = searchString.toLowerCase();
			for (var k = 0; k < cache.length; k++)
			{
				if (cache[k]['name'].substr(0, searchString.length).toLowerCase() == lowercaseSearch)
				{
					newcache[j++] = cache[k];
				}
			}

			cache = [];
			if (newcache.length != 0)
			{
				cache = newcache;
				// Repopulate.
				populateDiv(cache);

				// Check it can be seen.
				autoSuggestShow();

				return true;
			}
		}

		if (xmlRequestHandle != null && typeof(xmlRequestHandle) == "object")
			xmlRequestHandle.abort();

		searchString = searchString.php_to8bit().php_urlencode();

		// Get the document.
		xmlRequestHandle = getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=arcade;sa=suggest;name=' + searchString + ';xml;time=' + (new Date().getTime()), onSuggestionReceived);

		return true;
	}

	init();
}

// Select Games for Arcade
function gameSelector(sessionID, textID)
{
	var lastSearch = "", lastDirtySearch = "";
	var textHandle = document.getElementById(textID);
	var suggestDivHandle = document.getElementById('suggest_' + textID);
	var selectedDiv = false;

	var cache = [];
	var displayData = [];
	var maxDisplayQuantity = 15;

	var positionComplete = false;
	var onAddCallback = false;
	var doAutoAdd = false;

	var maxRound = 0;
	var hideTimer = false;

	xmlRequestHandle = null;

	this.registerCallback = registerCallback;
	this.deleteItem = deleteAddedItem;
	this.onSubmit = onElementSubmitted;

	function init()
	{
		if (!window.XMLHttpRequest)
			return false;

		createEventListener(textHandle);
		textHandle.addEventListener('keyup', autoSuggestUpdate, false);
		textHandle.addEventListener('change', autoSuggestUpdate, false);
		textHandle.addEventListener('blur', autoSuggestHide, false);
		textHandle.addEventListener('focus', autoSuggestUpdate, false);

		return true;
	}

	function registerCallback(callbackType, callbackFunction)
	{
		if (callbackType == 'add')
			onAddCallback = callbackFunction;
	}

	function onElementSubmitted()
	{
		return_value = true;
		// Do we have something that matches the current text?
		for (i = 0; i < cache.length; i++)
		{
			if (lastSearch.toLowerCase() == cache[i]['name'].toLowerCase().substr(0, lastSearch.length))
			{
				// Exact match?
				if (lastSearch.length == cache[i]['name'].length)
				{
					// This is the one!
					return_value = {'id': cache[i]['id'], 'gamename': cache[i]['name']};
					break;
				}

				// If we have two matches don't find anything.
				if (return_value != true)
					return_value = false;
				return_value = {'id': cache[i]['id'], 'gamename': cache[i]['name']};
			}
		}

		if (return_value == true || return_value == false)
			return return_value;
		else
		{
			addGame(return_value, true);
			return false;
		}
	}

	function postitionDiv()
	{
		// Only do it once.
		if (positionComplete)
			return true;

		positionComplete = true;

		// Put the div under the text box.
		var parentPos = smf_itemPos(textHandle);

		suggestDivHandle.style.left = parentPos[0] + 'px';
		suggestDivHandle.style.top = (parentPos[1] + textHandle.offsetHeight) + 'px';
		suggestDivHandle.style.width = textHandle.style.width;

		return true;
	}

	function itemClicked(ev)
	{
		if (!ev)
			ev = window.event;

		if (ev.srcElement)
			curElement = ev.srcElement;
		else if (ev.memberid)
			curElement = ev;
		else
			curElement = this;

		var curGame = {'id': curElement.gameid, 'gamename': curElement.innerHTML};
		addGame(curGame);

		return true;
	}

	function removeLastSearchString()
	{
		tempText = textHandle.value.toLowerCase();
		tempSearch = lastSearch.toLowerCase();
		startString = tempText.indexOf(tempSearch);

		if (startString != -1)
		{
			while (startString > 0)
			{
				if (tempText.charAt(startString - 1) == '"' || tempText.charAt(startString - 1) == ',' || tempText.charAt(startString - 1) == ' ')
				{
					startString--;
					if (tempText.charAt(startString - 1) == ',')
						break;
				}
				else
					break;
			}

			textHandle.value = textHandle.value.substr(0, startString);
		}
		else
			textHandle.value = '';
	}

	function addGame(curGame, fromSubmit)
	{
		// Is there a div that we are duplicating and populating?
		if (document.getElementById('suggest_template_' + textID))
		{
			curRound = ++maxRound;

			// What will the new element be called?
			newID = 'suggest_template_' + textID + '_' + curRound;
			// Better not exist?
			while (document.getElementById(newID))
			{
				curRound = ++maxRound;
				newID = 'suggest_template_' + textID + '_' + curRound;
			}

			if (!document.getElementById(newID))
			{
				brotherNode = document.getElementById('suggest_template_' + textID);

				newNode = brotherNode.cloneNode(true);
				brotherNode.parentNode.insertBefore(newNode, brotherNode);
				newNode.id = newID;

				// If it supports remove this will be the javascript.
				deleteCode = 'gameSelector' + textID + '.deleteItem(' + curRound + ');';

				// Parse in any variables.
				newNode.innerHTML = newNode.innerHTML.replace(/::GAME_NAME::/g, curGame.gamename).replace(/::ROUND::/g, curRound).replace(/'*(::|%3A%3A)GAME_ID(::|%3A%3A)'*/g, curGame.id).replace(/'*::DELETE_ROUND_URL::'*/g, deleteCode);

				newNode.style.visibility = 'visible';
				newNode.style.display = '';
			}
		}

		removeLastSearchString();

		if (textHandle.value != '' && fromSubmit)
			doAutoAdd = true;
		else
			doAutoAdd = false;

		// Update the fellow..
		autoSuggestUpdate();
	}

	function deleteAddedItem(round)
	{
		// Remove the div if it exists.
		divID = 'suggest_template_' + textID + '_' + round;
		if (document.getElementById(divID))
		{
			nodeRemove = document.getElementById(divID);
			nodeRemove.parentNode.removeChild(nodeRemove);
		}

		return false;
	}

	function autoSuggestHide()
	{
		// Delay to allow events to propogate through....
		hideTimer = setTimeout(function()
			{
				suggestDivHandle.style.visibility = 'hidden';
			}, 250
		);
	}

	function autoSuggestShow()
	{
		if (hideTimer)
		{
			clearTimeout(hideTimer);
			hideTimer = false;
		}

		postitionDiv();

		suggestDivHandle.style.visibility = 'visible';
	}

	function populateDiv(results)
	{
		while (suggestDivHandle.childNodes[0])
		{
			suggestDivHandle.removeChild(suggestDivHandle.childNodes[0]);
		}

		if (typeof(results) == 'undefined')
		{
			displayData = [];
			return false;
		}

		var newDisplayData = [];
		for (i = 0; i < (results.length > maxDisplayQuantity ? maxDisplayQuantity : results.length); i++)
		{
			// Create the sub element
			newDivHandle = document.createElement('div');
			newDivHandle.className = 'game_suggest_item';
			newDivHandle.style.width = textHandle.style.width;

			createEventListener(newDivHandle);
			newDivHandle.addEventListener('click', itemClicked, false);
			newDivHandle.gameid = results[i]['id'];
			newDivHandle.innerHTML = results[i]['name'];

			suggestDivHandle.appendChild(newDivHandle);

			newDisplayData[i] = newDivHandle;
		}

		displayData = newDisplayData;

		return true;
	}

	function onSuggestionReceived(oXMLDoc)
	{
		if (xmlRequestHandle.readyState != 4)
			return true;

		var games = oXMLDoc.getElementsByTagName('game');
		cache = [];
		for (var i = 0; i < games.length; i++)
		{
			cache[i] = new Array(2);
			cache[i]['id'] = games[i].getAttribute('id');
			cache[i]['name'] = games[i].childNodes[0].nodeValue;
		}

		populateDiv(cache);

		if (games.length == 0)
			autoSuggestHide();
		else
			autoSuggestShow();

		return true;
	}

	function autoSuggestUpdate()
	{
		if (isEmptyText(textHandle))
		{
			autoSuggestHide();

			return true;
		}

		if (textHandle.value == lastDirtySearch)
			return true;
		lastDirtySearch = textHandle.value;

		var searchString = textHandle.value;

		realLastSearch = lastSearch;
		lastSearch = searchString;

		if (searchString == "" || searchString.length < 3)
			return true;
		else if (searchString.substr(0, realLastSearch.length) == realLastSearch)
		{
			// Instead of hitting the server again, just narrow down the results...
			var newcache = [], j = 0;
			var lowercaseSearch = searchString.toLowerCase();
			for (var k = 0; k < cache.length; k++)
			{
				if (cache[k]['name'].substr(0, searchString.length).toLowerCase() == lowercaseSearch)
				{
					newcache[j++] = cache[k];
				}
			}

			cache = [];
			if (newcache.length != 0)
			{
				cache = newcache;
				// Repopulate.
				populateDiv(cache);

				// Check it can be seen.
				autoSuggestShow();

				return true;
			}
		}

		if (xmlRequestHandle != null && typeof(xmlRequestHandle) == "object")
			xmlRequestHandle.abort();

		searchString = searchString.php_to8bit().php_urlencode();

		// Get the document.
		xmlRequestHandle = getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=arcade;sa=suggest;name=' + searchString + ';xml;textid=' + textID + ';time=' + (new Date().getTime()), onSuggestionReceived);

		return true;
	}

	init();
}

// Floating info box
function arcadeBox(text)
{
	setInnerHTML(document.getElementById('arcadebox_html'), text);

	if (document.getElementById('arcadebox').style.display == 'none')
		document.getElementById('arcadebox').style.display = 'block';
	else
		document.getElementById('arcadebox').style.display = 'none';
}

function arcadeBoxMove(evt)
{
	document.getElementById('arcadebox').style.top = evt.clientY + 30 + 'px';
	document.getElementById('arcadebox').style.left = evt.clientX  + 20 + 'px';
}

// Quick action change
function QactionChange()
{
	document.getElementById('qcategory').style.display = 'none';
	document.getElementById('qset').style.display = 'none';

	if (document.getElementById('qaction').value == 'change')
	{
		document.getElementById('qcategory').style.display = '';
		document.getElementById('qset').style.display = '';
	}
	else if (document.getElementById('qaction').value == 'clear_scores')
	{
		document.getElementById('qset').style.display = '';
	}
	else
	{

	}
}