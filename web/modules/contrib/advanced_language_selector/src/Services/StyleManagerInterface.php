<?php

namespace Drupal\advanced_language_selector\Services;

/**
 * Style Manager Interface.
 *
 * Provides the interface to work with styles.
 */
interface StyleManagerInterface {

  /**
   * Gets specified style.
   *
   * @param string $key
   *   The id of the style.
   *
   * @return array
   *   Theme definition.
   */
  public function getStyle(string $key): array;

  /**
   * Gets all the available styles.
   *
   * @return array
   *   An array with all styles definition.
   */
  public function getAvailableStyles(): array;

  /**
   * Gets style selector.
   *
   * @param array $styles
   *   The available styles.
   *
   * @return array
   *   An array with the style selector.
   */
  public function getStyleSelector(array $styles): array;

}
