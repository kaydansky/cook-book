<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 11:26 PM
 */

namespace Cookbook\Domain\Recipe;

use Cookbook\Output\OutputBuilder;

class yieldListPacker implements yieldPacker
{
    private $templateYields = 'Recipe/View/recipe_yields.html';
    private $templateYieldsContainer = 'Recipe/View/yield.html';
    private $templateYieldsService = 'Recipe/View/recipe_yields_srvice.html';
    private $templateYieldsPrint = 'Recipe/Print/yield.html';
    private $yields;

    public function __construct(array $yields = null)
    {
        $this->yields = $yields;
    }

    public function pack()
    {
        if (! $this->yields) {
            return false;
        }

        $builder = new OutputBuilder();
        $listYields = '';
        $yieldCount = 0;

        foreach ($this->yields as $value) {
            $listYields .= $builder
                ->setTemplate($this->templateYields)
                ->addBrackets([
                    'YIELD' => $value['unitType'],
                    'YIELD_TYPE' => $value['unitType'],
                    'YIELD_VALUE' => $value['quantity'],
                    'YIELD_UNIT_NAME' => $value['unit'],
                    'YIELD_COUNT' => 'Y' . $yieldCount++,
                    'YIELD_CODE' => $value['mimecode'],
                    'MEASUREMENT_SYSTEM' => $value['measurement_system'],
                    'CONVERSION' => $value['conversion'],
                    'CONVERSION_UNIT' => $value['conversion_unit']
                ])
                ->build()
                ->result;
        }

        return (new OutputBuilder())
            ->setTemplate($this->templateYieldsContainer)
            ->addBrackets(['YIELDS' => trim($listYields, ', ')])
            ->build()
            ->result;
    }

    public function packService()
    {
        $listYields = $this->listYields();

        if (! $listYields) {
            return false;
        }

        return (new OutputBuilder())
            ->setTemplate($this->templateYieldsService)
            ->addBrackets(['YIELDS' => trim($listYields, ', ')])
            ->build()
            ->result;
    }

    public function packPrint()
    {
        $listYields = $this->listYields();

        if (! $listYields) {
            return false;
        }

        return (new OutputBuilder())
            ->setTemplate($this->templateYieldsPrint)
            ->addBrackets(['YIELDS' => trim($listYields, ', ')])
            ->build()
            ->result;
    }

    private function listYields()
    {
        if (! $this->yields) {
            return false;
        }

        $listYields = '';

        foreach ($this->yields as $value) {
            $listYields .= '<em>' . $value['unitType'] . '</em>: ' . $value['quantity'] . ' ' . $value['unit'] . ', ';
        }

        return trim($listYields, ', ');
    }
}