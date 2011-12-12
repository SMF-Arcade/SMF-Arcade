function SMFArcade_game(gameID)
{
	this.registerCallback = registerCallback;
	
	var onSessionStartCallback = false;
	
	function registerCallback(callbackType, callbackFunction)
	{
		if (callbackType == 'session_start')
			onSessionStartCallback = callbackFunction;
	}
	
	
}