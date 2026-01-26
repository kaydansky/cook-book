<?php
/**
 * @author : AlexK
 * Date: 25-Nov-18
 * Time: 12:49 AM
 */

namespace Cookbook\Domain\Filter;


interface SearchResult
{
    public function result();

    public function wholeWordChecked();

    public function isIngredientNoChecked();

    public function isIngredientYesChecked();

    public function isMyItemsChecked();

    public function query();

    public function queryIngredient();

    public function daysRange();

    public function categoryName();

    public function source();

    public function rangeDateAddedChecked();

    public function rangeDateModifiedChecked();

    public function rangeDishDateChecked();

    public function rangeFrom();

    public function rangeTo();

    public function sortAz();

    public function sortAdded();

    public function sortModified();

    public function author();

    public function recipeChecked();

    public function dishChecked();
}