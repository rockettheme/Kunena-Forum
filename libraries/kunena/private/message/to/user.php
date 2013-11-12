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
 * Private message receiver: user.
 *
 * @property int $id
 * @property int $private_id
 * @property int $user_id
 * @property string $read_at
 * @property string $replied_at
 * @property string $deleted_at
 */
class KunenaPrivateMessageToUser extends KunenaDatabaseObject {
	protected $_table = 'KunenaPrivateToUser';
}
