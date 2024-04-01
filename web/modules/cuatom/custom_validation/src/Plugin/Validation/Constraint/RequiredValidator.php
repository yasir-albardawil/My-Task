<?php


namespace Drupal\custom_validation\Plugin\Validation\Constraint;


use Drupal\node\Entity\Node;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Required constraint.
 */
class RequiredValidator extends ConstraintValidator
{
  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint)
  {
    if (empty($entity)) {
      return;
    }

    $e = $entity->getEntity();

    if ($constraint->validation_attributes['type'] == 'multi') {
      foreach ($constraint->validation_attributes['data'] as $row) {
        if ($row['value'] === $entity->value) {
          $fields = $row['fields'];
        }
      }
    } else if ($constraint->validation_attributes['value'] == $entity->value) {
      $fields = $constraint->fields;
    } else {
      return;
    }

    foreach ($fields as $field) {
      if (isset($field['type']) && ('ref-node' == $field['type'])) {
        $value = $e->get($field['name'])->target_id;
      } else {
        $value = $e->get($field['name'])->value;
      }

      if (empty($value)) {
        $this->context->addViolation($constraint->message, ['%value' => $field['name']]);
      }
    }
  }
}

