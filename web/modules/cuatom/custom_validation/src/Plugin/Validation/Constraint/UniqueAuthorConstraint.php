<?php

namespace Drupal\custom_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * checks if the Author id is unique.
 *
 * @Constraint(
 *   id = "UniqueAuthor",
 *   label = @Translation("Unique Author", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class UniqueAuthorConstraint extends Constraint {

  /**
   * The message that will be shown if the Author is already exists
   *
   * @var string
   */
  public $message = 'Author ID %value must be unique.';
  public $bundles = [];
}
