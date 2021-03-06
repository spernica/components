<?php
/**
 * This file is part of the Mesour components (http://components.mesour.com)
 *
 * Copyright (c) 2017 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Components\Session;

use Mesour;

/**
 * @author Matouš Němec <http://mesour.com>
 */
class SessionSection implements ISessionSection
{

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $data = [];

	public function __construct($section)
	{
		if (!Mesour\Components\Utils\Helpers::validateKeyName($section)) {
			throw new Mesour\InvalidArgumentException(
				sprintf('SessionSection name must be integer or string, %s given.', gettype($section))
			);
		}
		$this->name = $section;
	}

	public function getName()
	{
		return $this->name;
	}

	public function loadState($data)
	{
		$this->data = $data;
		return $this;
	}

	public function set($key, $val)
	{
		if (!Mesour\Components\Utils\Helpers::validateKeyName($key)) {
			throw new Mesour\InvalidArgumentException(
				sprintf('Key must be integer or string, %s given.', gettype($key))
			);
		}
		$this->data[$key] = $val;
		return $this;
	}

	public function get($key = null, $default = null)
	{
		if (is_null($key)) {
			return $this->data;
		}
		return !isset($this->data[$key]) ? $default : $this->data[$key];
	}

	public function remove()
	{
		$this->data = [];
		return $this;
	}

}
