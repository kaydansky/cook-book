<?php
/**
 * @author : AlexK
 * Date: 24-Nov-18
 * Time: 6:22 PM
 */

namespace Cookbook\Domain\Filter;

class AutocompleteOutput implements JsonOutput
{
    private $id;
    private $value;
    private $data;
    private $dataIngredient;
    private $dataRecipe;
    private $dataRaw;

    public function __construct(
        string $id = null,
        string $value = null,
        array $data = null,
        array $dataIngredient = null,
        array $dataRecipe = null,
        array $dataRaw = null)
    {
        $this->id = $id;
        $this->value = $value;
        $this->data = $data;
        $this->dataIngredient = $dataIngredient;
        $this->dataRecipe = $dataRecipe;
        $this->dataRaw = $dataRaw;
    }

    public function output()
    {
        $list = [];

        if (! $this->data && ! $this->dataIngredient && ! $this->dataRecipe && ! $this->dataRaw) {
            die(json_encode($list));
        }

        if ($this->dataIngredient) {
            foreach ($this->dataIngredient as $item) {
                $list[] = [
                    'id' => $item['ingredient_id'],
                    'value' => html_entity_decode($item['ingredient'])
                ];
            }
        }

        if ($this->dataRecipe) {
            foreach ($this->dataRecipe as $item) {
                $list[] = [
                    'id' => $item['recipe_id'],
                    'value' => 'RECIPE: '
                        . html_entity_decode($item['recipe_title'])
                        . ', AUTHOR: '
                        . $item['author_name']
                        . ', DESC.: '
                        . html_entity_decode(substr($item['description'], 0, 30))
                        . '...'
                ];
            }
        }

        if ($this->data) {
            foreach ($this->data as $item) {
                $list[] = [
                    'id' => $item[$this->id],
                    'value' => html_entity_decode($item[$this->value]),
                    'recipe_id' => isset($item['recipe_id']) ? $item['recipe_id'] : null
                ];
            }
        }

        if ($this->dataRaw) {
            foreach ($this->dataRaw as $item) {
                $list[] = $item;
            }
        }

        die(json_encode($list, JSON_INVALID_UTF8_SUBSTITUTE));
    }
}