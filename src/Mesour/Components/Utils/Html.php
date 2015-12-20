<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Mesour\Components\Utils;

use Mesour;


/**
 * HTML helper.
 *
 * <code>
 * $el = Html::el('a')->href($link)->setText('Nette');
 * $el->class = 'myclass';
 * echo $el;
 *
 * echo $el->startTag(), $el->endTag();
 * </code>
 *
 * @author     David Grudl
 *
 * @method $this style() style($style, $append = FALSE) Add style
 * @method $this src() src($src, $append = FALSE) Add src
 * @method $this class() class($src, $append = FALSE) Add src
 * @method $this id($src) Set ID attribute
 */
class Html implements \ArrayAccess, \Countable, \IteratorAggregate, IString
{
    /** @var string  element's name */
    private $name;

    /** @var bool  is element empty? */
    private $isEmpty;

    /** @var array  element's attributes */
    public $attrs = [];

    /** @var array  of Html | string nodes */
    protected $children = [];

    /** @var bool  use XHTML syntax? */
    public static $xhtml = FALSE;

    /** @var array  empty (void) elements */
    public static $emptyElements = [
        'img' => 1, 'hr' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'area' => 1, 'embed' => 1, 'keygen' => 1,
        'source' => 1, 'base' => 1, 'col' => 1, 'link' => 1, 'param' => 1, 'basefont' => 1, 'frame' => 1,
        'isindex' => 1, 'wbr' => 1, 'command' => 1, 'track' => 1,
    ];


    /**
     * Static factory.
     * @param  string $name        element name (or NULL)
     * @param  array|string $attrs element 's attributes or plain text content
     * @return self
     */
    public static function el($name = NULL, $attrs = NULL)
    {
        $el = new static;
        $parts = explode(' ', $name, 2);
        $el->setName($parts[0]);

        if (is_array($attrs)) {
            $el->attrs = $attrs;

        } elseif ($attrs !== NULL) {
            $el->setText($attrs);
        }

        if (isset($parts[1])) {
            foreach (Helpers::matchAll($parts[1] . ' ', '#([a-z0-9:-]+)(?:=(["\'])?(.*?)(?(2)\\2|\s))?#i') as $m) {
                $el->attrs[$m[1]] = isset($m[3]) ? $m[3] : TRUE;
            }
        }

        return $el;
    }


    /**
     * Changes element's name.
     * @param  string $name
     * @param  bool $isEmpty Is element empty?
     * @return self
     * @throws Mesour\InvalidArgumentException
     */
    public function setName($name, $isEmpty = NULL)
    {
        if ($name !== NULL && !is_string($name)) {
            throw new Mesour\InvalidArgumentException(sprintf('Name must be string or NULL, %s given.', gettype($name)));
        }

        $this->name = $name;
        $this->isEmpty = $isEmpty === NULL ? isset(static::$emptyElements[$name]) : (bool)$isEmpty;
        return $this;
    }


    /**
     * Returns element's name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Is element empty?
     * @return bool
     */
    public function isEmpty()
    {
        return $this->isEmpty;
    }


    /**
     * Sets multiple attributes.
     * @param  array
     * @return self
     */
    public function addAttributes(array $attrs)
    {
        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }


    /**
     * Overloaded setter for element's attribute.
     * @param  string    HTML attribute name
     * @param  mixed     HTML attribute value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attrs[$name] = $value;
    }


    /**
     * Overloaded getter for element's attribute.
     * @param  string    HTML attribute name
     * @return mixed     HTML attribute value
     */
    public function &__get($name)
    {
        return $this->attrs[$name];
    }


    /**
     * Overloaded tester for element's attribute.
     * @param  string    HTML attribute name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->attrs[$name]);
    }


    /**
     * Overloaded unsetter for element's attribute.
     * @param  string    HTML attribute name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->attrs[$name]);
    }


    /**
     * Overloaded setter for element's attribute.
     * @param  string                HTML attribute name
     * @param  array   (string) HTML attribute value or pair?
     * @return self
     */
    public function __call($m, $args)
    {
        $p = substr($m, 0, 3);
        if ($p === 'get' || $p === 'set' || $p === 'add') {
            $m = substr($m, 3);
            $m[0] = $m[0] | "\x20";
            if ($p === 'get') {
                return isset($this->attrs[$m]) ? $this->attrs[$m] : NULL;

            } elseif ($p === 'add') {
                $args[] = TRUE;
            }
        }

        if (count($args) === 0) { // invalid

        } elseif (count($args) === 1) { // set
            $this->attrs[$m] = $args[0];

        } elseif ((string)$args[0] === '') {
            $tmp = &$this->attrs[$m]; // appending empty value? -> ignore, but ensure it exists

        } elseif (!isset($this->attrs[$m]) || is_array($this->attrs[$m])) { // needs array
            $this->attrs[$m][$args[0]] = $args[1];

        } else {
            $this->attrs[$m] = [$this->attrs[$m], $args[0] => $args[1]];
        }

        return $this;
    }


    /**
     * Special setter for element's attribute.
     * @param  string $path
     * @param  array $query
     * @return self
     */
    public function href($path, $query = NULL)
    {
        if ($query) {
            $query = http_build_query($query, NULL, '&');
            if ($query !== '') {
                $path .= '?' . $query;
            }
        }
        $this->attrs['href'] = $path;
        return $this;
    }


    /**
     * Setter for data-* attributes. Booleans are converted to 'true' resp. 'false'.
     * @param $name
     * @param null $value
     * @return self
     */
    public function data($name, $value = NULL)
    {
        if (func_num_args() === 1) {
            $this->attrs['data'] = $name;
        } else {
            $this->attrs["data-$name"] = is_bool($value) ? json_encode($value) : $value;
        }
        return $this;
    }


    /**
     * Sets element's HTML content.
     * @param  string $html raw HTML string
     * @return self
     * @throws Mesour\InvalidArgumentException
     */
    public function setHtml($html)
    {
        if (is_array($html)) {
            throw new Mesour\InvalidArgumentException(sprintf('Textual content must be a scalar, %s given.', gettype($html)));
        }
        $this->removeChildren();
        $this->children[] = (string)$html;
        return $this;
    }


    /**
     * Returns element's HTML content.
     * @return string
     */
    public function getHtml()
    {
        $s = '';
        foreach ($this->children as $child) {
            if (is_object($child)) {
                $s .= $child->render();
            } else {
                $s .= $child;
            }
        }
        return $s;
    }


    /**
     * Sets element's textual content.
     * @param  string
     * @return self
     */
    public function setText($text)
    {
        if (!is_array($text) && !$text instanceof self) {
            $text = htmlspecialchars((string)$text, ENT_NOQUOTES, 'UTF-8');
        }
        return $this->setHtml($text);
    }


    /**
     * Returns element's textual content.
     * @return string
     */
    public function getText()
    {
        return html_entity_decode(strip_tags($this->getHtml()), ENT_QUOTES, 'UTF-8');
    }


    /**
     * Adds new element's child.
     * @param  Html|string Html node or raw HTML string
     * @return self
     */
    public function add($child)
    {
        return $this->insert(NULL, $child);
    }


    /**
     * Creates and adds a new Html child.
     * @param  string $name        elements's name
     * @param  array|string $attrs element 's attributes or raw HTML string
     * @return self  created element
     */
    public function create($name, $attrs = NULL)
    {
        $this->insert(NULL, $child = static::el($name, $attrs));
        return $child;
    }


    /**
     * Inserts child node.
     * @param  int|NULL $index    position of NULL for appending
     * @param  Html|string $child Html node or raw HTML string
     * @param  bool $replace
     * @return self
     * @throws Mesour\InvalidArgumentException
     */
    public function insert($index, $child, $replace = FALSE)
    {
        if ($child instanceof Html || $child instanceof IString || is_scalar($child)) {
            if ($index === NULL) { // append
                $this->children[] = $child;

            } else { // insert or replace
                array_splice($this->children, (int)$index, $replace ? 1 : 0, [$child]);
            }

        } else {
            throw new Mesour\InvalidArgumentException(sprintf('Child node must be scalar or Html object, %s given.', is_object($child) ? get_class($child) : gettype($child)));
        }

        return $this;
    }


    /**
     * Inserts (replaces) child node (\ArrayAccess implementation).
     * @param  int|NULL $index    position of NULL for appending
     * @param  Html|string $child Html node or raw HTML string
     * @return void
     */
    public function offsetSet($index, $child)
    {
        $this->insert($index, $child, TRUE);
    }


    /**
     * Returns child node (\ArrayAccess implementation).
     * @param  int $index
     * @return self|string
     */
    public function offsetGet($index)
    {
        return $this->children[$index];
    }


    /**
     * Exists child node? (\ArrayAccess implementation).
     * @param  int $index
     * @return bool
     */
    public function offsetExists($index)
    {
        return isset($this->children[$index]);
    }


    /**
     * Removes child node (\ArrayAccess implementation).
     * @param  int $index
     * @return void
     */
    public function offsetUnset($index)
    {
        if (isset($this->children[$index])) {
            array_splice($this->children, (int)$index, 1);
        }
    }


    /**
     * Returns children count.
     * @return int
     */
    public function count()
    {
        return count($this->children);
    }


    /**
     * Removed all children.
     * @return void
     */
    public function removeChildren()
    {
        $this->children = [];
    }


    /**
     * Iterates over a elements.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }


    /**
     * Returns all of children.
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * Renders element's start tag, content and end tag.
     * @param  int
     * @return string
     */
    public function render($indent = NULL)
    {
        $s = $this->startTag();

        if (!$this->isEmpty) {
            // add content
            if ($indent !== NULL) {
                $indent++;
            }
            foreach ($this->children as $child) {
                if (is_object($child)) {
                    $s .= $child->render($indent);
                } else {
                    $s .= $child;
                }
            }

            // add end tag
            $s .= $this->endTag();
        }


        if ($indent !== NULL) {
            return "\n" . str_repeat("\t", $indent - 1) . $s . "\n" . str_repeat("\t", max(0, $indent - 2));
        }
        return $s;
    }


    public function __toString()
    {
        return $this->render();
    }


    /**
     * Returns element's start tag.
     * @return string
     */
    public function startTag()
    {
        if ($this->name) {
            return '<' . $this->name . $this->attributes() . (static::$xhtml && $this->isEmpty ? ' />' : '>');

        } else {
            return '';
        }
    }


    /**
     * Returns element's end tag.
     * @return string
     */
    public function endTag()
    {
        return $this->name && !$this->isEmpty ? '</' . $this->name . '>' : '';
    }


    /**
     * Returns element's attributes.
     * @return string
     * @internal
     */
    public function attributes()
    {
        if (!is_array($this->attrs)) {
            return '';
        }

        $s = '';
        foreach ($this->attrs as $key => $value) {
            if ($value === NULL || $value === FALSE) {
                continue;

            } elseif ($value === TRUE) {
                if (static::$xhtml) {
                    $s .= ' ' . $key . '="' . $key . '"';
                } else {
                    $s .= ' ' . $key;
                }
                continue;

            } elseif (is_array($value)) {
                if ($key === 'data') { // deprecated
                    foreach ($value as $k => $v) {
                        if ($v !== NULL && $v !== FALSE) {
                            if (is_array($v)) {
                                $v = json_encode($v);
                            }
                            $q = strpos($v, '"') === FALSE ? '"' : "'";
                            $s .= ' data-' . $k . '='
                                . $q . str_replace(['&', $q], ['&amp;', $q === '"' ? '&quot;' : '&#39;'], $v)
                                . (strpos($v, '`') !== FALSE && strpbrk($v, ' <>"\'') === FALSE ? ' ' : '')
                                . $q;
                        }
                    }
                    continue;

                } elseif (strncmp($key, 'data-', 5) === 0) {
                    $value = json_encode($value);

                } else {
                    $tmp = NULL;
                    foreach ($value as $k => $v) {
                        if ($v != NULL) { // intentionally ==, skip NULLs & empty string
                            //  composite 'style' vs. 'others'
                            $tmp[] = $v === TRUE ? $k : (is_string($k) ? $k . ':' . $v : $v);
                        }
                    }
                    if ($tmp === NULL) {
                        continue;
                    }

                    $value = implode($key === 'style' || !strncmp($key, 'on', 2) ? ';' : ' ', $tmp);
                }

            } elseif (is_float($value)) {
                $value = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

            } else {
                $value = (string)$value;
            }

            $q = strpos($value, '"') === FALSE ? '"' : "'";
            $s .= ' ' . $key . '='
                . $q . str_replace(['&', $q], ['&amp;', $q === '"' ? '&quot;' : '&#39;'], $value)
                . (strpos($value, '`') !== FALSE && strpbrk($value, ' <>"\'') === FALSE ? ' ' : '')
                . $q;
        }

        $s = str_replace('@', '&#64;', $s);
        return $s;
    }


    /**
     * Clones all children too.
     */
    public function __clone()
    {
        foreach ($this->children as $key => $value) {
            if (is_object($value)) {
                $this->children[$key] = clone $value;
            }
        }
    }

}