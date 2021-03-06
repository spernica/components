<?php
/**
 * This file is part of the Mesour components (http://components.mesour.com)
 *
 * Copyright (c) 2017 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Components\Application;

use Mesour;

/**
 * @author Matouš Němec <http://mesour.com>
 */
class Payload implements IPayload
{

	private $data = [];

	public function sendPayload()
	{
		if (ob_get_contents()) {
			ob_clean();
		}
		header('Content-type: application/json');
		echo json_encode($this->data);
		exit(0);
	}

	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	public function __get($key)
	{
		return $this->get($key);
	}

	public function __isset($key)
	{
		return isset($this->data[$key]);
	}

	public function __unset($key)
	{
		unset($this->data[$key]);
	}

	public function set($key, $value)
	{
		if (!is_string($key)) {
			throw new Mesour\InvalidArgumentException(
				sprintf('Key must be string. "%s" given.', gettype($key))
			);
		}
		$this->data[$key] = $value;
		return $this;
	}

	public function get($key = null, $default = null)
	{
		if (is_null($key)) {
			return $this->data;
		}
		if (!is_string($key)) {
			throw new Mesour\InvalidArgumentException(
				sprintf('Key must be string. "%s" given.', gettype($key))
			);
		}
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

}
