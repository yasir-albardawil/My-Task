<?php

namespace Drupal\custom_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueAuthor constraint.
 */
class UniqueAuthorConstraintValidator extends ConstraintValidator
{

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint)
  {
    if (!isset($entity)) {
      return;
    }

    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');
    $query = \Drupal::entityQuery($entity_type_id);
    $entity_id = $entity->id();

    // Using isset() instead of !empty() as 0 and '0' are valid ID values for
    // entity types using string IDs.
    if (isset($entity_id)) {
      $query->condition($id_key, $entity_id, '<>');
    }

    foreach ($constraint->bundles as $bundle) {
      if ($entity->bundle() == $bundle['name']) {

        $statement = $query
          ->condition('type', $bundle['name'])
          ->condition($bundle['field'], $entity->getOwnerId());

        $value_taken = (bool)$statement
          ->range(0, 1)
          ->count()
          ->execute();

        if ($value_taken) {
          $this->context->addViolation($constraint->message, [
            '%value' => $entity->getOwnerId(),
          ]);
        }
      }
    }
  }
}
