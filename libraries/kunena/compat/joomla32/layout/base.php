<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Compatibility layer for JLayoutBase
 */
class KunenaCompatLayoutBase extends JLayoutBase {
    /**
     * Data for the layout
     *
     * @var    array
     * @since  3.5
     */
    protected $data = array();

    /**
     * Add a debug message to the debug messages array
     *
     * @param   string  $message  Message to save
     *
     * @return  self
     *
     * @since   3.2
     */
    public function addDebugMessage($message)
    {
        $this->debugMessages[] = $message;

        return $this;
    }

    /**
     * Clear the debug messages array
     *
     * @return  self
     *
     * @since   3.5
     */
    public function clearDebugMessages()
    {
        $this->debugMessages = array();

        return $this;
    }

    /**
     * Render a layout with debug info
     *
     * @param   mixed  $data  Data passed to the layout
     *
     * @return  string
     *
     * @since    3.5
     */
    public function debug($data = array())
    {
        $this->setDebug(true);

        $output = $this->render($data);

        $this->setDebug(false);

        return $output;
    }

    /**
     * Method to get the value from the data array
     *
     * @param   string  $key           Key to search for in the data array
     * @param   mixed   $defaultValue  Default value to return if the key is not set
     *
     * @return  mixed   Value from the data array | defaultValue if doesn't exist
     *
     * @since   3.5
     */
    public function get($key, $defaultValue = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $defaultValue;
    }

    /**
     * Get the data being rendered
     *
     * @return  array
     *
     * @since   3.5
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return  boolean
     *
     * @since   3.5
     */
    public function isDebugEnabled()
    {
        return $this->getOptions()->get('debug', false) === true;
    }

    /**
     * Method to set a value in the data array. Example: $layout->set('items', $items);
     *
     * @param   string  $key    Key for the data array
     * @param   mixed   $value  Value to assign to the key
     *
     * @return  self
     *
     * @since   3.5
     */
    public function set($key, $value)
    {
        $this->data[(string) $key] = $value;

        return $this;
    }

    /**
     * Set the the data passed the layout
     *
     * @param   array  $data  Array with the data for the layout
     *
     * @return  self
     *
     * @since   3.5
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Change the debug mode
     *
     * @param   boolean  $debug  Enable / Disable debug
     *
     * @return  self
     *
     * @since   3.5
     */
    public function setDebug($debug)
    {
        $this->options->set('debug', (boolean) $debug);

        return $this;
    }
}
