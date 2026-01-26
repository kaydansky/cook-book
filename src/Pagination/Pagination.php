<?php

namespace Cookbook\Pagination;

use JasonGrimes\Paginator;

class Pagination
{

    public $itemsPerPage = 20;
    public $totalRecords;
    public $totalPages;
    public $currentPage;

    private $db;
    private $queryTotal;
    private $queryPage;
    private $queryData;

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function getData()
    {
        $q = $this->db->selectRow($this->queryTotal, $this->queryData);
        $this->totalRecords = $q['total'];
        $this->totalPages = ceil($this->totalRecords / $this->itemsPerPage);
        $this->currentPage = min($this->totalPages,
            filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'default'   => 1,
                        'min_range' => 1,
                    ],
                ]
            )
        );

        $this->currentPage = $this->currentPage > 0 ? $this->currentPage : 1;
        $offset = ($this->currentPage - 1) * $this->itemsPerPage;
        $this->db->exec('SET NAMES \'utf8\'');
        $this->queryPage .= " LIMIT $offset, $this->itemsPerPage";

        return $this->db->select($this->queryPage, $this->queryData);
    }

    public function getPaginator()
    {
        $uri = preg_replace('/[\?,&]p=\d+/m', '', $_SERVER['REQUEST_URI']);
        $urlPattern = strpos($uri, '?') !== false ? $uri . '&p=(:num)' : $uri . '?p=(:num)';
        return new Paginator($this->totalRecords, $this->itemsPerPage, $this->currentPage, $urlPattern);
    }

}