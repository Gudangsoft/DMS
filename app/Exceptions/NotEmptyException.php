<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when deleting a category/subcategory that still contains documents
 * or subcategories. The message is user-facing (Indonesian).
 */
class NotEmptyException extends RuntimeException {}
