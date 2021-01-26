<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Forum.Message.Attachment
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Class KunenaForumMessageAttachment
 *
 * @property int $id
 * @property int $userid
 * @property int $mesid
 * @property int $protected
 * @property string $hash
 * @property int $size
 * @property string $folder
 * @property string $filetype
 * @property string $filename
 * @property string $filename_real
 * @property string $caption
 */
class KunenaForumMessageAttachment extends JObject {
	// Higher protection level means that the attachment is visible to less people.
	// Protection level can be checked as bitmask: PROTECTION_ACL + PROTECTION_FRIENDS.
	// To filter out attachments when doing a database query, you can use:
	// Visible for author = value < PROTECTION_AUTHOR * 2
	// TODO: Implement these
	const PROTECTION_NONE = 0;
	const PROTECTION_PUBLIC = 1;
	const PROTECTION_ACL = 2;
	const PROTECTION_FRIENDS = 4;
	const PROTECTION_MODERATORS = 8;
	const PROTECTION_ADMINS = 16;
	const PROTECTION_PRIVATE = 32;
	const PROTECTION_AUTHOR = 64;
	const PROTECTION_UNPUBLISHED = 128;

	protected $_exists = false;
	protected $_db = null;
	protected $_shortname = null;
	protected $_shorttime = null;
	protected $_textLink = null;
	protected $_imagelink = null;
	protected $_thumblink = null;
	/**
	 * @var bool
	 */
	public $disabled = false;

	protected static $_directory = 'media/kunena/attachments';
	protected static $actions  = array(
		'read'=>array('Read'),
		'create'=>array(),
		'delete'=>array('Exists', 'Own'),
	);

	/**
	 * @param int $identifier
	 *
	 * @internal
	 */
	public function __construct($identifier = 0) {
		// Always load the attachment -- if attachment does not exist: fill empty data
		$this->_db = JFactory::getDBO ();
		$this->load ( $identifier );
	}

	public function __destruct() {
		if (!$this->exists()) {
			$this->deleteFile();
		}
	}

	/**
	 * @param mixed $identifier
	 * @param bool $reload
	 *
	 * @return KunenaForumMessageAttachment
	 */
	static public function getInstance($identifier = null, $reload = false) {
		return KunenaForumMessageAttachmentHelper::get($identifier, $reload);
	}

	/**
	 * @param null|bool $exists
	 *
	 * @return bool
	 */
	function exists($exists = null) {
		$return = $this->_exists;
		if ($exists !== null) $this->_exists = $exists;
		return $return;
	}

	/**
	 * @param string $mime
	 *
	 * @return bool
	 */
	function isImage($mime = null) {
		if (is_null($mime)) $mime = $this->filetype;

		return (stripos ( $mime, 'image/' ) !== false);
	}

	/**
	 * @return string
	 */
	function getTextLink() {
		$this->generate();
		return $this->_textLink;
	}

	/**
	 * @return string
	 */
	function getImageLink() {
		$this->generate();
		return $this->_imagelink;
	}

	/**
	 * @return string
	 */
	function getThumbnailLink() {
		$this->generate();
		return $this->_thumblink;
	}

	public function getFilename($escape = true) {
		$filename = $this->protected ? $this->filename_real : $this->filename;
		return $escape ? $this->escape($filename) : $filename;
	}

	public function getUrl($thumb = false, $inline = true) {
		$protect = (bool) KunenaConfig::getInstance()->attachment_protection;
		// Use direct URLs to the attachments if protection is turned off and file wasn't protected.
		if (!$protect && !$this->protected) {
			$file = $this->folder . '/' . $this->filename;
			$thumbfile = $this->folder . '/thumb/' . $this->filename;
			if (!is_file(JPATH_ROOT . '/' . $thumbfile)) {
				$thumbfile = $file;
			}
			return JUri::root(true) .'/'. $this->escape($thumb ? $thumbfile : $file);
		}

		// Route attachment through Kunena.
		$thumb = $thumb ? '&thumb=1' : '';
		$download = $inline ? '' : '&download=1';
		return KunenaRoute::_("index.php?option=com_kunena&view=attachment&id={$this->id}{$thumb}{$download}&format=raw");
	}

	protected function generate() {
		if (!isset($this->_shortname)) {
			$this->_shortname = KunenaForumMessageAttachmentHelper::shortenFileName($this->getFilename(false));

			$config = KunenaFactory::getConfig();
			$this->_isImage = (stripos($this->filetype, 'image/') !== false);
			if ($this->_isImage) {
				// Check for thumbnail and if available, use for display
				$thumbUrl = $this->getUrl(true);
				$imageUrl = $this->getUrl();
				if (is_file(JPATH_ROOT . '/' . $this->folder . '/thumb/' . $this->filename)) {
					$imgsize = '';
				} else {
					$imgsize = 'width="' . $config->thumbwidth . 'px" height="' . $config->thumbheight . 'px"';
				}

				$img = '<img title="' . $this->getFilename() . '" ' . $imgsize . ' src="' . $thumbUrl . '" alt="' . $this->getFilename() . '" />';
				$this->_thumblink = $this->_getAttachementLink($imageUrl, $img, $this->getFilename(), ($config->lightbox)? true : false);

				$img = '<img title="' . $this->getFilename() . '" src="' . $imageUrl . '" alt="' . $this->getFilename() . '" />';
				$this->_imagelink = $this->_getAttachementLink($imageUrl, $img, $this->getFilename(), ($config->lightbox)? true : false);

				$this->_textLink = $this->_getAttachementLink($imageUrl, $this->escape($this->_shortname), $this->getFilename(), ($config->lightbox)? true : false, 'nofollow') . ' (' . number_format(intval($this->size) / 1024, 0, '', ',') . 'KB)';

			} else {
				$fileUrl = $this->getUrl();
				// Filetype without thumbnail or icon support - use default file icon
				$img = '<img src="' . JUri::root(true). '/media/kunena/images/attach_generic.png" alt="' . JText::_ ( 'COM_KUNENA_ATTACH' ) . '" />';
				$this->_thumblink = $this->_getAttachementLink($fileUrl, $img, $this->getFilename(), false, 'nofollow');
				$this->_textLink = $this->_getAttachementLink ($fileUrl, $this->escape($this->_shortname), $this->getFilename(), false, 'nofollow') . ' (' . number_format(intval($this->size) / 1024, 0, '', ',') . 'KB)';
			}

			$this->disabled = false;
			if (! KunenaUserHelper::getMyself()->exists()) {
				if ($this->_isImage && !$config->showimgforguest) {
					$this->disabled = true;
					$this->_textLink = JText::_ ( 'COM_KUNENA_SHOWIMGFORGUEST_HIDEIMG' );
				}
				if (!$this->_isImage && !$config->showfileforguest) {
					$this->disabled = true;
					$this->_textLink = JText::_ ( 'COM_KUNENA_SHOWIMGFORGUEST_HIDEFILE' );
				}
				if ($this->disabled) {
					$this->_thumblink = '<img src="' . JUri::root() .'media/kunena/images/attach_generic.png" alt="' . JText::_ ( 'COM_KUNENA_ATTACH' ) . '" />';
					$this->_imagelink = null;
					$this->size = 0;
				}
			}
		}
	}

	/**
	 * @return KunenaForumMessage
	 */
	public function getMessage() {
		return KunenaForumMessageHelper::get($this->mesid);
	}

	/**
	 * @return KunenaUser
	 */
	public function getAuthor() {
		return KunenauserHelper::get($this->userid);
	}

	/**
	 * Returns true if user is authorised to do the action.
	 *
	 * @param string     $action
	 * @param KunenaUser $user
	 *
	 * @return bool
	 *
	 * @since 3.1
	 */
	public function isAuthorised($action='read', KunenaUser $user = null) {
		return !$this->tryAuthorise($action, $user, false);
	}

	/**
	 * Throws an exception if user isn't authorised to do the action.
	 *
	 * @param string      $action
	 * @param KunenaUser  $user
	 * @param bool        $throw
	 *
	 * @return KunenaExceptionAuthorise|null
	 * @throws KunenaExceptionAuthorise
	 * @throws InvalidArgumentException
	 *
	 * @since 3.1
	 */
	public function tryAuthorise($action='read', KunenaUser $user = null, $throw = true) {
		// Special case to ignore authorisation.
		if ($action == 'none') {
			return null;
		}

		// Load user if not given.
		if ($user === null) {
			$user = KunenaUserHelper::getMyself();
		}

		// Unknown action - throw invalid argument exception.
		if (!isset(self::$actions[$action])) {
			throw new InvalidArgumentException(JText::sprintf('COM_KUNENA_LIB_AUTHORISE_INVALID_ACTION', $action), 500);
		}

		// Start by checking if attachment is protected.
		$exception = !$this->protected ? null : new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), $user->id ? 403 : 401);

		// TODO: Add support for PROTECTION_PUBLIC

		// Currently we only support ACL checks, not public attachments.
		if ($exception && $this->mesid && $this->protected & (self::PROTECTION_PUBLIC + self::PROTECTION_ACL))
		{
			// Load message authorisation.
			$exception = $this->getMessage()->tryAuthorise('attachment.'.$action, $user, false);
		}

		// TODO: Add support for PROTECTION_FRIENDS
		// TODO: Add support for PROTECTION_MODERATORS
		// TODO: Add support for PROTECTION_ADMINS

		// Check if attachment is private.
		if ($exception && $this->protected & self::PROTECTION_PRIVATE)
		{
			$exception = $this->authorisePrivate($user);
		}

		// Check author access.
		if ($exception && $this->protected & self::PROTECTION_AUTHOR)
		{
			$exception = $user->exists() && $user->id == $this->userid
				? null : new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), $user->userid ? 403 : 401);
		}

		if ($exception) {
			// Hide original exception behind no access.
			$exception = new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), $user->userid ? 403 : 401, $exception);
		} else {
			// Check authorisation action.
			foreach (self::$actions[$action] as $function) {
				$authFunction = 'authorise'.$function;
				$exception = $this->$authFunction($user);
				if ($exception) break;
			}
		}

		// Throw or return the exception.
		if ($throw && $exception) throw $exception;
		return $exception;
	}

	/**
	 * @param string $action
	 * @param mixed  $user
	 * @param bool   $silent
	 *
	 * @return bool
	 * @deprecated 3.1
	 */
	public function authorise($action='read', $user=null, $silent=false) {
		KUNENA_PROFILER ? KunenaProfiler::instance()->start('function '.__CLASS__.'::'.__FUNCTION__.'()') : null;

		if ($user === null) {
			$user = KunenaUserHelper::getMyself();
		} elseif (!($user instanceof KunenaUser)) {
			$user = KunenaUserHelper::get($user);
		}

		$exception = $this->tryAuthorise($action, $user, false);
		if ($silent === false && $exception) $this->setError($exception->getMessage());

		KUNENA_PROFILER ? KunenaProfiler::instance()->stop('function '.__CLASS__.'::'.__FUNCTION__.'()') : null;
		if ($silent !== null) return !$exception;
		return $exception ? $exception->getMessage() : null;
	}

	/**
	 * @param string $key
	 * @param null|int   $catid
	 *
	 * @return bool
	 */
	function upload($key='kattachment', $catid=null) {
		require_once (KPATH_SITE . '/lib/kunena.upload.class.php');
		$path = JPATH_ROOT . '/media/kunena/attachments/' . $this->userid;
		$upload = new CKunenaUpload($catid);
		$protection = (bool) KunenaConfig::getInstance()->attachment_protection;
		$filename = $protection ? null : '';
		$upload->uploadFile($path, $key, $filename, false);
		$fileinfo = $upload->getFileInfo();

		if ($fileinfo ['status'] && $fileinfo['ready'] === true) {
			$this->protected = (int) $protection;
			$this->hash =$fileinfo ['hash'];
			$this->size = $fileinfo ['size'];
			$this->folder = 'media/kunena/attachments/' . $this->userid;
			$this->filetype = $fileinfo ['mime'];
			$this->filename = $fileinfo ['name'];
			$this->filename_real = $fileinfo ['real'];
			$this->caption = '';
			return true;
		}
		$this->setError( JText::sprintf ( 'COM_KUNENA_UPLOAD_FAILED', $fileinfo ['name'] ) . ': ' . $fileinfo ['error'] );
		return false;
	}

	/**
	 * Set attachment file.
	 *
	 * Copies the attachment into proper location and makes sure that all the unset fields get properly assigned.
	 *
	 * @param  string  $source     Absolute path to the upcoming attachment.
	 * @param  string  $basename   Filename without extension.
	 * @param  string  $extension  File extension.
	 * @param  bool    $unlink     Whether to delete the original file or not.
	 * @param  bool    $overwrite  If not allowed, throw exception if the file exists.
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @since 3.1
	 */
	public function saveFile($source, $basename = null, $extension = null, $unlink = false, $overwrite = false)
	{
		if (!is_file($source))
		{
			throw new InvalidArgumentException(__CLASS__.'::'.__METHOD__.'(): Attachment file not found.');
		}

		// Hash, size and MIME are set during saving, so let's deal with all other variables.
		$this->userid = is_null($this->userid) ? KunenaUserHelper::getMyself() : $this->userid;
		$this->folder = is_null($this->folder) ? "media/kunena/attachments/{$this->userid}" : $this->folder;
		$this->protected = is_null($this->protected) ? (KunenaConfig::getInstance()->attachment_protection ? static::PROTECTION_AUTHOR : static::PROTECTION_NONE) : $this->protected;

		if (!$this->filename_real)
		{
			$this->filename_real = $this->filename;
		}

		if (!$this->filename || $this->filename == $this->filename_real)
		{
			if (!$basename || !$extension)
			{
				throw new InvalidArgumentException(__CLASS__.'::'.__METHOD__.'(): Parameters $basename or $extension not provided.');
			}

			// Find available filename.
			$this->filename = KunenaForumMessageAttachmentHelper::getAvailableFilename(
				$this->folder, $basename, $extension, $this->protected
			);
		}

		// Create target directory if it does not exist.
		if (!KunenaFolder::exists(JPATH_ROOT . "/{$this->folder}") && !KunenaFolder::create(JPATH_ROOT . "/{$this->folder}"))
		{
			throw new RuntimeException(JText::_('Failed to create attachment directory.'));
		}

		$destination = JPATH_ROOT . "/{$this->folder}/{$this->filename}";

		// Move the file into the final location (if not already in there).
		if ($source != $destination)
		{
			// Create target directory if it does not exist.
			if (!$overwrite && is_file($destination))
			{
				throw new RuntimeException(JText::sprintf('Attachment %s already exists.'), $this->filename_real);
			}

			if ($unlink)
			{
				@chmod($source, 0644);
			}

			$success = KunenaFile::copy($source, $destination);

			if (!$success)
			{
				throw new RuntimeException(JText::sprintf('COM_KUNENA_UPLOAD_ERROR_NOT_MOVED', $destination));
			}

			KunenaPath::setPermissions($destination);

			if ($unlink)
			{
				unlink($source);
			}
		}

		return $this->save();
	}

	/**
	 *  Method to get the table object.
	 *
	 * @param string $type		The messages table name to be used.
	 * @param string $prefix	The messages table prefix to be used.
	 *
	 * @return KunenaTable
	 */
	public function getTable($type = 'KunenaAttachments', $prefix = 'Table') {
		static $tabletype = null;

		//Set a custom table type is defined
		if ($tabletype === null || $type != $tabletype ['name'] || $prefix != $tabletype ['prefix']) {
			$tabletype ['name'] = $type;
			$tabletype ['prefix'] = $prefix;
		}

		// Create the user table object
		return JTable::getInstance ( $tabletype ['name'], $tabletype ['prefix'] );
	}

	/**
	 * @param array $data
	 * @param array $ignore
	 */
	public function bind(array $data, array $ignore = array()) {
		$data = array_diff_key($data, array_flip($ignore));
		$this->setProperties ( $data );
	}

	/**
	 * Method to load a KunenaForumMessageAttachment object by id.
	 *
	 * @param int $id	The message id to be loaded.
	 *
	 * @return bool	True on success.
	 */
	public function load($id) {
		// Create the table object
		$table = $this->getTable ();

		// Load the KunenaTable object based on id
		$this->_exists = $table->load ( $id );

		// Assuming all is well at this point lets bind the data
		$this->setProperties ( $table->getProperties () );
		return $this->_exists;
	}

	/**
	 * Method to save the object to the database.
	 *
	 * @param bool $updateOnly	Save the object only if not a new message.
	 *
	 * @return bool|null	True on success.
	 */
	public function save($updateOnly = false) {
		// Do not save altered message
		if ($this->disabled) return null;

		// Create the messages table object
		$table = $this->getTable ();
		$table->bind ( $this->getProperties () );
		$table->exists ( $this->_exists );

		if ($this->getError()) {
			return false;
		}
		// Check and store the object.
		if (! $table->check ()) {
			$this->setError ( $table->getError () );
			return false;
		}

		//are we creating a new message
		$isnew = ! $this->_exists;

		// If we aren't allowed to create new message return
		if ($isnew && $updateOnly) {
			return true;
		}

		//Store the message data in the database
		if (! $result = $table->store ()) {
			$this->setError ( $table->getError () );
		}

		// Set the id for the KunenaForumMessageAttachment object in case we created a new message.
		if ($result && $isnew) {
			$this->load ( $table->get ( 'id' ) );
			$this->_exists = true;
		}

		return $result;
	}

	/**
	 * Method to delete this object from the database.
	 *
	 * @return bool	True on success.
	 */
	public function delete() {
		if (!$this->exists()) {
			return true;
		}

		// Create the table object
		$table = $this->getTable ();

		$result = $table->delete ( $this->id );
		if (! $result) {
			$this->setError ( $table->getError () );
			return false;
		}
		$this->_exists = false;

		return $result;
	}

	// Internal functions

	/**
	 * @param KunenaUser $user
	 *
	 * @return RuntimeException|null
	 */
	protected function authorisePrivate(KunenaUser $user)
	{
		if (!$user->exists())
		{
			return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 401);
		}

		// Need to load private message (for now allow only one private message per attachment).
		$map = JTable::getInstance('KunenaPrivateAttachmentMap', 'Table');
		$map->load(array('attachment_id' => $this->id));

		$finder = new KunenaPrivateMessageFinder();
		$private = $finder->where('id', '=', $map->private_id)->firstOrNew();

		if (!$private->exists())
		{
			return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 403);
		}

		if (in_array($user->userid, $private->users()->getMapped()))
		{
			// Yes, I have access..
			return null;
		}
		else
		{
			$messages = KunenaForumMessageHelper::getMessages($private->posts()->getMapped());

			foreach ($messages as $message)
			{
				if ($user->isModerator($message->getCategory()))
				{
					// Yes, I have access..
					return null;
				}
			}
		}

		return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 403);
	}

	protected function check() {
		//$author = KunenaUserHelper::get($this->userid);
	}

	protected function deleteFile() {
		if (self::$_directory != substr($this->folder, 0, strlen(self::$_directory)))
			return;
		$path = JPATH_ROOT."/{$this->folder}";
		$filetoDelete = $path.'/'.$this->filename;
		if (is_file($filetoDelete)) {
			KunenaFile::delete($filetoDelete);
		}
		$filetoDelete = $path.'/raw/'.$this->filename;
		if (is_file($filetoDelete)) {
			KunenaFile::delete($filetoDelete);
		}
		$filetoDelete = $path.'/thumb/'.$this->filename;
		if (is_file($filetoDelete)) {
			KunenaFile::delete($filetoDelete);
		}
	}

	/**
	 * @param string $var
	 *
	 * @return string
	 */
	protected function escape($var) {
		return htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * @param KunenaUser $user
	 *
	 * @return KunenaExceptionAuthorise|null
	 */
	protected function authoriseExists(KunenaUser $user) {
		// Checks if attachment exists
		if (!$this->exists()) {
			return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 404);
		}
		return null;
	}

	/**
	 * @param KunenaUser $user
	 *
	 * @return KunenaExceptionAuthorise|null
	 */
	protected function authoriseRead(KunenaUser $user) {
		// Checks if attachment exists
		if (!$this->exists()) {
			return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 404);
		}
		// FIXME: authorisation for guests is missing, but needs a few changes in the code in order to work.
		/*
		if (!$user->exists()) {
			$config = KunenaConfig::getInstance();
			$this->generate();
			if ($this->_isImage && !$config->showimgforguest) {
				return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_SHOWIMGFORGUEST_HIDEIMG'), 401);
			}
			if (!$this->_isImage && !$config->showfileforguest) {
				return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_SHOWIMGFORGUEST_HIDEFILE'), 401);
			}
		}
		*/
		return null;
	}

	/**
	 * @param KunenaUser $user
	 *
	 * @return KunenaExceptionAuthorise|null
	 */
	protected function authoriseOwn(KunenaUser $user) {
		// Checks if attachment is users own or user is moderator in the category (or global)
		if (($user->userid && $this->userid != $user->userid) && !$user->isModerator($this->getMessage()->getCategory())) {
			return new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 403);
		}
		return null;
	}

	/**
	 * @param string $path
	 * @param string $name
	 * @param string $title
	 * @param string $rel
	 *
	 * @return string
	 */
	protected function _getAttachementLink($link, $name, $title = '', $data = false, $rel = 'nofollow') {
		return '<a href="'.$link.'" title="'.$title.'" rel="'.$rel.'"'.($data ? ' data-modal' : '').'>'.$name.'</a>';
	}
}
