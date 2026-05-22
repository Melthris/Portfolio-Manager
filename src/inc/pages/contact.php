<?php
/**
 * Legacy wrapper.
 *
 * The older Portfolio Manager installs and links may still request `page=contact`.
 * The new/current public page is now `contactme`, but this wrapper keeps direct
 * includes safe if a deployment bypasses the route alias.
 * 
 * This will be removed in the next update and is more or less here temporarily so 
 * I can push this update and address this after.
 */

declare(strict_types=1);

include __DIR__ . '/contactme.php';
