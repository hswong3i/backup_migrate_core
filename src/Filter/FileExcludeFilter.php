<?php
/**
 * @file
 * Contains BackupMigrate\Core\Filter\FileExcludeFilter
 */


namespace BackupMigrate\Core\Filter;


use BackupMigrate\Core\Config\Config;
use BackupMigrate\Core\Plugin\PluginBase;

/**
 * Class FileExcludeFilter
 * @package BackupMigrate\Core\Filter
 */
class FileExcludeFilter extends PluginBase {

  /**
   * The 'beforeDBTableBackup' plugin op.
   *
   * @param array $table
   * @param array $params
   * @return array $table
   */
  public function beforeFileBackup($path, $params = []) {
    $source = $this->confGet('source');
    if ($source && $source == $params['source']) {
      $exclude = $this->confGet('exclude_filepaths');
      $exclude = $this->compileExcludePatterns($exclude);

      if ($this->matchPath($path, $exclude, $params['base_path'])) {
        return null;
      }
    }
    return $path;
  }

  /**
   * Get the default values for the plugin.
   *
   * @return \BackupMigrate\Core\Config\Config
   */
  public function configDefaults() {
    return new Config([
      'source' => '',
      'exclude_filepaths' => [],
    ]);
  }



  /**
   * Convert an array of glob patterns to an array of regex patterns for file name exclusion.
   *
   * @param array $exclude
   *    A list of patterns with glob wildcards
   * @return array
   *    A list of patterns as regular expressions
   *
   */
  private function compileExcludePatterns($exclude) {
    static $patterns = null;

    if ($patterns!== null) {
      return $patterns;
    }
    $patterns = [];
    foreach ($exclude as $pattern) {
      // Convert Glob wildcards to a regex per http://php.net/manual/en/function.fnmatch.php#71725
      $patterns[] = "#^". strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.', '\[' => '[', '\]' => ']'))."$#i";
    }
    return $patterns;
  }

  /**
   * Match a path to the list of exclude patterns.
   *
   * @param string $path
   *    The path to match.
   * @param array $exclude
   *    An array of regular expressions to match against.
   * @param string $base_path
   * @return bool
   */
  private function matchPath($path, $exclude, $base_path = '') {
    $path = substr($path, strlen($base_path));

    foreach ($exclude as $pattern) {
      if (preg_match($pattern, $path)) {
        return true;
      }
    }
    return false;
  }


  /**
   * Get a definition for user-configurable settings.
   *
   * @param array $params
   * @return array
   */
  public function configSchema($params = array()) {
    $schema = array();

    $source = $this->confGet('source');

    // Backup settings.
    if (!empty($source) && $params['operation'] == 'backup') {
      $schema['groups']['default'] = [
        'title' => $this->t('Exclude Files from %source', ['%source' => $source->confGet('name')]),
      ];
      // Backup settings.
      if ($params['operation'] == 'backup') {
        $schema['fields']['exclude_filepaths'] = [
          'type' => 'text',
          'title' => $this->t('Exclude these files'),
          'multiple' => true,
          'group' => 'default'
        ];
      }    }
    return $schema;
  }

}