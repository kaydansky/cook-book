<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 11:25 PM
 */

namespace Cookbook\Domain\Recipe;


interface yieldPacker
{
    public function pack();

    public function packService();

    public function packPrint();
}