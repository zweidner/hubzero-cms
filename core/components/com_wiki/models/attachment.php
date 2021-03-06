<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Wiki\Models;

use Hubzero\Database\Relational;
use Date;
use Lang;

/**
 * Wiki model for page attachments
 */
class Attachment extends Relational
{
	/**
	 * The table namespace
	 *
	 * @var  string
	 */
	protected $namespace = 'wiki';

	/**
	 * Default order by for model
	 *
	 * @var  string
	 */
	public $orderBy = 'created';

	/**
	 * Default order direction for select queries
	 *
	 * @var  string
	 */
	public $orderDir = 'asc';

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array
	 */
	protected $rules = array(
		'page_id'  => 'nonzero',
		'filename' => 'notempty'
	);

	/**
	 * Automatic fields to populate every time a row is created
	 *
	 * @var  array
	 */
	public $initiate = array(
		'created',
		'created_by'
	);

	/**
	 * Defines a belongs to one relationship between task and liaison
	 *
	 * @return  object
	 */
	public function creator()
	{
		return $this->belongsToOne('Hubzero\User\User', 'created_by');
	}

	/**
	 * Defines a belongs to one relationship between task and liaison
	 *
	 * @return  object
	 */
	public function page()
	{
		return $this->belongsToOne('Page', 'page_id');
	}

	/**
	 * Return a formatted timestamp for created date
	 *
	 * @param   string  $as
	 * @param   string  $format
	 * @return  string
	 */
	public function created($as='', $format=null)
	{
		$as = strtolower($as);

		if ($as == 'date')
		{
			return Date::of($this->get('created'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
		}

		if ($as == 'time')
		{
			return Date::of($this->get('created'))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
		}

		if ($format)
		{
			return Date::of($this->get('created'))->toLocal($format);
		}

		return $this->get('created');
	}

	/**
	 * Load a record by filename and optional page ID
	 *
	 * @param   string   $filename
	 * @param   integer  $page_id
	 * @return  object
	 */
	public static function oneByFilename($filename, $page_id=null)
	{
		$instance = self::blank()
			->whereEquals('filename', $filename);

		if (!is_null($page_id))
		{
			$instance->whereEquals('page_id', $page_id);
		}

		return $instance->row();
	}

	/**
	 * Is the file an image?
	 *
	 * @return  boolean
	 */
	public function isImage()
	{
		return preg_match("/\.(bmp|gif|jpg|jpe|jpeg|png)$/i", $this->get('filename'));
	}

	/**
	 * Get filespace
	 *
	 * @return  string
	 */
	public function filespace()
	{
		static $path;

		if (!$path)
		{
			$path = PATH_APP . DS . trim(\Component::params('com_wiki')->get('filepath', '/site/wiki'), DS);
		}

		return $path;
	}

	/**
	 * Delete the record and all associated data
	 *
	 * @return  boolean  False if error, True on success
	 */
	public function destroy()
	{
		// Remove files
		$path = $this->filespace() . DS . $this->get('page_id') . DS . $this->get('filename');

		if (file_exists($path))
		{
			if (!\Filesystem::delete($path))
			{
				$this->addError(Lang::txt('COM_WIKI_ERROR_UNABLE_TO_DELETE_FILE', $this->get('filename')));
				return false;
			}
		}

		if (!$this->get('id'))
		{
			return true;
		}

		return parent::destroy();
	}
}
