<?php
/**
 * @author : AlexK
 * Date: 18-Nov-18
 * Time: 5:30 PM
 */

namespace Cookbook\Domain\Image;


interface ImagePacker
{
    public function packCarousel();

    public function packEditImage();

    public function packSelectStepImages();

    public function packStepImages($recipeId);

    public function packCarouselSteps($current);

    public function listImages($template);
}