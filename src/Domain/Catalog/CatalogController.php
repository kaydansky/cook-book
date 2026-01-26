<?php
/**
 * @author : AlexK
 * Date: 24-Nov-18
 * Time: 5:01 PM
 */

namespace Cookbook\Domain\Catalog;

use Cookbook\{Domain\Image\ThumbnailBinder,
    Helpers\Format,
    Domain\Filter\AutocompleteFactory,
    Domain\Filter\SearchFactory};

class CatalogController
{
    private $containerList = 'Catalog/container_list.html';
    private $templateCard = 'Catalog/catalog_card.html';
    private $templateDash = 'Common/dash.html';
    private $modelName = 'Cookbook\Domain\Catalog\CatalogModel';
    private $model;
    private $builder;
    private $resolver;
    private $content;
    private $notification;
    private $path = [];
    private $auth;
    private $pageTitle = 'Catalog';
    private $paginator;
    private $data;
    private $descLimitCharacters = 140;

    public function inject($path, $auth, $builder, $resolver)
    {
        $this->path = array_replace($this->path, $path);
        $this->auth = $auth;
        $this->builder = $builder;
        $this->resolver = $resolver;
        $this->model = $this->resolver->resolve($this->modelName);
        $this->model->inject($auth);
    }

    public function action()
    {
        if ($this->path[2] === 'loggedin') {
            $this->notification = 'notif.Promo("top", "center", "<span>You are logged in</span>", "success");';
        }

        new AutocompleteFactory('catalog', $this->path[2], $this->model);
        $this->data = new SearchFactory('catalog', $this->model);
        $this->content = $this->bindRecipeList($this->data->result());
    }

    private function bindRecipeList($data)
    {
        if (! $data['data']) {
            return;
        }
//var_dump($data);
        $this->paginator = $data['paginator'];
        $this->pageTitle .= '&nbsp;<small>'
            . $data['totalRecords']
            . '&nbsp;' . 'items, page '
            . $data['currentPage']
            . ' of ' . $data['totalPages'] . '</small>';
        $countDishes = 1;
        $countRecipes = 1;
        $aDishes = null;
        $aRecipes = null;
        $contentDishes = '';
        $contentRecipes = '';

        foreach ($data['data'] as $value) {
            if (intval($value['recipe_id'])) {
                $aRecipes[] = $value;
            } else {
                $aDishes[] = $value;
            }
        }

        if ($aDishes) {
            foreach ($aDishes as $item) {
                if ($countDishes % 4 == 1) {
                    $contentDishes .= '<div class="row mb-4">';
                }

                $thumbnail = ThumbnailBinder::bindThumbnail($item['image_filenames'], 'dish', $item['id'], $item['title']);

                $contentDishes .= $this->builder
                    ->setTemplate($this->templateCard)
                    ->addBrackets([
                        'PRODUCT' => 'dish',
                        'BACKGROUND' => ' bg-light',
                        'THUMBNAIL' => $thumbnail,
                        'TITLE' => $item['title'],
                        'SUBTITLE' => $item['recipe_id'],
                        'DESCRIPTION' => strlen($item['description']) > $this->descLimitCharacters
                            ? '<a class="plus-cursor" data-toggle="popover" data-content="' . htmlspecialchars($item['description']) . '"><p class="card-text">'
                            . substr($item['description'], 0, $this->descLimitCharacters) . '...</p></a>' : ($item['description'] ?: '<p class="card-text text-muted">No description</p>'),
                        'ID' => $item['id'],
                        'DATE_ADDED' => $item['DateAdded'] ? Format::date($item['DateAdded']) : '',
                        'SOURCE' => $item['source'] ? '<p class="card-text mb-0"><small><span class="text-muted">Source:</span> ' . $item['source'] . '</small></p>' : '',
                        'AUTHOR' => $item['AuthorLastName'] ? '<p class="card-text mb-0"><small><span class="text-muted">Author:</span> ' . $item['AuthorFirstName'] . ' ' . $item['AuthorLastName'] . '</small></p>' : '',
                    ])
                    ->build()
                    ->result;

                if ($countDishes % 4 == 0) {
                    $contentDishes .= '</div>';
                }

                $countDishes++;
            }

            if ($countDishes % 4 != 1) {
                $contentDishes .= '</div>';
            }
        } else {
            $contentDishes = $this->builder->setTemplate($this->templateDash)->template;
        }

        if ($aRecipes) {
            foreach ($aRecipes as $item) {
                if ($countRecipes % 4 == 1) {
                    $contentRecipes .= '<div class="row mb-4">';
                }

                $thumbnail = ThumbnailBinder::bindThumbnail($item['image_filenames'], 'recipe', $item['id'], $item['title']);

                $contentRecipes .= $this->builder
                    ->setTemplate($this->templateCard)
                    ->addBrackets([
                        'PRODUCT' => 'recipe',
                        'BACKGROUND' => '',
                        'THUMBNAIL' => $thumbnail,
                        'TITLE' => $item['title'],
                        'SUBTITLE' => '',
                        'DESCRIPTION' => strlen($item['description']) > $this->descLimitCharacters
                            ? '<a class="plus-cursor" data-toggle="popover" data-content="' . htmlspecialchars($item['description']) . '"><p class="card-text">'
                            . substr($item['description'], 0, $this->descLimitCharacters) . '...</p></a>' : ($item['description'] ?: '<p class="card-text text-muted">No description</p>'),
                        'ID' => $item['id'],
                        'DATE_ADDED' => $item['DateAdded'] ? Format::date($item['DateAdded']) : '',
                        'SOURCE' => $item['source'] ? '<p class="card-text mb-0"><small><span class="text-muted">Source:</span> ' . $item['source'] . '</small></p>' : '',
                        'AUTHOR' => $item['AuthorLastName'] ? '<p class="card-text mb-0"><small><span class="text-muted">Author:</span> ' . $item['AuthorFirstName'] . ' ' . $item['AuthorLastName'] . '</small></p>' : '',
                    ])
                    ->build()
                    ->result;

                if ($countRecipes % 4 == 0) {
                    $contentRecipes .= '</div>';
                }

                $countRecipes++;
            }

            if ($countRecipes % 4 != 1) {
                $contentRecipes .= '</div>';
            }

        } else {
            $contentRecipes = $this->builder->setTemplate($this->templateDash)->template;
        }

        return [$contentDishes, $contentRecipes];
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->containerList);

        return [
            'CONTAINER' => $container->template,
            'ACTIVE_CATALOG' => ' active',
            'LISTING_DISHES' => $this->content[0],
            'LISTING_RECIPES' => $this->content[1],
            'QUERY' => $this->data->query(),
            'PAGE_TITLE' => $this->pageTitle,
            'NOTIFICATION' => $this->notification,
            'CATEGORY_NAME' => $this->data->categoryName(),
            'PAGINATOR' => $this->paginator,
            'QUERY_INGREDIENT' => $this->data->queryIngredient(),
            'IS_INGREDIENT_YES_CHECKED' => $this->data->isIngredientYesChecked(),
            'IS_INGREDIENT_NO_CHECKED' => $this->data->isIngredientNoChecked(),
            'WHOLE_WORD_CHECKED' => $this->data->wholeWordChecked(),
            'MY_ITEMS_CHECKED' => $this->data->isMyItemsChecked(),
            'DISHES_CHECKED' => $this->data->dishesChecked(),
            'RECIPES_CHECKED' => $this->data->recipesChecked(),
            'DAYS_RANGE' => $this->data->daysRange(),
            'META_TITLE' => 'Catalog',
        ];
    }
}