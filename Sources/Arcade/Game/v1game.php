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
class Arcade_Game_v1game extends Arcade_Game_Flash
{
	/**
	 *
	 */
	protected $score = 0;
	
	/**
	 *
	 */
	protected $start_time = 0;

	/**
	 *
	 */
	protected $end_time = 0;
	
	/**
	 *
	 */
	protected $is_done = false;
	
	/**
	 *
	 */
	public function Prepare()
	{
		if (empty($this->start_time))
			$this->newSession();
		
		/// ???
	}
	
	/**
	 *
	 */
	public function getSession()
	{
		return array(
			//'game' => $this->game,
			'start_time' => $this->start_time,
			'end_time' => $this->end_time,
			'score' => $this->score,
			'done' => $this->is_done,
		);
	}
	
	/**
	 *
	 */
	public function setSession(array $session_data)
	{
	}
}