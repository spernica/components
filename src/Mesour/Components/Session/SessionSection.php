<?php
/**
 * This file is part of the Mesour components (http://components.mesour.com)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Components\Session;

use Mesour\Components\Helper;
use Mesour\Components\InvalidArgumentException;



/**
 * @author Matouš Němec <matous.nemec@mesour.com>
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
        if (!Helper::validateKeyName($section)) {
            throw new InvalidArgumentException('SessionSection name must be integer or string, ' . gettype($section) . ' given.');
        }
        $this->name = $section;
    }

    public function loadState($data)
    {
        $this->data = $data;
        return $this;
    }

    public function set($key, $val)
    {
        if (!Helper::validateKeyName($key)) {
            throw new InvalidArgumentException('Key must be integer or string, ' . gettype($key) . ' given.');
        }
        $this->data[$key] = $val;
        return $this;
    }

    public function get($key = NULL, $default = NULL)
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