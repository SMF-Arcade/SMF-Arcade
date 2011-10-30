<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

/**
 *
 */
class Arcade_Game_Minesweeper
{
	private $id;
	private $baseurl;
	private $gameurl;

	// Random ID
	private $random_id;

	// Board
	private $board;
	private $board_size = array(
		'x' => 7, // starts form 0
		'y' => 7,
		'mines' => 10,
	);
	private $mines_placed;

	// Score
	private $score = 0;
	private $clickScore = 0;
	private $bonus = 0;
	private $scoring = array();

	//
	private $difficulty = 1;
	private $modes = array(
		array(
			'board' => array(
				'x' => 7, // starts form 0
				'y' => 7,
				'mines' => 10,
			),
			'scoring' => array(
				'base' => 1,
				'reveal' => 5,
				'bonus_time' => 600,
				'complete_bonus' => 280,
			)
		),
		array(
			'board' => array(
				'x' => 15, // starts form 0
				'y' => 15,
				'mines' => 40,
			),
			'scoring' => array(
				'base' => 6,
				'reveal' => 6,
				'bonus_time' => 1200,
				'complete_bonus' => 1000,
			)
		),
		array(
			'board' => array(
				'x' => 29, // starts form 0
				'y' => 15,
				'mines' => 99,
			),
			'scoring' => array(
				'base' => 7,
				'reveal' => 7,
				'bonus_time' => 1800,
				'complete_bonus' => 2400,
			)
		),
	);

	// Status
	private $dead = false;
	private $complete = false;
	private $click_time = 0;
	private $start_time = 0;
	private $end_time = 0;
	private $flags = 0;

	// Constructor
	function minesweeper($id, $baseurl, $gameurl)
	{
		$this->id = $id;
		$this->baseurl = $baseurl;
		$this->gameurl = $gameurl;
	}

	function main()
	{
		if (empty($this->board))
			$this->newSession();
		elseif (!$this->dead && isset($_REQUEST['x']) && isset($_REQUEST['y']) && isset($this->board[$_REQUEST['y']][$_REQUEST['x']]))
		{
			if (!isset($_REQUEST['flag']) && !$this->board[$_REQUEST['y']][$_REQUEST['x']]['open'])
				$this->openSpot($_REQUEST['x'], $_REQUEST['y']);
			else
				$this->flagSpot($_REQUEST['x'], $_REQUEST['y']);
		}

		if (!$this->complete && !empty($this->board))
		{
			$this->complete = true;
			for ($y = 0; $y <= $this->board_size['y']; $y++)
			{
				for ($x = 0; $x <= $this->board_size['x']; $x++)
				{
					if (!$this->board[$y][$x]['is_mine'] && !$this->board[$y][$x]['open'])
					{
						$this->complete = false;
						break 2;
					}
				}
			}

			if ($this->complete)
			{
				$this->score += $this->scoring['complate_bonus'];
			}
		}

		if (($this->dead || $this->complete) && isset($_REQUEST['save']))
			ArcadeSubmit();
	}
	
	function showAjax()
	{
		$this->showScript();
	}

	function showScript()
	{
		global $context, $txt;

		$marked = 0;

		foreach ($this->board as $y => &$row)
		{
			foreach ($row as $x => &$col)
			{
				$image = $this->decideImage($col);

				if ($col['flag'])
					$marked++;

				if (isset($col['prev']) && $col['prev'] == $image)
					continue;
				else
					$col['prev'] = $image;

				echo '
document.getElementById(\'spot', $col['id'], '\').src = \'', $this->baseurl, $image, '\';';
			}
		}

			echo '
document.getElementById(\'minescore\').innerHTML = ', JavaScriptEscape($txt['minesweeper_score'] . ': <b>' . $this->score . '</b> | ' . $txt['minesweeper_mines'] . ': ' . $this->board_size['mines'] - $marked . ' / ' . $this->board_size['mines']), ';';

			if ($this->dead)
				echo '
document.getElementById(\'mineover\').style.display = \'block\';';

			echo '
document.getElementById(\'mineopt\').innerHTML = ', JavaScriptEscape(
			'<a href="' . $this->gameurl . ';restart">' . $txt['minesweeper_restart'] . '</a>' . ($this->dead || $this->complete ?
			' | <a href="' . $this->gameurl . ';save">' . $txt['minesweeper_save'] . '</a>' : '')), ';';
	}

	function decideImage($col)
	{
		$image = '';

		if ($col['flag'] && (!$this->dead || $col['is_mine']))
			$image .= 'flag.png';
		elseif ($col['flag'] && $this->dead)
			$image .= 'wrong_flag.png';
		elseif (!$col['open'] && !$this->dead)
			$image .= 'close.png';
		elseif ($col['is_mine'] && $this->dead && $col['open'])
			$image .= 'redmine.png';
		elseif ($col['is_mine'])
			$image .= 'mine.png';
		elseif ($col['mines'] > 0)
			$image .= $col['mines'] . '.png';
		else
			$image .= 'nothing.png';

		return $image;
	}

	function showGame()
	{
		global $txt;

		$marked = 0;

		foreach ($this->board as $y => $row)
		{
			foreach ($row as $x => $col)
			{
				if ($col['flag'])
					$marked++;
			}
		}

		echo '
		<script type="text/javascript" src="', $this->baseurl, '/jquery.js"></script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("a.spot").mouseup(function(event)
				{
					if (event.button == 2 || event.shiftKey)
						$.getScript($(this).attr("href") + \';flag;ajax\');
					else
						$.getScript($(this).attr("href") + \';ajax\');

					return false;
				}).click(function(){ return false; });

				$("a.spot").mouseup(function(event)
				{
					return false;
				});
			});
		</script>
		<div id="minesweeper">
			<div id="minescore">', $txt['minesweeper_score'], ': <b>', $this->score, '</b> | ', $txt['minesweeper_mines'], ': ', $this->board_size['mines'] - $marked, ' / ', $this->board_size['mines'], '</div>
			<div style="border: 1px black solid; width: ', ($this->board_size['x'] + 1) * 15, 'px; margin: auto;">';

		foreach ($this->board as $y => &$row)
		{
			foreach ($row as $x => &$col)
			{
				$image = $this->decideImage($col);

				$col['prev'] = $image;

				echo '<a class="spot" href="', $this->gameurl, ';x=', $x, ';y=', $y, '"><img oncontextmenu="return false" id="spot', $col['id'], '" src="', $this->baseurl, $image, '" alt="" /></a>';
			}

			echo '
			<br />';
		}

		echo '
			</div>
			<div id="mineover"', !$this->dead ? ' style="display: none;"' : '', '><b>Game over!</b></div>
			<div id="mineopt">
				<a href="', $this->gameurl, ';restart">', $txt['minesweeper_restart'], '</a>';

		if ($this->dead || $this->complete)
			echo ' | <a href="', $this->gameurl, ';save">', $txt['minesweeper_save'], '</a>';

		echo '
			</div>
		</div>';
	}

	// Called before main IF there was no session data
	function newSession()
	{
		$this->score = 0;
		$this->clickScore = 0;
		$this->bonus = 0;
		$this->dead = false;
		$this->random_id = rand(0, 1024);
		$this->mines_placed = false;
		$this->start_time = time();
		$this->board = array();
		$this->generateBoard($this->difficulty);
	}

	function generateBoard($diff)
	{
		if (isset($this->modes[$diff]))
		{
			$this->board_size = $this->modes[$diff]['board'];
			$this->scoring = $this->modes[$diff]['scoring'];
		}
		else
			return;
		$this->difficulty = $diff;

		// Generate board array without any mines
		if ($this->board_size['x'] * $this->board_size['y'] < $this->board_size['mines'])
			$this->board_size['mines'] = $this->board_size['x'] * $this->board_size['y'];

		$i = 1;

		for ($y = 0; $y <= $this->board_size['y']; $y++)
		{
			$this->board[$y] = array();

			for ($x = 0; $x <= $this->board_size['x']; $x++)
			{
				$this->board[$y][$x] = array(
					'id' => $i++,
					'is_mine' => false,
					'mines' => 0,
					'open' => false,
					'flag' => false,
				);
			}
		}

		updateGame($this->id, array('num_plays' => '+'));
	}

	// Called before main IF there was session data
	function setSession($session_data)
	{
		// Random ID
		$this->random_id = $session_data['random_id'];
		// Score
		$this->score = $session_data['score'];
		$this->difficulty = $session_data['difficulty'];

		// Board
		$this->board = $session_data['board'];
		$this->board_size = $this->modes[$this->difficulty]['board'];
		$this->scoring = $this->modes[$this->difficulty]['scoring'];
		$this->mines_placed = $session_data['mines_placed'];
		$this->complete = $session_data['complete'];

		// Status
		$this->dead = $session_data['dead'];
		$this->start_time = $session_data['start_time'];
		$this->click_time = $session_data['click_time'];
		$this->flags = $session_data['flags'];
	}

	// Must return data for session storage
	// Called after main
	// Returning empty data will reset session on next page load
	function getSession()
	{
		return array(
			// Random ID
			'random_id' => $this->random_id,
			// Board
			'board' => $this->board,
			'board_size' => $this->board_size,
			'mines_placed' => $this->mines_placed,
			'complete' => $this->complete,
			// Score
			'difficulty' => $this->difficulty,
			'score' => $this->score,
			// Status
			'dead' => $this->dead,
			'start_time' => $this->start_time,
			'click_time' => $this->click_time,
			'flags' => $this->flags,
		);
	}

	function getResult()
	{
		return array(
			'start_time' => $this->start_time,
			'end_time' => time(),
			'score' => $this->score,
		);
	}

	// Internal functions
	function flagSpot($x, $y)
	{
		if (!$this->board[$y][$x]['open'])
		{
			$this->board[$y][$x]['flag'] = !$this->board[$y][$x]['flag'];
			$this->board[$y][$x]['prev'] = '';

			if ($this->board[$y][$x]['flag'])
				$this->flags++;
			else
				$this->flags--;
		}
	}

	function openSpot($x, $y)
	{
		if ($this->board[$y][$x]['flag'])
			return false;

		$this->board[$y][$x]['open'] = true;

		if (!$this->mines_placed)
		{
			$this->placeMines($this->board, $x, $y);

			$this->mines_placed = true;
		}

		if ($this->board[$y][$x]['is_mine'])
		{
			// It was mine

			$this->dead = true;
		}
		else
		{
			$points = $this->scoring['base'];

			$y2 = $y;

			if ($this->board[$y][$x]['mines'] == 0)
			{
				$this->openRow($x, $y, $points);
				$this->openCol($x, $y, $points);
			}

			$this->score += $points;

			$this->bonus = 0;
			$this->clickScore = $points;

			$this->click_time = time();
		}
	}

	function openRow($x, $y, &$points)
	{
		$y2 = $y;
		$x2 = $x;

		while (true)
		{
			$x2--;

			if (!isset($this->board[$y2][$x2]))
				break;

			if (!$this->board[$y2][$x2]['open'] && !$this->board[$y2][$x2]['is_mine'])
			{
				$this->board[$y2][$x2]['open'] = true;

				$points += $this->scoring['reveal'];

				if ($this->board[$y2][$x2]['mines'] == 0)
					$this->openCol($x2, $y2, $points);
			}

			if ($this->board[$y2][$x2]['is_mine'] || $this->board[$y][$x2]['mines'] > 0)
			{
				$leftSide = $x2;
				break;
			}
		}

		$x2 = $x;

		while (true)
		{
			$x2++;

			if (!isset($this->board[$y2][$x2]))
				break;

			if (!$this->board[$y2][$x2]['open'] && !$this->board[$y2][$x2]['is_mine'])
			{
				$this->board[$y2][$x2]['open'] = true;

				$points += $this->scoring['reveal'];

				if ($this->board[$y2][$x2]['mines'] == 0)
					$this->openCol($x2, $y2, $points);
			}

			if ($this->board[$y2][$x2]['is_mine'] || $this->board[$y][$x2]['mines'] > 0)
			{
				$rightSide = $x2;
				break;
			}
		}
	}

	function openCol($x, $y, &$points)
	{
		$y2 = $y;
		$x2 = $x;

		while (true)
		{
			$y2--;

			if (!isset($this->board[$y2][$x2]))
				break;

			if (!$this->board[$y2][$x2]['open'] && !$this->board[$y2][$x2]['is_mine'])
			{
				$this->board[$y2][$x2]['open'] = true;

				$points += $this->scoring['reveal'];

				if ($this->board[$y2][$x2]['mines'] == 0)
					$this->openRow($x2, $y2, $points);
			}

			if ($this->board[$y2][$x2]['is_mine'] || $this->board[$y2][$x2]['mines'] > 0)
			{
				$topSide = $y2;
				break;
			}
		}

		$y2 = $y;

		while (true)
		{
			$y2++;

			if (!isset($this->board[$y2][$x2]))
				break;

			if (!$this->board[$y2][$x2]['open'] && !$this->board[$y2][$x2]['is_mine'])
			{
				$this->board[$y2][$x2]['open'] = true;

				$points += $this->scoring['reveal'];

				if ($this->board[$y2][$x2]['mines'] == 0)
					$this->openRow($x2, $y2, $points);
			}

			if ($this->board[$y2][$x2]['is_mine'] || $this->board[$y2][$x2]['mines'] > 0)
			{
				$bottomSide = $y2;
				break;
			}
		}
	}

	function placeMines(&$board, $mx, $my)
	{
		$this->start_time = time();

		for ($i = 1; $i <= $this->board_size['mines']; $i++)
		{
			while (true)
			{
				$x = rand(0, $this->board_size['x']);
				$y = rand(0, $this->board_size['y']);

				if ((abs($x - $mx) > 1 || abs($y - $my) > 1) && !$board[$y][$x]['is_mine'] && !$board[$y][$x]['open'])
				{
					$board[$y][$x]['is_mine'] = true;

					$coords = array(
						array($x - 1, $y - 1),
						array($x, $y - 1),
						array($x + 1, $y - 1),
						array($x - 1, $y),
						array($x + 1, $y),
						array($x - 1, $y + 1),
						array($x, $y + 1),
						array($x + 1, $y + 1),
					);

					foreach ($coords as $x2)
					{
						list ($x2, $y2) = $x2;

						if (isset($board[$y2][$x2]))
							$board[$y2][$x2]['mines']++;
					}

					break;
				}
			}
		}
	}
}
?>