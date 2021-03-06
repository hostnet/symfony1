<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr <sean@code-box.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <sean@code-box.org>
 * @version    SVN: $Id: sfAutoloadConfigHandler.class.php 17047 2009-04-06 14:43:02Z fabien $
 */
class sfAutoloadConfigHandler extends sfYamlConfigHandler
{
  /**
   * Executes this configuration handler.
   *
   * @param array $configFiles An array of absolute filesystem path to a configuration file
   *
   * @return string Data to be written to a cache file
   *
   * @throws sfConfigurationException If a requested configuration file does not exist or is not readable
   * @throws sfParseException If a requested configuration file is improperly formatted
   */
  public function execute($configFiles)
  {
    // set our required categories list and initialize our handler
    $this->initialize(array('required_categories' => array('autoload')));

    // parse the yaml
    $config = self::getConfiguration($configFiles);

    // init our data array
    $data = array();

    // let's do our fancy work
    foreach ($config['autoload'] as $name => $entry)
    {
      if (isset($entry['name']))
      {
        $data[] = sprintf("\n// %s", $entry['name']);
      }

      // file mapping or directory mapping?
      if (isset($entry['files']))
      {
        // file mapping
        foreach ($entry['files'] as $class => $path)
        {
          $data[] = sprintf("'%s' => '%s',", $class, $path);
        }
      }
      else
      {
        // directory mapping
        $ext  = isset($entry['ext']) ? $entry['ext'] : '.php';
        $path = $entry['path'];

        // we automatically add our php classes
        require_once(sfConfig::get('sf_symfony_lib_dir').'/util/sfFinder.class.php');
        $finder = sfFinder::type('file')->name('*'.$ext)->follow_link();

        // recursive mapping?
        $recursive = isset($entry['recursive']) ? $entry['recursive'] : false;
        if (!$recursive)
        {
          $finder->maxdepth(0);
        }

        // exclude files or directories?
        if (isset($entry['exclude']) && is_array($entry['exclude']))
        {
          $finder->prune($entry['exclude'])->discard($entry['exclude']);
        }

        if ($matches = glob($path))
        {
          $files = $finder->in($matches);
        }
        else
        {
          $files = array();
        }

        $regex = '~^\s*(?:abstract\s+|final\s+)?(?:class|interface)\s+(\w+)~mi';
        foreach ($files as $file)
        {
          preg_match_all($regex, file_get_contents($file), $classes);
          foreach ($classes[1] as $class)
          {
            $prefix = '';
            if (isset($entry['prefix']))
            {
              // FIXME: does not work for plugins installed with a symlink
              preg_match('~^'.str_replace('\*', '(.+?)', preg_quote(str_replace('/', DIRECTORY_SEPARATOR, $path), '~')).'~', str_replace('/', DIRECTORY_SEPARATOR, $file), $match);
              if (isset($match[$entry['prefix']]))
              {
                $prefix = $match[$entry['prefix']].'/';
              }
            }

            $data[] = sprintf("'%s%s' => '%s',", $prefix, $class, str_replace('\\', '\\\\', $file));
          }
        }
      }
    }

    // compile data
    $retval = sprintf("<?php\n".
                      "// auto-generated by sfAutoloadConfigHandler\n".
                      "// date: %s\nreturn array(\n%s\n);\n",
                      date('Y/m/d H:i:s'), implode("\n", $data));

    return $retval;
  }

  /**
   * @see sfConfigHandler
   */
  static public function getConfiguration(array $configFiles)
  {
    $config = self::replaceConstants(self::parseYamls($configFiles));

    foreach ($config['autoload'] as $name => $values)
    {
      if (isset($values['path']))
      {
        $config['autoload'][$name]['path'] = self::replacePath($values['path']);
      }
    }

    return $config;
  }
}
