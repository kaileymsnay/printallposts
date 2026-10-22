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

namespace phpbbmodders\printallposts;

/**
 * Print All Posts extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends \phpbb\extension\base
{
	/** Package name this extension used before the rename */
	const OLD_EXT_NAME = 'rmcgirr83/printallposts';

	/** Package name this extension uses now */
	const NEW_EXT_NAME = 'phpbbmodders/printallposts';

	/** PHP namespace prefix used before the rename */
	const OLD_NAMESPACE = '\\rmcgirr83\\printallposts\\';

	/** PHP namespace prefix used now */
	const NEW_NAMESPACE = '\\phpbbmodders\\printallposts\\';

	/**
	 * Check whether the extension can be enabled.
	 * The current phpBB and PHP version should meet or
	 * exceed the minimum version required by this extension:
	 *
	 * @return bool|array
	 * @access public
	 */
	public function is_enableable()
	{
		$enableable = $this->check_phpbb_version() && $this->check_php_version();

		if (!$enableable)
		{
			$language = $this->container->get('language');
			$language->add_lang('printallposts_install', 'phpbbmodders/printallposts');

			return $language->lang('PRINTALLPOSTS_NOT_ENABLEABLE');
		}

		if ($this->container->get('ext.manager')->is_enabled(self::OLD_EXT_NAME))
		{
			$language = $this->container->get('language');
			$language->add_lang('printallposts_install', 'phpbbmodders/printallposts');

			return $language->lang('PRINTALLPOSTS_DISABLE_OLD', self::OLD_EXT_NAME);
		}

		return $enableable;
	}

	/**
	 * Require phpBB 3.3.19 due to several security fixes in the phpBB software.
	 *
	 * @return bool
	 */
	protected function check_phpbb_version()
	{
		return phpbb_version_compare(PHPBB_VERSION, '3.3.19', '>=');
	}

	/**
	 * Requires PHP 8.3.36 due to security fixes.
	 *
	 * @return bool
	 */
	protected function check_php_version()
	{
		return PHP_VERSION_ID >= 80336;
	}

	/**
	 * Move an old install's data to the new name, then enable as usual.
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function enable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->move_old_install();

			// The migrator loaded its state before this ran; reload it so the
			// moved migration history counts as already installed.
			$this->migrator->load_migration_state();
		}

		return parent::enable_step($old_state);
	}

	/**
	 * Rewrite everything the database stores under the old name.
	 */
	protected function move_old_install()
	{
		$db = $this->container->get('dbal.conn');

		$db->sql_transaction('begin');

		// The old extension's own record (only once it's disabled)
		$sql = 'DELETE FROM ' . EXT_TABLE . "
			WHERE ext_name = '" . $db->sql_escape(self::OLD_EXT_NAME) . "'
				AND ext_active = 0";
		$db->sql_query($sql);

		$db->sql_transaction('commit');
	}
}
