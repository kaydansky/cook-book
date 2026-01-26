<?php
/**
 * @author: AlexK
 * Date: 02-May-19
 * Time: 2:04 PM
 */

namespace Cookbook\Domain\Generalinfo;

use Cookbook\Helpers\Format;

class GeneralInfoFactory implements GeneralInfoPacker
{
    private $builder;
    private $modelUser = 'Cookbook\Domain\Users\UsersModel';
    private $categoryList;
    private $restrictionList;

    private $templateDescription = 'description.html';
    private $templateDateDish = 'date_dish.html';
    private $templateDateAdded = 'date_added.html';
    private $templateDateModified = 'date_modified.html';
    private $templateSource = 'source.html';
    private $templateAuthor = 'author.html';
    private $templateApproved = 'approved.html';
    private $templateNotes = 'notes.html';
    private $templateCategories = 'categories.html';
    private $templateRestrictions = 'restrictions.html';
    private $templateTimes = 'times.html';
    private $templateKitchenAssembly = 'kitchen_assembly.html';
    private $templateDinningAssembly = 'dinning_assembly.html';
    private $templatePurveyors = 'purveyors.html';
    private $templateMarking = 'marking.html';
    private $templateWinePairing = 'wine_pairing.html';
    private $templateChina = 'china.html';
    private $folder;

    public $description;
    public $dateDish;
    public $dateAdded;
    public $dateModified;
    public $source;
    public $author;
    public $approved;
    public $notes;
    public $categories;
    public $restrictions;
    public $times;
    public $titleRecipe;
    public $kitchenAssembly;
    public $diningAssembly;
    public $purveyors;
    public $marking;
    public $winePairing;
    public $china;
    public $data;
    public $authorName;
    public $approvedByName;

    public function __construct($builder, $resolver, $data, $categoryList, $restrictionList, $product, $print)
    {
        $this->builder = $builder;
        $this->data = $data;
        $this->categoryList = $categoryList;
        $this->restrictionList = $restrictionList;

        $modelUser = $resolver->resolve($this->modelUser);
        $this->authorName = $modelUser->getUserById($data['user_id']);
        $this->approvedByName = $modelUser->getUserById($data['approved_by']);

        $this->folder = $print ? 'Generalinfo/Print/' : 'Generalinfo/';

        $this->description($product);
        $this->dateDish();
        $this->dateAdded();
        $this->dateModified();
        $this->source();
        $this->author();
        $this->approved();
        $this->notes();
        $this->categories();
        $this->restrictions();
        $this->times();
        $this->titleRecipe();
        $this->kitchenAssembly();
        $this->diningAssembly();
        $this->purveyors();
        $this->marking();
        $this->winePairing();
        $this->china();
    }

    public function description()
    {
        $this->description = $this->data['description']
            ? $this->builder->setTemplate($this->folder . $this->templateDescription)
                ->addBrackets(
                    [
                        'DESCRIPTION_TEXT' => $this->data['description']
                    ]
                )
                ->build()->result
            : '';
    }

    public function dateDish()
    {
        $this->dateDish = isset($this->data['dish_date']) && $this->data['dish_date']
            ? $this->builder->setTemplate($this->folder . $this->templateDateDish)
                ->addBrackets(['DATE' => Format::date($this->data['dish_date'])])
                ->build()->result
            : '';
    }

    public function dateAdded()
    {
        $this->dateAdded = $this->data['date_added']
            ? $this->builder->setTemplate($this->folder . $this->templateDateAdded)
                ->addBrackets(['DATE' => Format::date($this->data['date_added'])])
                ->build()->result
            : '';
    }

    public function dateModified()
    {
        $this->dateModified = $this->data['date_modified']
            ? $this->builder->setTemplate($this->folder . $this->templateDateModified)
                ->addBrackets(['DATE' => Format::date($this->data['date_modified'])])
                ->build()->result
            : '';
    }

    public function source()
    {
        $this->source = $this->data['source']
            ? $this->builder->setTemplate($this->folder . $this->templateSource)
            ->addBrackets(
                [
                    'SOURCE_LINK' => ($this->data['source_link']
                        ? '<a href="' . $this->data['source_link'] . '" target="_blank">' . $this->data['source'] . '</a>'
                        : $this->data['source'])
                ])
            ->build()->result
            : '';
    }

    public function author()
    {
        $this->author = ! empty($this->authorName['first_name']) || ! empty($this->authorName['last_name'])
            ? $this->builder->setTemplate($this->folder . $this->templateAuthor)
                ->addBrackets(
                    [
                        'AUTHOR_FIRST_NAME' => $this->authorName['first_name'],
                        'AUTHOR_LAST_NAME' => $this->authorName['last_name'],
                    ])
                ->build()->result
            : '';
    }

    public function approved()
    {
        $approved = false;

        if ($this->data['approved']) {
            if (! empty($this->approvedByName['first_name']) || ! empty($this->approvedByName['last_name'])) {
                $approved = $this->approvedByName['first_name'] . '&nbsp;' . $this->approvedByName['last_name'];
            } elseif (! empty($this->authorName['first_name']) || ! empty($this->authorName['last_name'])) {
                $approved = $this->authorName['first_name'] . '&nbsp;' . $this->authorName['last_name'];
            } else {
                $approved = false;
            }
        }

        $this->approved = $approved
            ? $this->builder->setTemplate($this->folder . $this->templateApproved)
                ->addBrackets(['APPROVED_NAME' => $approved])
                ->build()->result
            : '';
    }

    public function notes()
    {
        $this->notes = ! empty($this->data['notes'])
            ? $this->builder->setTemplate($this->folder . $this->templateNotes)
                ->addBrackets(['NOTE' => $this->data['notes']])
                ->build()->result
            : '';
    }

    public function categories()
    {
        $this->categories = $this->categoryList
            ? $this->builder->setTemplate($this->folder . $this->templateCategories)
                ->addBrackets(['LIST' => $this->categoryList])
                ->build()->result
            : '';
    }

    public function restrictions()
    {
        $this->restrictions = $this->restrictionList
            ? $this->builder->setTemplate($this->folder . $this->templateRestrictions)
                ->addBrackets(['LIST' => $this->restrictionList])
                ->build()->result
            : '';
    }

    public function times()
    {
        $this->times = ! empty($this->data['prepare_hours']) || ! empty($this->data['prepare_min']) || ! empty($this->data['cook_hours']) || ! empty($this->data['cook_min'])
            ? $this->builder->setTemplate($this->folder . $this->templateTimes)
                ->addBrackets(
                    [
                        'PREPARE_TIME' => sprintf('%02d', $this->data['prepare_hours']) . ':' . sprintf('%02d', $this->data['prepare_min']),
                        'COOKING_TIME' => sprintf('%02d', $this->data['cook_hours']) . ':' . sprintf('%02d', $this->data['cook_min']),
                        'TOTAL_TIME' => sprintf('%02d', ($this->data['prepare_hours'] + $this->data['cook_hours'])) . ':' . sprintf('%02d', ($this->data['prepare_min'] + $this->data['cook_min']))
                    ])
                ->build()->result
            : '';
    }

    public function titleRecipe()
    {
        $this->titleRecipe = $this->data['alt_search_terms'] && isset($this->data['recipe_title'])
            ? '<u class="plus-cursor" style="border-bottom: 1px dashed #607e80; text-decoration: none;" data-toggle="popover" data-content="' . $this->data['alt_search_terms'] . '">' . $this->data['recipe_title'] . '</u>'
            : (isset($this->data['recipe_title']) ? $this->data['recipe_title'] : '');
    }

    public function kitchenAssembly()
    {
        $this->kitchenAssembly = ! empty($this->data['foh_kitchen_assembly'])
            ? $this->builder->setTemplate($this->folder . $this->templateKitchenAssembly)
                ->addBrackets(['TEXT' => $this->data['foh_kitchen_assembly']])
                ->build()->result
            : '';
    }

    public function diningAssembly()
    {
        $this->diningAssembly = ! empty($this->data['foh_dining_assembly'])
            ? $this->builder->setTemplate($this->folder . $this->templateDinningAssembly)
                ->addBrackets(['TEXT' => $this->data['foh_dining_assembly']])
                ->build()->result
            : '';
    }

    public function purveyors()
    {
        $this->purveyors = ! empty($this->data['foh_purveyors'])
            ? $this->builder->setTemplate($this->folder . $this->templatePurveyors)
                ->addBrackets(['TEXT' => $this->data['foh_purveyors']])
                ->build()->result
            : '';
    }

    public function marking()
    {
        $this->marking = ! empty($this->data['marking'])
            ? $this->builder->setTemplate($this->folder . $this->templateMarking)
                ->addBrackets(['TEXT' => $this->data['marking']])
                ->build()->result
            : '';
    }

    public function winePairing()
    {
        $this->winePairing = ! empty($this->data['wine_pairing'])
            ? $this->builder->setTemplate($this->folder . $this->templateWinePairing)
                ->addBrackets(['TEXT' => $this->data['wine_pairing']])
                ->build()->result
            : '';
    }

    public function china()
    {
        $this->china = ! empty($this->data['china_name'])
            ? $this->builder->setTemplate($this->folder . $this->templateChina)
                ->addBrackets(
                    [
                        'CHINA_ID' => $this->data['china_id'],
                        'CHINA_DESCRIPTION' => $this->data['manufacturer'],
                        'CHINA_NAME' => $this->data['china_name']
                    ]
                )->build()->result
            : '';
    }
}