<?php
/**
 * DoctrineExtensions Slug
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace DoctrineExtensions\Slug;

class Exception extends \Exception
{
    static public function slugAlreadyExists($class, $id, $slug)
    {
        return new self("Entity {$class} with identifier {$id} has a slug collision ('{$slug}' is already in use)");
    }
}
