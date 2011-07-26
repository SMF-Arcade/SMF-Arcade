<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*

*/

function ArcadeSilverScore()
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

	file_put_contents('./debug/' . sha1(serialize($_REQUEST)) . '.txt', print_r(array($_REQUEST, $_SESSION['arcade']['v2_play']), true));

	ArcadeXMLOutput(array(
		'test' => 'ok',
	));

	obExit(false);
}

function ArcadeSilverPlay(&$game, &$session)
{
	global $scripturl, $txt, $db_prefix, $context, $sourcedir, $modSettings, $smcFunc;

}

function ArcadeSilverHtml(&$game, $auto_start = true)
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<script type="text/javascript">
        function onSilverlightError(sender, args) {

            var appSource = "";
            if (sender != null && sender != 0) {
                appSource = sender.getHost().Source;
            }
            var errorType = args.ErrorType;
            var iErrorCode = args.ErrorCode;

            var errMsg = "Unhandled Error in Silverlight 2 Application " +  appSource + "\n" ;

            errMsg += "Code: "+ iErrorCode + "    \n";
            errMsg += "Category: " + errorType + "       \n";
            errMsg += "Message: " + args.ErrorMessage + "     \n";

            if (errorType == "ParserError")
            {
                errMsg += "File: " + args.xamlFile + "     \n";
                errMsg += "Line: " + args.lineNumber + "     \n";
                errMsg += "Position: " + args.charPosition + "     \n";
            }
            else if (errorType == "RuntimeError")
            {
                if (args.lineNumber != 0)
                {
                    errMsg += "Line: " + args.lineNumber + "     \n";
                    errMsg += "Position: " +  args.charPosition + "     \n";
                }
                errMsg += "MethodName: " + args.methodName + "     \n";
            }

            throw new Error(errMsg);
        }
    </script>
	<div id="silverlightControlHost">
		<object data="data:application/x-silverlight," type="application/x-silverlight-2-b2" width="100%" height="100%">
			<param name="source" value="', $game['url']['flash'], '"/>
			<param name="onerror" value="onSilverlightError" />
			<param name="background" value="white" />
			<param name="score_uri" value="', $scripturl, '" />

			<a href="http://go.microsoft.com/fwlink/?LinkID=115261" style="text-decoration: none;">
     			<img src="http://go.microsoft.com/fwlink/?LinkId=108181" alt="Get Microsoft Silverlight" style="border-style: none"/>
			</a>
		</object>
	</div>';
}

?>