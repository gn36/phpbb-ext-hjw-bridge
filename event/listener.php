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
			'gn36.hookup.viewtopic.process_status'	=> 'sync_participant_hookup',
			'hjw.calendar.viewtopic.modify_participants_list' => 'sync_participant_calendar',
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

	/** @var string */
	protected $cal_participants_table;

	/** @var array */
	protected $part_ary = array(hookup::HOOKUP_YES => 'yes', hookup::HOOKUP_NO => 'no', hookup::HOOKUP_MAYBE => 'mb');

	/** @var array */
	protected $part_ary_inv = array('yes' => hookup::HOOKUP_YES, 'no' => hookup::HOOKUP_NO, 'mb' => hookup::HOOKUP_MAYBE);

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
	 function __construct(\gn36\hookup\functions\hookup $hookup, \phpbb\template\template $template, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\request\request_interface $request, \phpbb\event\dispatcher_interface $phpbb_dispatcher, \phpbb\notification\manager $notification_manager, $phpbb_root_path, $phpEx, $hookup_path, $cal_table, $cal_event_table, $cal_participants_table)
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
		$this->cal_participants_table = $cal_participants_table;
	}

	public function activedate_set(\Symfony\Component\EventDispatcher\Event $event)
	{
		$topic_data = $event['topic_data'];
		$first_post = intval($topic_data['topic_first_post_id']);

		if (0 == $event['set_active'])
		{
			$sql = 'DELETE FROM ' . $this->cal_table . ' WHERE post_id = ' . $first_post;
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

			$set_date = isset($this->hookup->hookup_dates[$event['set_active']]) ? (isset($this->hookup->hookup_dates[$event['set_active']]['date_time']) ? $this->hookup->hookup_dates[$event['set_active']]['date_time'] : 0) : 0;
			if (!$set_date)
			{
				// We can't enter a text without date
				return;
			}

			// Quick & dirty: The event
			$sql = 'SELECT id FROM ' . $this->cal_event_table . ' WHERE event = \'hookup\'';
			$result = $this->db->sql_query_limit($sql, 1);
			$event_id = $this->db->sql_fetchfield('id');
			$this->db->sql_freeresult($result);

			if (!$event_id)
			{
				$sql_ary = array(
					'event'			=> 'hookup',
					'participants' 	=> 1,
				);
				$sql = 'INSERT INTO ' . $this->cal_event_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);

				$this->db->sql_query($sql);
				$event_id = $this->db->sql_nextid();
			}

			$sql = 'SELECT count(*) as cnt FROM ' . $this->cal_table . ' WHERE post_id = ' . $topic_data['topic_first_post_id'];
			$result = $this->db->sql_query($sql);
			$cnt = $this->db->sql_fetchfield('cnt');
			$this->db->sql_freeresult($result);

			$sql_ary = array(
				'post_id' 		=> $topic_data['topic_first_post_id'],
				'event_id' 		=> $event_id,
				'event_name' 	=> $topic_data['topic_title'],
				'date_from' 	=> date('Y-m-d', $set_date),
			);
			if ($cnt)
			{
				$sql = 'UPDATE ' . $this->cal_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE post_id = ' . $topic_data['topic_first_post_id'];
			}
			else
			{
				$sql = 'INSERT INTO ' . $this->cal_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			}
			$this->db->sql_query($sql);

			// Participants
			$part_ary = $this->part_ary;

			// Already entered?
			$entered_users = array();
			if ($cnt)
			{
				$sql = 'SELECT user_id FROM ' . $this->cal_participants_table . ' WHERE ' . $this->db->sql_in_set('user_id', array_keys($this->hookup->hookup_users));
				$result = $this->db->sql_query($sql);
				$entered_users = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
			}

			$sql_ary = array();
			foreach ($this->hookup->hookup_users as $user_id => $userdata)
			{
				// Did the user enter anything for this date?
				if (!isset($this->hookup->hookup_availables[$user_id][$event['set_active']]) || $this->hookup->hookup_availables[$user_id][$event['set_active']] == hookup::HOOKUP_UNSET)
				{
					continue;
				}

				if (in_array($user_id, $entered_users))
				{
					// Update instead:
					$sql = 'UPDATE ' . $this->cal_participants_table . ' SET ' . $this->db->sql_build_array('UPDATE', array(
						'participants' 	=> $part_ary[$this->hookup->hookup_availables[$user_id][$event['set_active']]],
						'comment'		=> $userdata['comment'],
						'date'			=> date('Y-m-d-H-i'),
					)) . " WHERE user_id = $user_id AND post_id = {$topic_data['topic_first_post_id']}";
					$this->db->sql_query($sql);
					continue;
				}

				$sql_ary[] = array(
					'post_id' 		=> $topic_data['topic_first_post_id'],
					'user_id' 		=> $user_id,
					'participants' 	=> $part_ary[$this->hookup->hookup_availables[$user_id][$event['set_active']]],
					'comments'		=> $userdata['comment'],
					'date'			=> date('Y-m-d-H-i'),
				);
			}

			$this->db->sql_multi_insert($this->cal_participants_table, $sql_ary);
		}
	}

	/**
	 * Synchronizes new participant entries from hookup to calendar
	 *
	 * @param \Symfony\Component\EventDispatcher\Event $event
	 * @throws \Exception
	 */
	public function sync_participant_hookup(\Symfony\Component\EventDispatcher\Event $event)
	{
		$topic_data = $event['topic_data'];

		if (!$event['active_date'])
		{
			die('ACT');
			return;
		}

		$availables = $event['availables'];

		if (!isset($availables[$event['active_date']]) || !in_array($availables[$event['active_date']], $this->part_ary_inv))
		{
			die('missing');
			return;
		}

		// Check if we have a calendar entry for the first post:
		$sql = 'SELECT post_id FROM ' . $this->cal_table . ' WHERE post_id = ' . (int) $topic_data['topic_first_post_id'];
		$result = $this->db->sql_query($sql);
		$cal_exists = $this->db->sql_fetchfield('post_id');
		$this->db->sql_freeresult($result);

		if (!$cal_exists)
		{
			echo "post: " . $event['topic_first_post_id'];
			die('CAL');
			return;
		}
		$user_id = $this->user->data['user_id'];

		$sql_ary = array(
			'POST_ID'		=> (int) $topic_data['topic_first_post_id'],
			'USER_ID'		=> $user_id,
			'NUMBER'		=> 1,
			'PARTICIPANTS'	=> $this->part_ary[$availables[$event['active_date']]],
			'COMMENTS'		=> utf8_normalize_nfc($event['comment']),
			'DATE'			=> date("Y-n-j-H-i"),
		);

		// Check, whether user is already entered:
		$sql='SELECT * from ' . $this->cal_participants_table . "
					WHERE post_id = '" . $sql_ary['POST_ID'] ."' and user_id = '" . $user_id."'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Enter data:
		if ($row)
		{
			$sql = 'UPDATE ' . $this->cal_participants_table . '
				SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . "
				WHERE post_id = '" . $sql_ary['POST_ID'] ."' and user_id = '" . $user_id."'";
		}
		else
		{
			$sql = 'INSERT INTO ' . $this->cal_participants_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		}
		$result = $this->db->sql_query($sql);
	}


	/**
	 * Synchronizes new participant entries from calendar to hookup
	 *
	 * @param \Symfony\Component\EventDispatcher\Event $event
	 * @throws \Exception
	 */
	public function sync_participant_calendar(\Symfony\Component\EventDispatcher\Event $event)
	{
		$sql_data = $event['sql_ary'];
		$post_id = (int) $sql_data['POST_ID'];

		// Retrieve Hookup for that post:
		$sql = 'SELECT p.topic_id, t.forum_id, t.topic_first_post_id FROM ' . POSTS_TABLE . ' p LEFT JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id WHERE post_id = ' . $post_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || !$this->hookup->load_hookup($row['topic_id']))
		{
			throw new \Exception('Loading topic or hookup failed. Post ID: ' . $post_id);
		}

		if (!$this->hookup->hookup_enabled || $post_id != $row['topic_first_post_id'])
		{
			// Don't sync if the hookup is disabled or the event is not linked to the first post of the topic
			return;
		}

		// Do not enter, if the user is not permitted to use the hookup
		// Ignore permissions, if called for user_id different from current user
		if ($event['user_id'] == $this->user->data['user_id'] && !$this->auth->acl_get('f_hookup', $row['forum_id']))
		{
			return;
		}

		// Don't sync if no activedate is set
		if (!$this->hookup->hookup_active_date)
		{
			return;
		}

		$this->hookup->add_user($sql_data['USER_ID'], $sql_data['COMMENTS']);
		$this->hookup->set_user_date($sql_data['USER_ID'], $this->hookup->hookup_active_date, $this->part_ary_inv[$sql_data['PARTICIPANTS']]);
		$this->hookup->submit(false);

	}
}
