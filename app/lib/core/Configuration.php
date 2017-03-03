<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Configuration.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2015 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */


/**
 * Parses and provides access to application configuration files.
 *
 * A configuration file can contain any number of key-value pairs. Keys are simple
 * alphanumeric text expressions. Values may be one of three types:
 *
 * - Scalar: a string or number. Strings are always unquoted and may contain any character.
 * - List: a list of strings or numbers separated by commas and enclosed in square brackets ("[" and "]"). A string must be enclosed in double quotes if it contains a comma. You may not place the double quote character in a list item. Lists are retrievable as indexed PHP arrays. Lists may not be nested.
 * - Associative array: a list of key-value pairs. Both keys and values must be enclosed in double quotes if they contain commas. Neither may contain double quotes. Associative arrays are enclosed with curly brackets ("{" and "}"). Separate keys from values with "=" Separate key-value pairs from each other using commas. Values may be strings, numbers or nested associative arrays. Associative arrays may be nested to any depth.
 *
 * Keys are always separated from values by "=" You may place as many spaces as you like on either side of the "=" character.
 *
 * Both lists and associative array may span as many lines as necessary.
 *
 * @example docs/Configuration1.example Example configuration file
 */
class Configuration {
	/**
	 * Contains parsed configuration values
	 *
	 * @access private
	 */
	var $ops_config_settings;

	/**
	 * Error message
	 *
	 * @access private
	 */
	var $ops_error="";		#  error message - blank if no error

	/**
	 * Absolute path to configuration file
	 *
	 * @access private
	 */
	var $ops_config_file_path;

	/**
	 * Display debugging info
	 *
	 * @access private
	 */
	var $opb_debug = false;

	static $s_get_cache;
	static $s_config_cache = null;
	static $s_have_to_write_config_cache = false;
	private $ops_md5_path;

	/* ---------------------------------------- */
	/**
	 * Load a configuration file
	 *
	 * @param string $ps_file_path
	 * @param bool $pb_dont_cache Don't use config file cached. [Default is false]
	 * @param bool $pb_dont_cache_instance Don't attempt to cache config file Configuration instance. [Default is false]
	 * @param bool $pb_dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 * @return Configuration
	 */
	static function load($ps_file_path=__CA_APP_CONFIG__, $pb_dont_cache=false, $pb_dont_cache_instance=false, $pb_dont_load_from_default_path=false) {
		if(!$ps_file_path) { $ps_file_path = __CA_APP_CONFIG__; }

		if(!MemoryCache::contains($ps_file_path, 'ConfigurationInstances') || $pb_dont_cache || $pb_dont_cache_instance) {
			MemoryCache::save($ps_file_path, new Configuration($ps_file_path, true, $pb_dont_cache, $pb_dont_load_from_default_path), 'ConfigurationInstances');
		}

		return MemoryCache::fetch($ps_file_path, 'ConfigurationInstances');
	}
	/* ---------------------------------------- */
	/**
	 * Load a configuration file. In addition to the parameters described below two global variables can also affect loading:
	 *
	 *		$g_ui_locale - if it contains the current locale code, this code will be used when computing the MD5 signature of the current configuration for caching purposes. By setting this to the current locale simultaneous caching of configurations for various locales (eg. config files with gettext-translated strings in them) is enabled.
	 *		$g_configuration_cache_suffix - any text it contains is used along with the configuration path and $g_ui_locale to compute the MD5 signature of the current configuration for caching purposes. By setting this to some value you can support simultaneous caching of configurations for several different modes. This is mainly used to support caching of theme-specific configurations. Since the theme can change based upon user agent, we need to potentially keep several computed configurations cached at the same time, one for each theme used.
	 *
	 * @param string $ps_file_path Absolute path to configuration file to parse
	 * @param bool $pb_die_on_error If true, request processing will halt with call to die() on error in parsing config file. [Default is false]
	 * @param bool $pb_dont_cache If true, file will be parsed even if it's already cached. [Default is false]
	 * @param bool $pb_dont_load_from_default_path Don't attempt to load additional configuration files from default paths (defined by __CA_LOCAL_CONFIG_DIRECTORY__ and __CA_LOCAL_CONFIG_DIRECTORY__). [Default is false]
	 *
	 *
	 */
	public function __construct($ps_file_path=__CA_APP_CONFIG__, $pb_die_on_error=false, $pb_dont_cache=false, $pb_dont_load_from_default_path=false) {
		global $g_ui_locale, $g_configuration_cache_suffix;

		$this->ops_config_file_path = $ps_file_path ? $ps_file_path : __CA_APP_CONFIG__;	# path to configuration file
		// cache key for on-disk caching
		$vs_path_as_md5 = md5($_SERVER['HTTP_HOST'].$this->ops_config_file_path.'/'.$g_ui_locale.(isset($g_configuration_cache_suffix) ? '/'.$g_configuration_cache_suffix : ''));

		#
		# Is configuration file already cached?
		#
		$va_config_path_components = explode("/", $this->ops_config_file_path);
		$vs_config_filename = array_pop($va_config_path_components);


		$vs_local_conf_file_path = null;
		if (!$pb_dont_load_from_default_path) {
			if (defined('__CA_LOCAL_CONFIG_DIRECTORY__') && file_exists(__CA_LOCAL_CONFIG_DIRECTORY__.'/'.$vs_config_filename)) {
				$vs_local_conf_file_path = __CA_LOCAL_CONFIG_DIRECTORY__.'/'.$vs_config_filename;
			} elseif (defined('__CA_DEFAULT_THEME_CONFIG_DIRECTORY__') && file_exists(__CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$vs_config_filename)) {
				$vs_local_conf_file_path = __CA_DEFAULT_THEME_CONFIG_DIRECTORY__.'/'.$vs_config_filename;
			}
		}

		// try to figure out if we can get it from cache
		if((!defined('__CA_DISABLE_CONFIG_CACHING__') || !__CA_DISABLE_CONFIG_CACHING__) && !$pb_dont_cache) {
			self::loadConfigCacheInMemory();

			if($vb_setup_has_changed = caSetupPhpHasChanged()) {
				self::clearCache();
			}

			if(!$vb_setup_has_changed && isset(self::$s_config_cache[$vs_path_as_md5])) {
				$vb_cache_is_invalid = false;

				$vs_config_mtime = caGetFileMTime($this->ops_config_file_path);
				if($vs_config_mtime != self::$s_config_cache['mtime_'.$vs_path_as_md5]) { // config file has changed
					self::$s_config_cache['mtime_'.$vs_path_as_md5] = $vs_config_mtime;
					$vb_cache_is_invalid = true;
				}

				if ($vs_local_conf_file_path) {
					$vs_local_config_mtime = caGetFileMTime($vs_local_conf_file_path);
					if($vs_local_config_mtime != self::$s_config_cache['local_mtime_'.$vs_path_as_md5]) { // local config file has changed
						self::$s_config_cache['local_mtime_'.$vs_path_as_md5] = $vs_local_config_mtime;
						$vb_cache_is_invalid = true;
					}
				}

				if (!$vb_cache_is_invalid) { // cache is ok
					$this->ops_config_settings = self::$s_config_cache[$vs_path_as_md5];;
					$this->ops_md5_path = md5($this->ops_config_file_path);
					return;
				}
			}

		}

		# load hash
		$this->ops_config_settings = array();

		# try loading global.conf file
		$vs_global_path = join("/", $va_config_path_components).'/global.conf';
		if (file_exists($vs_global_path)) { $this->loadFile($vs_global_path, false); }

		//
		// Insert current user locale as constant into configuration.
		//
		$this->ops_config_settings['scalars']['LOCALE'] = $g_ui_locale;

		#
		# load specified config file
		#
		if (file_exists($this->ops_config_file_path) && $this->loadFile($this->ops_config_file_path, false)) {
			$this->ops_config_settings["ops_config_file_path"] = $this->ops_config_file_path;
		}

		#
		# try to load optional "local" config file (extra, optional, config file that can override values in the specified config file with "local" values)
		#
		if ($vs_local_conf_file_path) {
			$this->loadFile($vs_local_conf_file_path, false, false);
		}

		if($vs_path_as_md5 && !$pb_dont_cache) {
			self::$s_config_cache[$vs_path_as_md5] = $this->ops_config_settings;
			// we loaded this cfg from file, so we have to write the
			// config cache to disk at least once on this request
			self::$s_have_to_write_config_cache = true;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Parses configuration file located at $ps_file_path.
	 *
	 * @param $ps_filepath - absolute path to configuration file to parse
	 * @param $pb_die_on_error - if true, die() will be called on parse error halting request; default is false
	 * @param $pn_num_lines_to_read - if set to a positive integer, will abort parsing after the first $pn_num_lines_to_read lines of the config file are read. This is useful for reading in headers in config files without having to parse the entire file.
	 * @return boolean - returns true if parse succeeded, false if parse failed
	 */
	public function loadFile($ps_filepath, $pb_die_on_error=false, $pn_num_lines_to_read=null) {
		$this->ops_md5_path = md5($ps_filepath);
		$this->ops_error = "";
		$r_file = @fopen($ps_filepath,"r", true);
		if (!$r_file) {
			$this->ops_error = "Couldn't open configuration file '".$ps_filepath."'";
			if ($pb_die_on_error) { $this->_dieOnError(); }
			return false;
		}

		$vs_key = $vs_scalar_value = $vs_assoc_key = "";
		$vn_in_quote = $vn_state = 0;
		$vb_escape_set = false;
		$va_assoc_pointer_stack = array();

		$va_token_history = array();
		$vn_line_num = 0;
		$vb_merge_mode = false;
		while (!feof($r_file)) {
			$vn_line_num++;

			if (($pn_num_lines_to_read > 0) && ($vn_line_num > $pn_num_lines_to_read)) { break; }
			$vs_buffer = trim(fgets($r_file, 32000));

			# skip comments (start with '#') or blank lines
			if (strtolower(substr($vs_buffer,0,7)) == '#!merge') { $vb_merge_mode = true; }
			if (strtolower(substr($vs_buffer,0,9)) == '#!replace') { $vb_merge_mode = false; }
			if (!$vs_buffer || (substr($vs_buffer,0,1) === "#")) { continue; }

			$va_token_tmp = preg_split("/([={}\[\]\",\\\]){1}/", $vs_buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

			// eliminate blank tokens
			$va_tokens = array();
			$vn_tok_count = sizeof($va_token_tmp);
			for($vn_i = 0; $vn_i < $vn_tok_count; $vn_i++) {
				if (strlen($va_token_tmp[$vn_i])) {
					$va_tokens[] =& $va_token_tmp[$vn_i];
				}
			}
			while (sizeof($va_tokens)) {
				$vs_token = array_shift($va_tokens);

				$va_token_history[] = $vs_token;
				if (sizeof($va_token_history) > 50) { array_shift($va_token_history); }
				switch($vn_state) {
					# ------------------------------------
					# init
					case -1:
						$vs_key = $vs_assoc_key = $vs_scalar_value = "";
						$vn_in_quote = 0;
						$va_assoc_pointer_stack = array();

						$vn_state = 0;

					# ------------------------------------
					# looking for key
					case 0:
						if ($vs_token != "=") {
							$vs_key .= $vs_token;
						} else {
							$vn_got_key = 1;
							$vs_key = trim($vs_key);

							$vn_state = 10;
						}
						break;
					# ------------------------------------
					# determine type of value
					case 10:
						switch($vs_token) {
							case '[':
								if(!is_array($this->ops_config_settings["lists"][$vs_key]) || !$vb_merge_mode) {
									$this->ops_config_settings["lists"][$vs_key] = array();
								}
								$vn_state = 30;
								break;
							case '{':
								if(!is_array($this->ops_config_settings["assoc"][$vs_key]) || !$vb_merge_mode) {
									$this->ops_config_settings["assoc"][$vs_key] = array();
								}
								$va_assoc_pointer_stack[] =& $this->ops_config_settings["assoc"][$vs_key];
								$vn_state = 40;
								break;
							case '"':
								if($vn_in_quote) {
									$vn_in_quote = 0;
									$vn_state = -1;
								} else {
									$vs_scalar_value = '';
									$vn_in_quote = 1;
									$vn_state = 20;
								}
								break;
							default:
								// strip leading exclaimation in scalar to allow scalars to start with [ or {
								if (trim($vs_token) == '!') {
									$vs_token = array_shift($va_tokens);
								}
								if (!preg_match("/^[ \t]*$/", $vs_token)) {
									$vs_scalar_value .= $vs_token;
									$vn_state = 20;

									if(!$vn_in_quote) {
										if (sizeof($va_tokens) == 0) {
											$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
											$vn_state = -1;
										}
									}
								}
								break;
						}
						break;
					# ------------------------------------
					# handle scalar values
					case 20:
						// end quote? -> accept scalar
						if((trim($vs_token) == '"') && $vn_in_quote) {
							if($vn_in_quote) {
								$vn_in_quote = 0;
								$vn_state = -1;

								$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
								break;
							}
						}

						if (preg_match("/[\r\n]/", $vs_token) && !$vn_in_quote) {
							$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);
							$vn_state = -1;
						} else {
							if ((sizeof($va_tokens) == 0) && !$vn_in_quote) {
								$vs_scalar_value .= $vs_token;

								# accept scalar
								$this->ops_config_settings["scalars"][$vs_key] = $this->_trimScalar($vs_scalar_value);

								# initialize
								$vn_state = -1;
							} else { # keep going to next line
								$vs_scalar_value .= $vs_token;
								$vn_state = 20;
							}
						}
						break;
					# ------------------------------------
					# handle list values
					case 30:
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$vs_key][] = $vs_item;
									}
									$vs_scalar_value = "";
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ']':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "]";
								} else {
									# accept list
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$this->ops_config_settings["lists"][$vs_key][] = $vs_item;
									}
									# initialize
									$vn_state = -1;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($va_tokens) == 0) && ($vn_in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$vs_key'";
							fclose($r_file);
							if ($pb_die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------
					# handle associative array values
					# get associative key
					case 40:
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_assoc_key .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '=':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_assoc_key .= "=";
								} else {
									if (($vs_assoc_key = trim($this->_interpolateScalar($vs_assoc_key))) == '') {
										$this->ops_error = "Associative key must not be empty";
										fclose($r_file);

										if ($pb_die_on_error) { $this->_dieOnError(); }
										return false;
									}

									$vn_state = 50;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_assoc_key .= ",";
								} else {
									if ($vs_assoc_key) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
									}
									$vs_assoc_key = "";
									$vs_scalar_value = "";
									$vn_state = 40;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '}':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "}";
								} else {
									if (sizeof($va_assoc_pointer_stack) > 1) {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
										}
										array_pop($va_assoc_pointer_stack);

										$vn_state = 40;
									} else {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = trim($vs_assoc_key);
										}
										$vn_state = -1;
									}
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_assoc_key .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							default:
								if (preg_match("/^#/", trim($vs_token))) {
									// comment
								} else {
									$vb_escape_set = false;
									$vs_assoc_key .= $vs_token;
								}
								break;
							# -------------------
						}

						break;
					# ------------------------------------
					# handle associative value
					case 50:
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if ($vs_assoc_key) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
									}
									$vs_assoc_key = "";
									$vs_scalar_value = "";
									$vn_state = 40;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							# open nested associative value
							case '{':
								if (!$vn_in_quote && !$vb_escape_set) {
									$i = sizeof($va_assoc_pointer_stack) - 1;
									if (!is_array($va_assoc_pointer_stack[$i][$vs_assoc_key]) || !$vb_merge_mode) {
										$va_assoc_pointer_stack[$i][$vs_assoc_key] = array();
									}
									$va_assoc_pointer_stack[] =& $va_assoc_pointer_stack[$i][$vs_assoc_key];
									
									$vn_state = 40;
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
								} else {
									$vs_scalar_value .= $vs_token;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '}':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "}";
								} else {
									if (sizeof($va_assoc_pointer_stack) > 1) {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
										}
										array_pop($va_assoc_pointer_stack);

										$vn_state = 40;
									} else {
										if ($vs_assoc_key) {
											$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key] = $this->_trimScalar($this->_interpolateScalar($vs_scalar_value));
										}
										$vn_state = -1;
									}
									$vs_key = $vs_assoc_key = $vs_scalar_value = "";
									$vn_in_quote = 0;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							# open list
							case '[':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$i = sizeof($va_assoc_pointer_stack) - 1;
									if(!is_array($va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][$vs_assoc_key]) || !$vb_merge_mode) {
										$va_assoc_pointer_stack[$i][$vs_assoc_key] = array();
									}
									$va_assoc_pointer_stack[] =& $va_assoc_pointer_stack[$i][$vs_assoc_key];
									$vn_state = 60;
									$vn_in_quote = 0;
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								if ($vb_escape_set) {
									$vs_scalar_value .= $vs_token;
								} else {
									$vb_escape_set = true;
								}
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						break;
					# ------------------------------------
					# handle list values nested in assoc
					case 60:
						switch($vs_token) {
							# -------------------
							case '"':
								if ($vb_escape_set) {
									$vs_scalar_value .= '"';
								} else {
									if (!$vn_in_quote) {
										$vn_in_quote = 1;
									} else {
										$vn_in_quote = 0;
									}
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ',':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= ",";
								} else {
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = $vs_item;
									}
									$vs_scalar_value = "";
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case ']':
								if ($vn_in_quote || $vb_escape_set) {
									$vs_scalar_value .= "]";
								} else {
									# accept list
									if (strlen($vs_item = trim($this->_interpolateScalar($this->_trimScalar($vs_scalar_value)))) > 0) {
										$va_assoc_pointer_stack[sizeof($va_assoc_pointer_stack) - 1][] = $vs_item;
									}
									array_pop($va_assoc_pointer_stack);
									# initialize
									$vn_state = 40;
									$vs_assoc_key = '';
								}
								$vb_escape_set = false;
								break;
							# -------------------
							case '\\':
								$vb_escape_set = true;
								break;
							# -------------------
							default:
								$vs_scalar_value .= $vs_token;
								$vb_escape_set = false;
								break;
							# -------------------
						}
						if ((sizeof($va_tokens) == 0) && ($vn_in_quote)) {
							$this->ops_error = "Missing trailing quote in list '$vs_key'";
							fclose($r_file);
							if ($pb_die_on_error) { $this->_dieOnError(); }
							return false;
						}
						break;
					# ------------------------------------

				}
			}
			if ((($vn_state == 10) || ($vn_state == 20)) && !$vn_in_quote) {
				$this->ops_config_settings["scalars"][$vs_key] = "";
				$vn_state = -1;
			}

			if(in_array($vn_state, [10,20]) && $vn_in_quote) {
				$vs_scalar_value .= "\n";
			}

			if ($vn_in_quote && !in_array($vn_state, [10,20])) {
				switch($vn_state) {
					case 30:
						$this->ops_error = "Missing trailing quote in list '$vs_key'<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
						break;
					case 40:
					case 50:
						$this->ops_error = "Missing trailing quote in associative array '$vs_key'<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
					default:
						$this->ops_error = "Missing trailing quote in '$vs_key' [Last token was '{$vs_token}'; state was $vn_state]<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
				}
				fclose($r_file);

				if ($pb_die_on_error) { $this->_dieOnError(); }
				return false;
			}
		}

		if ($vn_state > 0) {
			$this->ops_error = "Syntax error in configuration file: missing { or } [state=$vn_state]<br/><strong>Last ".sizeof($va_token_history)." tokens were: </strong>".$this->_formatTokenHistory($va_token_history, array('outputAsHTML' => true));
			fclose($r_file);

			if ($pb_die_on_error) { $this->_dieOnError(); }
			return false;
		}

		// interpolate scalars
		if (is_array($this->ops_config_settings["scalars"])) {
			foreach($this->ops_config_settings["scalars"] as $vs_key => $vs_val) {
				$this->ops_config_settings["scalars"][$vs_key] = $this->_interpolateScalar($vs_val);
			}
		}
		fclose($r_file);

		return true;
	}
	/* ---------------------------------------- */
	private function _formatTokenHistory($pa_token_history, $pa_options=null) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		$vs_output = '';
		if (isset($pa_options['outputAsHTML']) && $pa_options['outputAsHTML']) {
			$vs_output = "<pre>";
			for($vn_i=1; $vn_i <=sizeof($pa_token_history); $vn_i++) {
				$vs_output .= "\t[{$vn_i}] ".$pa_token_history[$vn_i-1]."\n";
			}
			$vs_output .= "</pre>";
		} else {
			if (!isset($pa_options['delimiter'])) { $vs_delimiter = ';'; } else { $vs_delimiter = $pa_options['delimiter']; }
			$vs_output = join($vs_delimiter, $pa_token_history);
		}
		return $vs_output;
	}
	/* ---------------------------------------- */
	/**
	 * Get configuration value
	 *
	 * @param string $ps_key Name of configuration value to get. get() will look for the
	 * configuration value first as a scalar, then as a list and finally as an associative array.
	 * The first value found is returned.
	 *
	 * @return mixed A string, indexed array (list) or associative array, depending upon what
	 * kind of configuration value was found.
	 */
	public function get($ps_key) {
		if (isset(Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]) && Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]) { return Configuration::$s_get_cache[$this->ops_md5_path][$ps_key]; }
		$this->ops_error = "";

		$vs_tmp = $this->getScalar($ps_key);
		if (!strlen($vs_tmp)) {
			$vs_tmp = $this->getList($ps_key);
		}
		if (!is_array($vs_tmp) && !strlen($vs_tmp)) {
			$vs_tmp = $this->getAssoc($ps_key);
		}
		Configuration::$s_get_cache[$this->ops_md5_path][$ps_key] = $vs_tmp;
		return $vs_tmp;
	}
	/* ---------------------------------------- */
	/**
	 * Get boolean configuration value
	 *
	 * @param string $ps_key Name of configuration value to get. getBoolean() will look for the
	 * configuration value only as a scalar, and return boolean 'true' if the scalar value is
	 * either 'yes', 'true' or '1'.
	 *
	 * @return boolean
	 */
	public function getBoolean($ps_key) {
		$vs_tmp = strtolower($this->getScalar($ps_key));
		if(($vs_tmp == "yes") || ($vs_tmp == "true") || ($vs_tmp == "1")) {
			return true;
		} else {
			return false;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get scalar configuration value
	 *
	 * @param string $ps_key Name of scalar configuration value to get. get() will look for the
	 * configuration value only as a scalar. Like-named list or associative array values are
	 * ignored.
	 *
	 * @return string
	 */
	public function getScalar($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["scalars"][$ps_key])) {
			return $this->ops_config_settings["scalars"][$ps_key];
		} else {
			return false;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for scalar values
	 *
	 *
	 * @return array List of all possible keys for scalar values
	 */
	public function getScalarKeys() {
		$this->ops_error = "";
		return array_keys($this->ops_config_settings["scalars"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for list values
	 *
	 *
	 * @return array List of all possible keys for list values
	 */
	public function getListKeys() {
		$this->ops_error = "";
		return array_keys($this->ops_config_settings["lists"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get keys for associative values
	 *
	 *
	 * @return array List of all possible keys for associative values
	 */
	public function getAssocKeys() {
		$this->ops_error = "";
		return @array_keys($this->ops_config_settings["assoc"]);
	}
	/* ---------------------------------------- */
	/**
	 * Get list configuration value
	 *
	 * @param string $ps_key Name of list configuration value to get. get() will look for the
	 * configuration value only as a list. Like-named scalar or associative array values are
	 * ignored.
	 *
	 * @return array An indexed array
	 */
	public function getList($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["lists"][$ps_key])) {
			if (is_array($this->ops_config_settings["lists"][$ps_key])) {
				return $this->ops_config_settings["lists"][$ps_key];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Get associative configuration value
	 *
	 * @param string $ps_key Name of associative configuration value to get. get() will look for the
	 * configuration value only as an associative array. Like-named scalar or list values are
	 * ignored.
	 *
	 * @return array An associative array
	 */
	public function getAssoc($ps_key) {
		$this->ops_error = "";
		if (isset($this->ops_config_settings["assoc"][$ps_key])) {
			if (is_array($this->ops_config_settings["assoc"][$ps_key])) {
				return $this->ops_config_settings["assoc"][$ps_key];
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
	/* ---------------------------------------- */
	/**
	 * Find out if there was an error processing the configuration file
	 *
	 * @return bool Returns true if error occurred, false if not
	 */
	public function isError() {
		return ($this->ops_error) ? true : false;
	}
	/* ---------------------------------------- */
	/**
	 * Get error message
	 *
	 * @return string Returns user-displayable error message
	 */
	public function getError() {
		return $this->ops_error;
	}
	/* ---------------------------------------- */
	private function _trimScalar($ps_scalar_value) {
		if (preg_match("/^[ ]+$/", $ps_scalar_value)) {
			$ps_scalar_value = " ";
		} else {
			$ps_scalar_value = trim($ps_scalar_value);
		}
		// perform constant var substitution
		if (preg_match("/^(__[A-Za-z0-9\_]+)(?=__)/", $ps_scalar_value, $va_matches)) {
			if (defined($va_matches[1].'__')) {
				return str_replace($va_matches[1].'__', constant($va_matches[1].'__'), $ps_scalar_value);
			}
		}
		return $ps_scalar_value;
	}
	/* ---------------------------------------- */
	private function _dieOnError() {
		die("Error loading configuration file '".$this->ops_config_file_path."': ".$this->ops_error."\n");
	}
	/* ---------------------------------------- */
	private function _interpolateScalar($ps_text) {
		if (preg_match_all("/<([A-Za-z0-9_\-\.]+)>/", $ps_text, $va_matches)) {
			foreach($va_matches[1] as $vs_key) {
				if (($vs_val = $this->getScalar($vs_key)) !== false) {
					$ps_text = preg_replace("/<$vs_key>/", $vs_val, $ps_text);
				}
			}
		}

		// attempt translation if text is enclosed in _( and ) ... for example _t(translate me)
		// assumes translation function _t() is present; if not loaded will not attempt translation
		if (preg_match("/_\(([^\"]+)\)/", $ps_text, $va_matches)) {
			if(function_exists('_t')) {
				$vs_trans_text = $ps_text;
				array_shift($va_matches);
				foreach($va_matches as $vs_match) {
					$vs_trans_text = str_replace("_({$vs_match})", _t($vs_match), $vs_trans_text);
				}
				return $vs_trans_text;
			}
		}
		return $ps_text;
	}
	/* ---------------------------------------- */
	/**
	 * Removes all cached configuration
	 */
	public static function clearCache() {
		ExternalCache::delete('ConfigurationCache');
		self::$s_config_cache = null;
	}
	/* ---------------------------------------- */
	/**
	 * Load configuration from external cache into memory
	 */
	public static function loadConfigCacheInMemory() {
		if(!is_null(self::$s_config_cache)) { return; }

		if(ExternalCache::contains('ConfigurationCache')) {
			self::$s_config_cache = ExternalCache::fetch('ConfigurationCache');
		}
	}
	/* ---------------------------------------- */
	/**
	 * Destructor: Save config cache to disk/external provider
	 */
	public function __destruct() {
		if(self::$s_have_to_write_config_cache) {
			ExternalCache::save('ConfigurationCache', self::$s_config_cache, 'default', 0);
			self::$s_have_to_write_config_cache = false;
		}
	}
	# ---------------------------------------------------------------------------
}
