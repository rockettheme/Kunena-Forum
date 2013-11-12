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
 * Private message receiver: forum.
 *
 * @property int $id
 * @property int $private_id
 * @property int $category_id
 * @property int $topic_id
 * @property int $message_id
 * @property string $params
 */
class KunenaPrivateMessageToForum extends KunenaDatabaseObject {
	protected $_table = 'KunenaPrivateToForum';
}
