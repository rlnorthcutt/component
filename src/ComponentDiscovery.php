<?php

namespace Drupal\component;

use Drupal\Core\Site\Settings;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Discovery service for front-end components provided by modules and themes.
 *
 * This is heavily influenced by ExtensionDiscovery since we also need to
 * scan subdirectories to look for yml files.
 *
 * Extensions can define components in a MACHINE_NAME.component.yml file
 * contained in the 'components' subfolder in the extension's base directory.
 *
 * See the component_example module for more detailed examples.
 */
class ComponentDiscovery implements ComponentDiscoveryInterface {

  /**
   * The app root for the current operation.
   *
   * @var string
   */
  protected $root;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Array of required keys in the component yml file.
   *
   * @var array
   */
  protected $required = ['name', 'description'];

  /**
   * Regular expression to match PHP function names.
   *
   * @see http://php.net/manual/functions.user-defined.php
   */
  const PHP_FUNCT_PATTERN = '/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/';

  /**
   * Defines the default component configuration object.
   *
   * @var array
   */
  protected $defaults = [
    // Human readable label for breakpoint.
    'name' => '',
    // Description of the component for the admin UI.
    'description' => '',
    // The type of plugin to create with this component.
    // Current options are 'block' or 'library'.
    'type' => 'block',
    // JS files for inclusion as a library for this component.
    'js' => [],
    // JS files for inclusion as a library for this component.
    'css' => [],
    // The HTML template to use to render the component.
    'template' => 'index.htm',
    // Form configuration for the component in the admin UI.
    'form_configuration' => [],
    // Static parameters to be passed to the JS component.
    'static_configuration' => [],
    // Set the cache timeout for this component if needed.
    'cache' => ['max-age' => 0],
    // Define the library dependencies that this component needs.
    'dependencies' => [],
  ];

  /**
   * ComponentDiscovery constructor.
   *
   * @param string $root
   *   The root web directory of the Drupal installation.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Look through the codebase to find and load the components.
   *
   * This is the ONLY public method available via this service.
   *
   * @return array
   *   A multidimensional array keyed by the component type first, then by
   *   component machine name, with the yml data as the values.
   */
  public function getComponents() {
    $components = [];
    // Find component yml files. This provides an array of file paths keyed by
    // the machine_name (filename) of the component.
    $component_files = $this->scan();
    // Process each component file.
    foreach ($component_files as $name => $filepath) {
      // If the yml is valid and has data, then process it.
      if ($file_data = $this->parse($filepath)) {
        // Set the defaults and add the component data from the file.
        $component_data = array_merge($this->defaults, $file_data);
        // Create the component path and subpath (relative to the root).
        $path = str_replace($name . '.component.yml', '', $filepath);
        $subpath = str_replace($this->root, '', $path);
        // Organize the components by type (block, library);.
        $type = $component_data['type'];
        // Build the components return array.
        $components[$type][$name] = $component_data;
        $components[$type][$name]['machine_name'] = $name;
        $components[$type][$name]['path'] = $path;
        $components[$type][$name]['subpath'] = $subpath;
      }
    }
    // Allow the component info to be altered.
    $this->moduleHandler->alter('component_info', $components);

    return $components;
  }

  /**
   * Discovers all of the component Yaml files in the system.
   *
   * This will look in all enambled modules and themes for a "component"
   *  subdirectory. Then it will check that directory and all subdirectories
   *  for component Yaml files.
   *
   * The information is returned in an associative array, keyed by the component
   *  name (without .component.yml extension).
   *
   * @return array
   *   Array of file paths for component.yml files keyed by machine name.
   */
  protected function scan() {
    $filepaths = [];
    // Scan in the proper directories for components.
    foreach ($this->getSearchDirs() as $key => $dir) {
      // Check for any component yml files, and store their filepath.
      if ($component_files = $this->scanDirectory($dir)) {
        $filepaths += $component_files;
      }
    }
    // Process and return the list of components keyed by machine name.
    return $filepaths;

  }

  /**
   * Generate an array of directories to search in for components.
   *
   * We want to only look in the "components" folder of any activated module
   *  or themes.
   *
   * @return array
   *   Array of paths with a "components" directory in the root.
   */
  protected function getSearchDirs() {
    $dirs = [];
    // Create a list of parent directories to search. Look in all active modules
    // and themes. Look in the root as well.
    $parents = array_merge($this->moduleHandler->getModuleDirectories(), $this->themeHandler->getThemeDirectories());
    array_push($parents, ['root' => $this->root]);
    // Loop through the possible parent directories.
    foreach ($parents as $path) {
      // Only return paths that exist.
      // @TODO: figure out why this gives an array to string notice.
      if (($component_dir = $path . '/components') && is_dir($component_dir)) {
        $dirs[] = $component_dir;
      }
    }
    return $dirs;
  }

  /**
   * Parse a component Yaml file.
   *
   * @param string $filepath
   *   The path to the file that needs to be parsed.
   *
   * @return array
   *   An array of component data read from the file.
   */
  protected function parse($filepath) {
    $parsed_data = [];
    // Make sure the file exists.
    if (file_exists($filepath)) {
      // Straight Yaml decode.
      // @TODO: test exception
      try {
        $parsed_data = Yaml::decode(file_get_contents($filepath));
      }
      catch (InvalidDataTypeException $e) {
        throw new ComponentDiscoveryException(
          "Unable to parse {$filepath} " . $e->getMessage()
              );
      }
      // @TODO : test required keys
      // @TODO : right now this kills the site
      // Validate that the component has the required keys.
      $missing_keys = array_diff($this->required, array_keys($parsed_data));
      if (!empty($missing_keys)) {
        unset($parsed_data);
        \Drupal::logger('component')
          ->error('Component error: missing required keys (' . implode(', ', $missing_keys) . ') in ' . $filepath);
      }
    }
    return $parsed_data;
  }

  /**
   * Recursively scans a base directory for the components it contains.
   *
   * @param string $dir
   *   A relative base directory path to scan, without trailing slash.
   *
   * @return array
   *   An array of paths with filenames for each component yml.
   *
   * @see \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator
   * @see \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected function scanDirectory($dir) {
    // Validate that this is a directory worth investigating.
    if (!is_dir($dir)) {
      return NULL;
    }
    $files = [];
    // ******************** UNLEASH THE KRAKEN ********************.
    // We need to create a recursive iterator to crawl the directory and look
    // for component yml files. This type of action can be resource intensive.
    // For performance reasons, we want to limit the numebr of subdirectories
    // that we will search inside of. First we create a filter to do so.
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $dir_iterator = new \RecursiveDirectoryIterator($dir, $flags);

    // Allow directories specified in settings.php to be ignored. You can use
    // this to not check for files in common special-purpose directories.
    $ignore_dir = Settings::get('file_scan_ignore_directories', []);

    // Create the filter to use. Note that this is based on ExtensionDiscovery
    // We can safely use the same filters for finding info.yml files here.
    $filter = new RecursiveComponentFilterIterator($dir_iterator, $ignore_dir);

    // Grab the list of files that have been discovered using the filter above
    // to avoid scanning all subdirs. Glad we put a leash on this thing, huh?
    $iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);

    // Loop through the files found in directory and all valid subdirectories.
    foreach ($iterator as $key => $fileinfo) {
      // If this isn't a valid component file, then go check the next one.
      if (!preg_match(static::PHP_FUNCT_PATTERN, $fileinfo
        ->getBasename('.component.yml'))) {
        continue;
      }
      // Set the name of the component and the pathname of where to find it.
      $name = $fileinfo->getBasename('.component.yml');
      // Set the full path including the file itself.
      $pathfilename = $fileinfo->getPathName();
      // Add the filepath keyed by component machine name.
      $files[$name] = $pathfilename;
    }
    return $files;
  }

}
