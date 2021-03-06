<?php

namespace Dahl\PhpTerm\Output;

/**
 * Element class. For printing text.
 *
 * @copyright Copyright (C) 2015 Albert Dahlin
 * @author Albert Dahlin <info@albertdahlin.com>
 * @license MIT License <http://opensource.org/licenses/MIT>
 */
class Element
{
    /**
     * The text to print.
     *
     * @var array
     * @access protected
     */
    protected $_text = array();

    /**
     * If element text or style has changed.
     *
     * @var boolean
     * @access protected
     */
    protected $_hasChanges = false;

    /**
     * The element Id
     *
     * @var string
     * @access protected
     */
    protected $_id;

    /**
     * The parent element
     *
     * @var Window | Element
     * @access protected
     */
    protected $_parent;

    /**
     * The size of parent element.
     *
     * @var array
     * @access protected
     */
    protected $_size;

    /**
     * The element style.
     *
     * @var array
     * @access protected
     */
    protected $_style = array();

    /**
     * Constructor.
     *
     * @param string $id
     * @access public
     * @return void
     */
    public function __construct($id)
    {
        $this->_id = $id;
    }

    /**
     * Returns element id.
     *
     * @access public
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns element parent.
     *
     * @access public
     * @return Window | Element
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Sets the element parent.
     *
     * @param Window | Element $parent
     * @access public
     * @return void
     */
    public function setParent($parent)
    {
        $this->_parent = $parent;

        return $this;
    }

    /**
     * Returns the output object.
     *
     * @access public
     * @return Terminal
     */
    public function getOutput()
    {
        return $this->_parent->getOutput();
    }

    /**
     * Returns the output object.
     *
     * @access public
     * @return Terminal
     */
    public function getInput()
    {
        return $this->_parent->getInput();
    }

    /**
     * Select this element as the focus element.
     *
     * @access public
     * @return Element
     */
    public function setFocus()
    {
        $this->_parent->setFocus($this);

        return $this;
    }

    /**
     * Applies focus, moving the cursor to the end of the
     * element.
     *
     * @access public
     * @return void
     */
    public function applyFocus()
    {
        $output = $this->getOutput();
        $width  = $this->getWidth();
        $height = $this->getHeight();

        $row = $this->_getStartRow($height);
        $col = $this->_getStartCol($width, $width);
        $output->setPos($row, $col + $width);
    }

    /**
     * Set element text.
     *
     * @param string $string
     * @access public
     * @return void
     */
    public function setText($string)
    {
        $this->_hasChanges = true;
        $this->_text = array();
        foreach (explode("\n", $string) as $row) {
            $this->_text[] = array('text' => $row);
        };

        return $this;
    }

    /**
     * Set the element style.
     *
     * @param string $string
     * @access public
     * @return Element
     */
    public function setStyle($string)
    {
        $this->_hasChanges = true;
        $styles = explode(';', $string);
        foreach ($styles as $style) {
            $style = explode(':', $style);
            if (count($style) == 2) {
                $key = trim($style[0]);
                $val = trim($style[1]);
                $this->_style[$key] = $val;
            }
        }

        return $this;
    }

    /**
     * Returns element style
     *
     * @param string $key
     * @access public
     * @return string | array
     */
    public function getStyle($key = null)
    {
        if ($key) {
            if (isset($this->_style[$key])) {
                return $this->_style[$key];
            } else {
                return null;
            }
        }

        return $this->_style;
    }

    /**
     * Returns element width
     *
     * @access public
     * @return int
     */
    public function getWidth()
    {
        $width = 0;
        foreach ($this->_text as $row) {
            $width = max($width, mb_strlen($row['text']));
        }

        return $width;
    }

    /**
     * Returns element height.
     *
     * @access public
     * @return int
     */
    public function getHeight()
    {
        return count($this->_text);
    }

    public function getMaxHeight()
    {
        $maxHeight = $this->getStyle('max-height');
        if ($maxHeight) {
            return min($maxHeight, $this->getParent()->getMaxHeight());
        }

        return $this->getParent()->getMaxHeight();
    }

    public function getMaxWidth()
    {
        $maxWidth = $this->getStyle('max-width');

        if ($maxWidth) {
            return min($maxWidth, $this->getParent()->getMaxWidth());
        }

        return $this->getParent()->getMaxWidth();
    }

    /**
     * Returns the parent element size.
     *
     * @access public
     * @return array
     */
    public function getSize()
    {
        if (!$this->_size) {
            $this->_size = $this->getParent()->getSize();
        }

        return $this->_size;
    }

    /**
     * Renders element in termminal if it has changed.
     *
     * @access public
     * @return void
     */
    public function render($force = false)
    {
        if (!$force && !$this->_hasChanges) {
            return;
        }
        $this->_beforeRender();
        $output = $this->getOutput();
        $width  = $this->getWidth();
        $height = $this->getHeight();

        $row = $this->_getStartRow($height);
        foreach ($this->_text as $line) {
            $len = mb_strlen($line['text']);
            $line = $this->_prepareLine($line);
            $col = $this->_getStartCol($len, $width);
            if ($row !== false) {
                $output->setPos($row, $col);
                echo $line;
                $row++;
            } else {
                $output->setCol($col);
                echo $line . "\n";
            }
        }

        $this->_hasChanges = false;

        return $this;
    }

    protected function _beforeRender()
    {

    }

    /**
     * Prepares a text line with ANSI escape codes.
     *
     * @param array $line
     * @access protected
     * @return string
     */
    protected function _prepareLine($line)
    {
        $text = '';
        $color = array();
        $reset = '';
        if (isset($line['text'])) {
            $text = $line['text'];
        }
        if (isset($line['color'])) {
            $color[] = $this->_getFgColor($line['color']);
        }
        if (isset($line['background'])) {
            $color[] = $this->_getBgColor($line['background']);
        }

        if ($color) {
            $color = "\033[" . implode(';', $color) . 'm';
            $reset = "\033[0m";
        } else {
            $color = '';
        }

        return $color . $text . $reset;
    }

    /**
     * Returns an ANSI foreground color code.
     *
     * @param string $color
     * @access protected
     * @return string
     */
    protected function _getFgColor($color)
    {
        return $this->getOutput()->getFgColor($color);
    }

    /**
     * Returns an ANSI background color code.
     *
     * @param string $color
     * @access protected
     * @return string
     */
    protected function _getBgColor($color)
    {
        return $this->getOutput()->getBgColor($color);
    }

    /**
     * Calculates start row from element style settings.
     *
     * @param mixed $len
     * @access protected
     * @return int
     */
    protected function _getStartRow($len = 1)
    {
        $top      = $this->getStyle('top');
        $bottom   = $this->getStyle('bottom');
        $position = $this->getStyle('position');
        $size     = $this->getSize();

        if ($position == 'fixed') {
            if ($top !== null) {
                if (substr($top, -1) == '%') {
                    $top = substr($top, 0, -1) / 100;
                    $top = ceil($size['row'] * $top);
                }
                $row = $top + 1;
            } elseif ($bottom !== null) {
                if (substr($bottom, -1) == '%') {
                    $bottom = substr($bottom, 0, -1) / 100;
                    $bottom = ceil($size['row'] * $bottom);
                }

                $row = $size['row'] - $bottom;
            }
        } else {
            $row = false;
        }

        return $row;
    }

    /**
     * Calculates start col from element style settings.
     *
     * @param int $len
     * @param int $width
     * @param int $offset
     * @access protected
     * @return int
     */
    protected function _getStartCol($len, $width)
    {
        $align  = $this->getStyle('text-align');
        $valid  = array('left', 'right', 'center');
        $offset = $this->_getHOff($width);

        if (!$align || !in_array($align, $valid)) {
            $align = 'left';
        }

        switch ($align) {
            case 'left':
                $col = $offset;
                break;
            case 'center':
                $col = $offset + floor($width / 2 - $len / 2);
                break;
            case 'right':
                $col = $offset + $width - $len;
                break;
        }

        return $col;
    }

    /**
     * Get element horizontal offset.
     *
     * @param int $width
     * @access protected
     * @return int
     */
    protected function _getHOff($width)
    {
        $size    = $this->getSize();
        $left    = $this->getStyle('left');
        $middle  = $this->getStyle('middle');
        $right   = $this->getStyle('right');
        if ($right !== null) {
            if (substr($right, -1) == '%') {
                $right = substr($right, 0, -1) / 100;
                $right = ceil($size['col'] * $right);
            }
            $offset = $size['col'] - $width - $right + 1;
        } elseif ($middle !== null) {
            if (substr($middle, -1) == '%') {
                $middle = substr($middle, 0, -1) / 100;
                $middle = ceil($size['col'] * $middle);
            }
            $offset = $middle - floor($width / 2) + 1;
        } elseif ($left !== null) {
            if (substr($left, -1) == '%') {
                $left = substr($left, 0, -1) / 100;
                $left = ceil($size['col'] * $left);
            }
            $offset = $left + 1;
        } else {
            $offset = 0;
        }

        return $offset;
    }
}
