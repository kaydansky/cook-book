<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 2:29 PM
 */

namespace Cookbook\Domain\Recipe;


interface instructionPacker
{
    public function pack();
    public function packService();
}