<?php
/**
 * @author: AlexK
 * Date: 02-May-19
 * Time: 1:52 PM
 */

namespace Cookbook\Domain\Generalinfo;


interface GeneralInfoPacker
{
    public function description();

    public function dateDish();

    public function dateAdded();

    public function dateModified();

    public function source();

    public function author();

    public function approved();

    public function notes();

    public function categories();

    public function restrictions();

    public function times();

    public function titleRecipe();

    public function kitchenAssembly();

    public function diningAssembly();

    public function purveyors();

    public function marking();

    public function winePairing();

    public function china();
}