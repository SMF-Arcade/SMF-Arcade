<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6
 * @license http://download.smfarcade.info/license.php New-BSD
 */


/**
 *
 */
abstract class Arcade_Game
{
	/**
	 *
	 * @var array
	 */
	protected $game;
	
	/**
	 *
	 */
	public function __construct(array $game, $session)
	{
		$this->game = $game;
		
		if (!empty($session))
			$this->setSession($session);
	}

	/**
	 * 
	 */
	abstract public function setSession(array $session_data);

	/**
	 * 
	 */
	abstract public function getSession();
	
	/**
	 * Prepares game window
	 */
	abstract public function Prepare();
	
	/**
	 *
	 */
	public function _renderTop()
	{
		echo '
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div id="gamearea">';
	}
	
	/**
	 *
	 */
	public function _renderBottom()
	{
		echo '
			</div>
			<span class="botslice"><span></span></span>
		</div>';
	}
	
	/**
	 * Renders game window
	 */
	abstract public function Render();
}

?>