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

class Helper
{
    public static function slug($rawSlug, $spaceChar = '-', $maxLength = null, $replaceRegex = null, $iconv = true)
    {
        if ($iconv AND function_exists('iconv'))
            $rawSlug = @iconv('UTF-8', 'ASCII//TRANSLIT', $rawSlug);

        if (null === $replaceRegex)
            $replaceRegex = '/[^a-zA-Z0-9 -]/'; // TODO: move to a setting panel

        $slug = preg_replace($replaceRegex, '', $rawSlug);
        $slug = strtolower($slug);

        if (null !== $maxLength)
            $slug = substr($slug, 0, $maxLength);

        $slug = trim($slug);
        $slug = str_replace(' ', $spaceChar, $slug);
        return $slug;
    }
}
