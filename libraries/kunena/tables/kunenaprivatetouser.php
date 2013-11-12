<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Tables
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

require_once(__DIR__ . '/kunena.php');

/**
 * Kunena Private Message receivers: User
 * Provides access to the #__kunena_private_to_user table
 */
class TableKunenaPrivateToUser extends KunenaTable
{
	public $id = null;
	public $user_id = null;
	public $read_at = null;
	public $replied_at = null;
	public $deleted_at = null;

	public function __construct($db)
	{
		parent::__construct('#__kunena_private_to_user', 'id', $db);
	}
}
