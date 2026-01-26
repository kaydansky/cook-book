<?php
/**
 * @author: AlexK
 * Date: 11-Oct-19
 * Time: 5:49 PM
 */

namespace Cookbook\Domain\Image;


class ThumbnailBinder
{
    static public function bindThumbnail($fileName, $product, $id, $title)
    {
        if ($fileName) {
            $a = strpos($fileName, ',') !== false
                ? explode(',', $fileName, 2)
                : [$fileName];

            return '<a href="/'
                . $product
                . '/'
                . $id
                . '"><img class="img-fluid p-1" src="/images/'
                . $product
                . '/'
                .  $a[0] . '_tn.jpg" alt="' . $title . '"></a>';
        } else {
            return '';
        }
    }
}