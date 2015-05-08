<?php
/**
 * Mesour Components
 *
 * @license LGPL-3.0 and BSD-3-Clause
 * @copyright (c) 2013 - 2015 Matous Nemec <matous.nemec@mesour.com>
 */

namespace Mesour\Components;

use Mesour\Components\Link\ILink;
use Mesour\Components\Link\IUrl;
use Mesour\Components\Link\Link;
use Mesour\Components\Session\ISession;

/**
 * @author mesour <matous.nemec@mesour.com>
 * @package Mesour Components
 */
abstract class Control extends Component
{

    /**
     * @var ILink|null
     */
    static public $default_link = NULL;

    /**
     * @var ISession|null
     */
    static public $default_session = NULL;

    /**
     * @var ISession|null
     */
    private $session;

    /**
     * @var ILink|null
     */
    private $link;

    public function setLink(ILink $link)
    {
        $this->link = $link;
    }

    /**
     * @param $destination
     * @param array $args
     * @return IUrl
     */
    public function link($destination, $args = array()) {
        return $this->getLink()->create($destination, $args);
    }

    /**
     * @return ILink|null
     */
    private function getLink()
    {
        $parent = $this->getParent();
        return !$this->link && $parent instanceof self ? $parent->getLink() : ($this->link ? $this->link : (self::$default_link?self::$default_link:(self::$default_link = new Link)));
    }

    public function setSession(ISession $session)
    {
        $this->session = $session;
        $this->session->loadState();
    }

    /**
     * @return ISession|null
     */
    public function getSession()
    {
        $parent = $this->getParent();
        return !$this->session && $parent instanceof self ? $parent->getSession()->getEmptyClone($this->getFullName()) : $this->session;
    }

}
