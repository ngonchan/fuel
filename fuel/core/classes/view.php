<?php 
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 Dan Horrigan
 * @link		http://fuelphp.com
 */

namespace Fuel;

use Fuel\Application as App;

/**
 * View class
 *
 * NOTE: This class has been taken from the Kohana framework and slightly modified,
 * but on the whole all credit goes to them. Over time this will be worked on.
 */

/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2010 Kohana Team
 * @license    http://kohanaframework.org/license
 */

class View {

	// Array of global view data
	protected static $_global_data = array();

	// View filename
	protected $_file;

	// Array of local variables
	protected $_data = array();

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [static::set_filename].
	 *
	 *     $view = static::factory($file);
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  View
	 */
	public static function factory($file = NULL, array $data = NULL)
	{
		return new static($file, $data);
	}

	/**
	 * Sets the initial view filename and local data.
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  view filename
	 * @param   array   array of values
	 * @return  void
	 * @uses    static::set_filename
	 */
	public function __construct($file = NULL, array $data = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if ($data !== NULL)
		{
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->foo;
	 *
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  Exception
	 */
	public function & __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}
		elseif (array_key_exists($key, static::$_global_data))
		{
			return static::$_global_data[$key];
		}
		else
		{
//			throw new Exception('View variable is not set: :var',
//				array(':var' => $key));
		}
	}

	/**
	 * Magic method, calls [static::set] with the same parameters.
	 *
	 *     $view->foo = 'something';
	 *
	 * @param   string  variable name
	 * @param   mixed   value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($view->foo);
	 *
	 * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  variable name
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return (isset($this->_data[$key]) or isset(static::$_global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($view->foo);
	 *
	 * @param   string  variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->_data[$key], static::$_global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [static::render].
	 *
	 * @return  string
	 * @uses    static::render
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			// Display the exception message
			// TODO: Write the exception Handler

			return '';
		}
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 *     $output = static::capture($file, $data);
	 *
	 * @param   string  filename
	 * @param   array   variables
	 * @return  string
	 */
	protected static function capture($view_filename, array $view_data)
	{
		// Import the view variables to local namespace
		$view_data AND extract($view_data, EXTR_SKIP);

		if (static::$_global_data)
		{
			// Import the global view variables to local namespace and maintain references
			extract(static::$_global_data, EXTR_REFS);
		}

		// Capture the view output
		ob_start();

		try
		{
			// Load the view within the current scope
			include $view_filename;
		}
		catch (Exception $e)
		{
			die('Error');
			
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	/**
	 * Sets a global variable, similar to [static::set], except that the
	 * variable will be accessible to all views.
	 *
	 *     static::set_global($name, $value);
	 *
	 * @param   string  variable name or an array of variables
	 * @param   mixed   value
	 * @return  void
	 */
	public static function set_global($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				static::$_global_data[$key2] = $value;
			}
		}
		else
		{
			static::$_global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [static::bind], except
	 * that the variable will be accessible to all views.
	 *
	 *     static::bind_global($key, $value);
	 *
	 * @param   string  variable name
	 * @param   mixed   referenced variable
	 * @return  void
	 */
	public static function bind_global($key, & $value)
	{
		static::$_global_data[$key] =& $value;
	}

	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  view filename
	 * @return  View
	 * @throws  View_Exception
	 */
	public function set_filename($file)
	{
		if (($path = App\Fuel::find_file('views', $file)) === false)
		{
			throw new App\View_Exception('The requested view could not be found: '.App\Fuel::clean_path($file));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This value can be accessed as $foo within the view
	 *     $view->set('foo', 'my value');
	 *
	 * You can also use an array to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  $this
	 */
	public function set($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $value;
			}
		}
		else
		{
			$this->_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This reference can be accessed as $ref within the view
	 *     $view->bind('ref', $bar);
	 *
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @return  $this
	 */
	public function bind($key, & $value)
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	/**
	 * Renders the view object to a string. Global and local data are merged
	 * and extracted to create local variables within the view file.
	 *
	 *     $output = $view->render();
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param    string  view filename
	 * @return   string
	 * @throws   Fuel_View_Exception
	 * @uses     static::capture
	 */
	public function render($file = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if (empty($this->_file))
		{
			throw new App\View_Exception('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		return static::capture($this->_file, $this->_data);
	}

} // End View