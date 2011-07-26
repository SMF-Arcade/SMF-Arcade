<?php
/**
 * SWF Reader
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

class SWFReader
{
	var $header;
	var $handle;
	var $filesize;
	var $datalen;
	var $error;
	var $offset;
	var $data;
	var $bit;

	function SWFReader()
	{
	}

	function open($file)
	{
		$this->header = array();
		$this->handle = false;
		$this->data = '';
		$this->errors = array();
		$this->error = false;
		$this->bit = '';

		$this->handle = @fopen($file, 'rb');

		if (!$this->handle)
		{
			$this->error = true;
			return;
		}

		$this->filesize = filesize($file);

		$this->header['signature'] = fread($this->handle, 3);

		if ($this->header['signature'] == 'FWS')
			$this->header['compressed'] = false;
		elseif ($this->header['signature'] == 'CWS')
			$this->header['compressed'] = true;
		else
		{
			$this->error = true;
			$this->close();
			return;
		}

		$this->header['version'] = $this->le2int(fread($this->handle, 1));
		$this->header['file_length'] = $this->le2int(fread($this->handle, 4));
		$this->set_memory_limit();

		// Read rest of file
		if ($this->header['compressed'])
			$this->data = gzuncompress(fread($this->handle, $this->filesize));
		else
			$this->data = fread($this->handle, $this->filesize);

		$this->datalen = strlen($this->data);
		$this->offset = 0;

		// RECT with width and height (x1,y1 = 0)
		list (, $this->header['width'], , $this->header['height']) = $this->readRECT();

		// There seems to be zero that is always ignored skip it
		$this->offset++;

		// Framerate and frame count
		$this->header['framerate'] = $this->readUI8();
		$this->header['frames'] = $this->readUI16();
		$this->header['end_offset'] = $this->offset;

		$bg = $this->find_tag(9);
		$this->offset = $this->header['end_offset'];

		$this->header['background'] = $bg['data'];
		unset($bg);
	}

	// Finds next tag with certain id
	function find_tag($id, $reset = false)
	{
		if ($reset)
			$this->offset = $this->header['end_offset'];

		while ($this->offset < $this->datalen)
		{
			$offset = $this->offset;
			$tagidlen = $this->readUI16();

			$tag = array(
				'id' => ($tagidlen) >> 6,
				'length' => ($tagidlen & 0x3F)
			);

			if ($tag['length'] == 0x3F)
				$tag['length'] = $this->readUI32();

			if ($tag['id'] == $id)
			{
				$this->offset = $offset;
				return $this->read_tag();
			}

			$this->offset += $tag['length'];
		}

		return false;
	}

	// Read all tags
	function read_all_tags()
	{
		$this->offset = $this->header['end_offset'];
		$tags = array();

		while ($tag = $this->read_tag())
			$tags[] = $tag;

		return $tags;
	}

	// Read next tag
	function read_tag()
	{
		if ($this->offset >= $this->datalen)
			return false;

		$tagidlen = $this->readUI16();

		$tag = array(
			'id' => $tagidlen >> 6,
			//'name' => $this->tagname($tagidlen >> 6),
			'length' => $tagidlen & 0x3F
		);

		if ($tag['length'] == 63)
			$tag['length'] = $this->readUI32();

		$tag['position'] = array($this->offset, $tag['length']);

		$offset = $this->offset;

		// SetBackgroundColor
		if ($tag['id'] == 9)
			$tag['data'] = $this->readRGB();

		if ($this->offset != $offset + $tag['length'])
			$this->offset = $offset + $tag['length'];

		return $tag;
	}

	function close()
	{
		$this->data = '';

		if ($this->handle)
			fclose($this->handle);
	}

	function set_memory_limit()
	{
		// This may use a lot of memory espicially with compressed files
		$memory_limit = $this->bytes(@ini_get('memory_limit'));
		if ($memory_limit > 0)
		{
			$multi = $this->header['compressed'] ? 2 : 1.5;

			if ($this->header['file_length'] * $multi > $memory_limit)
				@ini_set('memory_limit', $this->header['file_length'] * $multi);
		}
	}

	function bytes($val)
	{
		$val = trim($val);
		$last = strtolower(substr($val, -1));

		switch($last)
		{
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

	    return $val;
	}

	// readRECT (used for example for frame size)
	function readRECT()
	{
		$bitshex = str_pad($this->readBit(5), 8, '0', STR_PAD_LEFT);
		$bitspv = bindec($bitshex);

		$str = '';

		$len = $bitspv * 4;

		$str = $this->readBit($len);

		return array(
			bindec(substr($str, 0, $bitspv)) / 20, // X1
			bindec(substr($str, $bitspv, $bitspv)) / 20, // X2
			bindec(substr($str, $bitspv * 2, $bitspv)) / 20, // Y1
			bindec(substr($str, $bitspv * 3, $bitspv)) / 20 // Y2
		);
	}

	// 8bit, 16bit, 32bit (un)signed integer (2 byte) or 1 in frame rate
	// little endian byteorder
	function readUI($byte, $signed = false)
	{
		$int = $this->le2int(substr($this->data, $this->offset, $byte), $signed);
		$this->offset += $byte;

		return $int;
	}

	function readUI8($byte = 1)
	{
		return $this->readUI($byte);
	}

	function readUI16($byte = 2)
	{
		return $this->readUI($byte);
	}

	function readUI32($byte = 4)
	{
		return $this->readUI($byte);
	}

	function readBytes($byte = 1)
	{
		$data = substr($this->data, $this->offset, $byte);
		$this->offset += $byte;

		return $data;
	}

	function readString()
	{
		$string = '';
		while (true)
		{
			$chr = $this->readBytes();

			if (ord($chr) == 0)
				return $string;

			$string .= $chr;
		}
	}

	function readBit($bit = 1)
	{
		if (empty($this->bit))
			$this->bit = str_pad(decbin(ord($this->readBytes())), 8, '0', STR_PAD_LEFT);

		if ($bit > strlen($this->bit))
		{
			$bytes = ceil($bit / 8);

			for ($i = 1; $i <= $bytes; $i++)
				$this->bit .= str_pad(decbin(ord($this->readBytes())), 8, '0', STR_PAD_LEFT);
		}

		$return = substr($this->bit, 0, $bit);
		$this->bit = substr($this->bit, $bit);

		return $return;
	}

	// Ints
	function readI16($byte = 2)
	{
		return $this->readUI($byte, true);
	}

	// RGB
	function readRGB()
	{
		$r = $this->readUI8();
		$g = $this->readUI8();
		$b = $this->readUI8();

		return array($r, $g, $b);
	}

	function readRGBA()
	{
		$r = $this->readUI8();
		$g = $this->readUI8();
		$b = $this->readUI8();
		$a = $this->readUI8();

		return array($r, $g, $b, $a);
	}

	// Little endian to integer
	function le2int($byte, $signed = false)
	{
		$byte = strrev($byte);
		$len = strlen($byte);
		$value = 0;

		for ($i = 0; $i < $len; $i++)
			$value += ord(substr($byte, $i, 1)) * pow(256, ($len - $i - 1));

		if (!$signed)
			return $value;

		if ($signed && $len > 1 && $len <= 4)
		{
			$mask = 0x80 << (8 * ($len - 1));
			if ($value & $mask)
				$value = 0 - ($value & ($mask - 1));

			return $value;
		}

		return false;
	}
}

?>