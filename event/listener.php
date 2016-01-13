<?php

/**
 *
 * @package gn36/hjw_bridge
 * @copyright (c) 2016 gn#36
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace gn36\hjw_bridge\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use gn36\hookup\functions\hookup;

class listener implements EventSubscriberInterface
{

	/**
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'gn36.hookup.set_activedate_confirmed'	=> 'activedate_set',
		);
	}

	/** @var \gn36\hookup\functions\hookup */
	protected $hookup;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\notification\manager */
	protected $notification_manager;

	/** @var \phpbb\event\dispatcher_interface */
	protected $phpbb_dispatcher;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $phpEx;

	/** @var string */
	protected $hookup_path;

	/** @var string */
	protected $cal_table;

	/** @var string */
	protected $cal_event_table;

	/**
	 * Constructor
	 *
	 * @param \gn36\hookup\functions\hookup $hookup
	 * @param \phpbb\template\template $template
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\user $user
	 * @param \phpbb\auth\auth $auth
	 * @param \phpbb\request\request_interface $request
	 * @param \phpbb\event\dispatcher_interface $phpbb_dispatcher
	 * @param \phpbb\notification\manager $notification_manager
	 * @param string $phpbb_root_path
	 * @param string $phpEx
	 * @param string $hookup_path
	 */
	 function __construct(\gn36\hookup\functions\hookup $hookup, \phpbb\template\template $template, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\request\request_interface $request, \phpbb\event\dispatcher_interface $phpbb_dispatcher, \phpbb\notification\manager $notification_manager, $phpbb_root_path, $phpEx, $hookup_path, $cal_table, $cal_event_table)
	 {
		$this->hookup = $hookup;
		$this->template = $template;
		$this->db = $db;
		$this->user = $user;
		$this->auth = $auth;
		$this->request = $request;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->hookup_path = $hookup_path;
		$this->phpbb_dispatcher = $phpbb_dispatcher;
		$this->notification_manager = $notification_manager;
		$this->cal_table = $cal_table;
		$this->cal_event_table = $cal_event_table;
	}

	public function activedate_set($event)
	{
		$topic_data = $event['topic_data'];
		$first_post = intval($topic_data['topic_first_post_id']);

		if (0 == $event['active_date'])
		{
			// Reset
			$sql = 'SELECT event_id FROM ' . $this->cal_table . ' WHERE post_id = ' . $first_post;

			$result = $this->db->sql_query_limit($sql, 1);
			$event_id = $this->db->sql_fetchfield('event_id');
			$this->db->sql_freeresult($result);
			if (!$event_id)
			{
				// No event entered, simply skip
				return;
			}

			$sql = 'DELETE FROM ' . $this->cal_table . ' WHERE event_id = ' . $event_id;
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->cal_event_table . ' WHERE id = ' . $event_id;
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->cal_participants_table . ' WHERE post_id = ' . $first_post;
			$this->db->sql_query($sql);
		}
		else
		{
			// Copy Date & entries
			if ($this->hookup->topic_id != $event['topic_id'])
			{
				if ($this->hookup->topic_id != 0)
				{
					$this->hookup = new hookup();
				}
				$this->hookup->load_hookup($event['topic_id']);
			}
			//TODO Fortsetzen
		}
	}
}
