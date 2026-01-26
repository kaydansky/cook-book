<?php
/**
 * @author : AlexK
 * Date: 18-Nov-18
 * Time: 4:30 PM
 */

namespace Cookbook\Domain\Image;

use Cookbook\Output\OutputBuilder;

class ImageFactory implements ImagePacker
{

    private $templateCarousel = 'Image/carousel.html';
    private $templateEditImage = 'Image/edit_image.html';
    private $templateSelectStepImage = 'Image/select_step_images.html';
    private $templateStepImages = 'Image/step_images.html';
    private $product;
    private $files;
    private $filesStep = [];
    private $stepsData;

    public function __construct($product, $fileNames = false, $fileNamesStep = false, $stepsData = false)
    {
        $this->product = $product;

        if ($fileNames) {
            $this->files = explode(',', $fileNames);
        }

        if ($fileNamesStep) {
            $this->filesStep = explode(',', $fileNamesStep);
        }

        if ($stepsData) {
            $this->stepsData = $stepsData;
        }
    }

    public function packCarousel()
    {
        if (! $this->files) {
            return null;
        }

        $ol = '';
        $div = '';
        $sliderId = 'sliderRecipe';

        foreach ($this->files as $key => $file) {
            $active = $key == 0 ? 'active' : '';
            $ol .= '<li data-target="#' . $sliderId . '" data-slide-to="'
                . $key
                . '" class="'
                . $active
                . '"></li>';
            $div .= '<div class="carousel-item '
                . $active
                . '"><a title="View Original" href="/images/' . $this->product . '/' . $file
                . '.jpg" target="_blank"><img class="d-block w-100 rounded img-fluid" src="/images/'
                . $this->product
                . '/'
                . $file
                . '_lg.jpg?auto=yes&bg=777&fg=555" alt="Presentation Image"></a></div>';
        }

        return (new OutputBuilder())
            ->setTemplate($this->templateCarousel)
            ->addBrackets([
                'CAROUSEL_OL' => $ol,
                'CAROUSEL_DIV' => $div,
                'SLIDER_ID' => $sliderId
            ])
            ->build()
            ->result;
    }

    public function packEditImage()
    {
        if (empty($this->files[0]) || ! $this->files[0]) {
            return null;
        }

        $imgList = '';
        $builder = new OutputBuilder();

        foreach ($this->files as $value) {
            $imgList .= $builder
                ->setTemplate($this->templateEditImage)
                ->addBrackets([
                    'IMG_FILENAME' => $value,
                    'PRODUCT' => $this->product
                ])
                ->build()
                ->result;
        }

        return $imgList;
    }

    public function packSelectStepImages()
    {
        if (! $this->files[0]) {
            return 'No image added to the recipe. Upload files using "Overall Step Images" input above.';
        }

        $imgList = '';
        $builder = new OutputBuilder();

        foreach ($this->files as $value) {
            if (in_array($value, $this->filesStep)) {
                $highlight = 'bg-green';
                $checked = 'checked';
            } else {
                $highlight = '';
                $checked = '';
            }

            $imgList .= $builder
                ->setTemplate($this->templateSelectStepImage)
                ->addBrackets([
                    'IMG_FILENAME' => $value,
                    'PRODUCT' => $this->product,
                    'HIGHLIGHT' => $highlight,
                    'CHECKED' => $checked
                ])
                ->build()
                ->result;
        }

        return $imgList;
    }

    public function packStepImages($recipeId)
    {
        if (empty($this->files[0]) || ! $this->files[0]) {
            return null;
        }

        $imgList = '';
        $builder = new OutputBuilder();

        foreach ($this->files as $value) {
            $imgList .= $builder
                ->setTemplate($this->templateStepImages)
                ->addBrackets([
                    'IMG_FILENAME' => $value,
                    'PRODUCT' => $this->product,
                    'RECIPE-ID' => $recipeId
                ])
                ->build()
                ->result;
        }

        return $imgList;
    }

    public function packCarouselSteps($current)
    {
        if (! $this->stepsData) {
            return null;
        }

        $ol = '';
        $div = '';
        $sliderId = 'sliderSteps';
        $c = -1;
        $activeSet = 0;

        foreach ($this->stepsData as $key => $value) {
            foreach ($value as $file => $content) {
                if (! $activeSet && $file == $current) {
                    $active = 'active';
                    $activeSet = 1;
                } else {
                    $active = '';
                }

                $c++;

                $ol .= '<li data-target="#' . $sliderId . '" data-slide-to="'
                    . $c
                    . '" class="'
                    . $active
                    . '"></li>';
                $div .= '<div class="carousel-item '
                    . $active
                    . '"><a title="View Original" href="/images/' . $this->product . '/' . $file
                    . '.jpg" target="_blank"><img class="d-block w-100 rounded" style="max-height: 770px;" src="/images/'
                    . $this->product
                    . '/'
                    . $file
                    . '_lg.jpg?auto=yes&bg=777&fg=555" alt="Step ' . $key . 'Image"></a>'
                    . (strpos($key, 'x') === false ? '<div class="carousel-caption d-none d-md-block" style="background-color:rgba(0, 0, 0, 0.6);">'
                    . '<h5>Step ' . $key . '</h5>'
                    . ($content ? '<p>' . $content . '</p>' : '')
                    .'</div>' : '')
                    . '</div>';
            }
        }

        return (new OutputBuilder())
            ->setTemplate($this->templateCarousel)
            ->addBrackets([
                'CAROUSEL_OL' => $ol,
                'CAROUSEL_DIV' => $div,
                'SLIDER_ID' => $sliderId
            ])
            ->build()
            ->result;
    }

    public function listImages($template)
    {
        if (! $this->files) {
            return null;
        }

        $list = '';
        $builder = new OutputBuilder();

        foreach ($this->files as $file) {
            $list .= $builder
                ->setTemplate($template)
                ->addBrackets(['IMAGE_FILE' => $file])
                ->build()
                ->result;
        }

        return $list;
    }
}