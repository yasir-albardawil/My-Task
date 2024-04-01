<?php

namespace Drupal\advanced_language_selector\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * This is the StyleManager implementation.
 *
 * This class is the responsible to fetch all styles from configuration files
 * located in config folder.
 */
class StyleManager implements StyleManagerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The filesystem object.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new StyleManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\File\FileSystemInterface $fsi
   *   File system object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, FileSystemInterface $fsi) {
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $fsi;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle(string $key): array {
    return $this->getAvailableStyles()[$key] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableStyles(): array {
    $module = $this->moduleHandler->getModule("advanced_language_selector");
    $styles = [];
    $modulePath = $module->getPath();
    $stylesLocation = $modulePath . '/config/styles/';

    $files = $this->fileSystem->scanDirectory($stylesLocation, '/\.yml$/');
    foreach ($files as $file) {
      $raw = file_get_contents($file->uri);
      $style = Yaml::decode($raw);
      $style['templates_location'] = $modulePath . $style['templates_location'];
      // Ignore style if it haven't id attribute.
      if (empty($style['id'])) {
        continue;
      }
      $styles[$style['id']] = $style;
    }
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleSelector(array $styles): array {
    $module = $this->moduleHandler->getModule("advanced_language_selector");
    $ret = [];
    $modulePath = $module->getPath();
    $styleSelectorDef = $modulePath . '/config/style_selector.yml';
    $raw = file_get_contents($styleSelectorDef);
    $selector = Yaml::decode($raw);
    $options = [];
    foreach ($styles as $style) {
      $options[$style['id']] = $style['title'];
      $selector['properties']['look_and_feel']['properties']['theme']['default_value'] = $style['id'];
    }
    $selector['properties']['look_and_feel']['properties']['theme']['options'] = $options;
    $ret[$selector['id']] = $selector;
    return $selector;
  }

}
