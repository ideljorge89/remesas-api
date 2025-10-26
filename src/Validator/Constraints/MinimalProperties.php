<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 14/12/20
 * Time: 9:49
 */
namespace App\Validator\Constraints;


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MinimalProperties extends Constraint
{
    public $message = 'The product must have the minimal properties required ("description", "price")';
}