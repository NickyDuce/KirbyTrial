<?php

namespace Kirby\Exception;

use Kirby\Cms\App;
use Kirby\Http\Environment;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/**
 * Exception
 * Thrown for general exceptions and extended by
 * other exception classes
 *
 * @package   Kirby Exception
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class Exception extends \Exception
{
	/**
	 * Data variables that can be used inside the exception message
	 */
	protected array $data;

	/**
	 * HTTP code that corresponds with the exception
	 */
	protected int $httpCode;

	/**
	 * Additional details that are not included in the exception message
	 */
	protected array $details;

	/**
	 * Whether the exception message could be translated
	 * into the user's language
	 */
	protected bool $isTranslated = true;

	/**
	 * Defaults that can be overridden by specific
	 * exception classes
	 */
	protected static string $defaultKey = 'general';
	protected static string $defaultFallback = 'An error occurred';
	protected static array $defaultData = [];
	protected static int $defaultHttpCode = 500;
	protected static array $defaultDetails = [];

	/**
	 * Prefix for the exception key (e.g. 'error.general')
	 */
	private static string $prefix = 'error';

	/**
	 * Class constructor
	 *
	 * @param array|string $args Full option array ('key', 'translate', 'fallback',
	 *                           'data', 'httpCode', 'details' and 'previous') or
	 *                           just the message string
	 */
	public function __construct(array|string $args = [])
	{
		// set data and httpCode from provided arguments or defaults
		$this->data     = $args['data']     ?? static::$defaultData;
		$this->httpCode = $args['httpCode'] ?? static::$defaultHttpCode;
		$this->details  = $args['details']  ?? static::$defaultDetails;

		// define the Exception key
		$key = $args['key'] ?? static::$defaultKey;

		if (Str::startsWith($key, self::$prefix . '.') === false) {
			$key = self::$prefix . '.' . $key;
		}

		if (is_string($args) === true) {
			$this->isTranslated = false;
			parent::__construct($args);
		} else {
			// define whether message can/should be translated
			$translate =
				($args['translate'] ?? true) === true &&
				class_exists(App::class) === true;

			// fallback waterfall for message string
			$message = null;

			if ($translate === true) {
				// 1. translation for provided key in current language
				// 2. translation for provided key in default language
				if (isset($args['key']) === true) {
					$message = I18n::translate(self::$prefix . '.' . $args['key']);
					$this->isTranslated = true;
				}
			}

			// 3. provided fallback message
			if ($message === null) {
				$message = $args['fallback'] ?? null;
				$this->isTranslated = false;
			}

			if ($translate === true) {
				// 4. translation for default key in current language
				// 5. translation for default key in default language
				if ($message === null) {
					$message = I18n::translate(self::$prefix . '.' . static::$defaultKey);
					$this->isTranslated = true;
				}
			}

			// 6. default fallback message
			if ($message === null) {
				$message = static::$defaultFallback;
				$this->isTranslated = false;
			}

			// format message with passed data
			$message = Str::template($message, $this->data, ['fallback' => '-']);

			// handover to Exception parent class constructor
			parent::__construct($message, 0, $args['previous'] ?? null);
		}

		// set the Exception code to the key
		$this->code = $key;
	}

	/**
	 * Returns the file in which the Exception was created
	 * relative to the document root
	 */
	final public function getFileRelative(): string
	{
		$file    = $this->getFile();
		$docRoot = Environment::getGlobally('DOCUMENT_ROOT');

		if (empty($docRoot) === false) {
			$file = ltrim(Str::after($file, $docRoot), '/');
		}

		return $file;
	}

	/**
	 * Returns the data variables from the message
	 */
	final public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Returns the additional details that are
	 * not included in the message
	 */
	final public function getDetails(): array
	{
		return $this->details;
	}

	/**
	 * Returns the exception key (error type)
	 */
	final public function getKey(): string
	{
		return $this->getCode();
	}

	/**
	 * Returns the HTTP code that corresponds
	 * with the exception
	 */
	final public function getHttpCode(): int
	{
		return $this->httpCode;
	}

	/**
	 * Returns whether the exception message could
	 * be translated into the user's language
	 */
	final public function isTranslated(): bool
	{
		return $this->isTranslated;
	}

	/**
	 * Converts the object to an array
	 */
	public function toArray(): array
	{
		return [
			'exception' => static::class,
			'message'   => $this->getMessage(),
			'key'       => $this->getKey(),
			'file'      => $this->getFileRelative(),
			'line'      => $this->getLine(),
			'details'   => $this->getDetails(),
			'code'      => $this->getHttpCode()
		];
	}
}
