<?php

namespace Drupal\custom_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Checks that the other value is selected and then make it required.
 *
 * @Constraint(
 *   id = "Required",
 *   label = @Translation("Required", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class Required extends Constraint
{

  // The message that will be shown if the value is empty.
  public $message = '%value field is required';
  public $fields = [];
  public $validation_attributes = [];
}
