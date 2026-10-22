<?php
/**
 *
 * Print All Posts extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @copyright (c) 2015, Rich McGirr (RMcGirr83)
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\printallposts\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Print All Posts event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var printallposts */
	protected $printallposts = false;

	/** @var printallposts_view */
	protected $printallposts_view = '';

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth          $auth
	 * @param \phpbb\config\config      $config
	 * @param \phpbb\language\language  $language
	 * @param \phpbb\request\request    $request
	 * @param \phpbb\template\template  $template
	 *
	 * @access public
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
	}

	/**
	 * Map phpBB core events to the listener methods that should handle those events
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.viewtopic_assign_template_vars_before'	=> 'viewtopic_assign_template_vars_before',
			'core.viewtopic_before_f_read_check'			=> 'viewtopic_before_f_read_check',
			'core.viewtopic_highlight_modify'				=> 'viewtopic_highlight_modify',
			'core.viewtopic_modify_page_title'				=> 'viewtopic_modify_page_title',
		];
	}

	/**
	 * Perform auth check
	 *
	 * @param \phpbb\event\data $event
	 * @access public
	 */
	public function viewtopic_before_f_read_check($event)
	{
		$this->printallposts_view = $this->request->variable('view', '');

		$this->language->add_lang('printallposts', 'phpbbmodders/printallposts');

		// Is the user allowed to print posts?
		if ($this->auth->acl_get('f_print', (int) $event['forum_id']))
		{
			$this->printallposts = true;
		}
		else if ($this->printallposts_view == 'printall' && !$this->auth->acl_get('f_print', (int) $event['forum_id']))
		{
			send_status_line(403, $this->language->lang('FORBIDDEN'));

			trigger_error('NO_AUTH_PRINT_TOPIC');
		}
	}

	/**
	 * Change start and config vars
	 *
	 * @param \phpbb\event\data $event
	 * @access public
	 */
	public function viewtopic_highlight_modify($event)
	{
		if ($this->printallposts_view == 'printall')
		{
			$event['start'] = '0';

			$this->config->offsetSet('posts_per_page', (int) $event['total_posts']);
		}
	}

	/**
	 * Change some vars in viewtopic
	 *
	 * @param \phpbb\event\data $event
	 * @access public
	 */
	public function viewtopic_assign_template_vars_before($event)
	{
		if ($this->printallposts == true)
		{
			$printallposts_url = $event['viewtopic_url'] . '&amp;view=printall';

			$this->template->assign_var('U_PRINTALLPOSTS', $printallposts_url);
		}
	}

	/**
	 * Overwrite $view var to use the correct template
	 *
	 * @param \phpbb\event\data $event
	 * @access public
	 */
	public function viewtopic_modify_page_title($event)
	{
		if ($this->printallposts_view == 'printall')
		{
			// Output the page
			page_header($event['page_title'], true, (int) $event['forum_id']);

			$this->template->set_filenames([
				'body' => '@phpbbmodders_printallposts/viewtopic_printall.html'
			]);

			page_footer();
		}
	}
}
