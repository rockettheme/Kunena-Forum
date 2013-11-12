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
 * Kunena Private Message receivers: Forum
 * Provides access to the #__kunena_private_to_forum table
 */
class TableKunenaPrivateToForum extends KunenaTable
{
	public $id = null;
	public $category_id = null;
	public $topic_id = null;
	public $message_id = null;
	public $params = null;

	public function __construct($db)
	{
		parent::__construct('#__kunena_private_to_forum', 'id', $db);
	}
}
