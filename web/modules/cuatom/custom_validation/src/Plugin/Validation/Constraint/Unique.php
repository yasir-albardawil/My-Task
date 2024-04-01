<?php

namespace Drupal\custom_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the other value is selected and then make it required.
 *
 * @Constraint(
 *   id = "Unique",
 *   label = @Translation("Unique", context = "Validation"),
 *   type = "string"
 * )
 */
class Unique extends Constraint {

  // The message that will be shown if the value is already exists.
  public $message = '%value must be unique.';
  public $fields = [];
}
