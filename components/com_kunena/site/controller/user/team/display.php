<?php
/**
 * Kunena Component
 * @package     Kunena.Site
 * @subpackage  Controller.User
 *
 * @copyright   (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.kunena.org
 **/
defined('_JEXEC') or die;

/**
 * Class ComponentKunenaControllerUserTeamDisplay
 *
 * @since  3.1
 */
class ComponentKunenaControllerUserTeamDisplay extends KunenaControllerDisplay
{
    protected $name = 'User/Team';

    public $state;

    public $me;

    public $total;

    public $users;

    public $pagination;

    /**
     * Load user team.
     *
     * @return void
     */
    protected function before()
    {
        parent::before();

        require_once KPATH_SITE . '/models/user.php';
        $this->model = new KunenaModelUser(array(), $this->input);
        $this->model->initialize($this->getOptions(), $this->getOptions()->get('embedded', false));
        $this->state = $this->model->getState();

        $this->me = KunenaUserHelper::getMyself();
        $this->config = KunenaConfig::getInstance();

        $start = $this->state->get('list.start');
        $limit = $this->state->get('list.limit');

        // Exclude super admins.
        $superadmins = JAccess::getUsersByGroup(8);

        $finder = new KunenaUserFinder;
        $finder
            //->filterByConfiguration($superadmins)
            ->filterBy('moderator', '=', 1);

        $this->total = $finder->count();

        $this->users = $finder
            ->order('registerDate')
            ->limit(-1)
            ->find();

        $this->users = $this->_cleanUsers($this->users);
    }

    /**
     * Prepare document.
     *
     * @return void
     */
    protected function prepareDocument()
    {
        $title = JText::_('COM_KUNENA_VIEW_USER_LIST') . $pagesText;
        $this->setTitle($title);
    }

    protected function _cleanUsers(){
        $users = ['kahuna' => [], 'devs' => [], 'mods' => []];

        foreach($this->users as $user){
            if (stripos($user->getName(), 'rocke') !== false || stripos($user->getName(), 'admin') !== false) continue;

            $rank = strtolower($user->getRank(0, 'title'));
            $user->rank = $rank;

            if ($user->getName() == 'Andy Miller') $users['kahuna'][] = $user;
            else if ($user->rank == 'moderator') $users['mods'][] = $user;
            else $users['devs'][] = $user;
        }

        return $users;
    }
}
