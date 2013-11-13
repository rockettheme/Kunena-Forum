<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Private
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Private message.
 *
 * @property int $id
 * @property int $parent_id
 * @property int $author_id
 * @property int $created_at
 * @property int $attachments
 * @property string $subject
 * @property string $body
 * @property string $params
 */
class KunenaPrivateMessage extends KunenaDatabaseObject
{
	protected $_table = 'KunenaPrivate';

	public function __construct($properties = null)
	{
		if (!empty($this->id))
		{
			$this->_exists = true;
		}
		else
		{
			parent::__construct($properties);
		}
	}

	/**
	 * @param string $field
	 *
	 * @return int|string
	 */
	public function displayField($field) {
		switch ($field) {
			case 'id':
				return intval($this->id);
			case 'subject':
				return KunenaHtmlParser::parseText($this->subject);
			case 'body':
				return KunenaHtmlParser::parseBBCode($this->body, $this);
		}
		return '';
	}
}
