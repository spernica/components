<?php
/**
 * Mesour Components
 *
 * @license LGPL-3.0 and BSD-3-Clause
 * @copyright (c) 2015 Matous Nemec <matous.nemec@mesour.com>
 */

namespace Mesour\UI;

use Mesour\Components\Application\Request;
use Mesour\Components\Application\Url;
use Mesour\Components\Component;
use Mesour\Components\Link\ILink;
use Mesour\Components\Localize\ITranslator;
use Mesour\Components\Security\IAuth;
use Mesour\Components\Session\ISession;

/**
 * @author mesour <matous.nemec@mesour.com>
 * @package Mesour Components
 */
class Application extends Component
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Url
     */
    private $url;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Request
     */
    public function getUrl()
    {
        if(!$this->url) {
            $this->url = new Url($_SERVER['REQUEST_URI']);
        }
        return $this->url;
    }

    public function isAjax()
    {
        return $this->request->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    public function createLink($component, $args = array()) {
        dump($this->getUrl());
        return '';
    }

    public function setRequest(array $request)
    {
        $this->request = new Request($request);
        return $this;
    }

    public function setSession(ISession $session)
    {
        Control::$default_session = $session;
        return $this;
    }

    public function setLink(ILink $link)
    {
        Control::$default_link = $link;
        return $this;
    }

    public function setTranslator(ITranslator $translator)
    {
        Control::$default_translator = $translator;
        return $this;
    }

    public function setAuth(IAuth $auth)
    {
        Control::$default_auth = $auth;
        return $this;
    }

    public function run()
    {

    }

}
