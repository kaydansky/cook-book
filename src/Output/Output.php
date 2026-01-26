<?php

namespace Cookbook\Output;

class Output
{

    protected $template;
    protected $data;
    protected $brackets;
    protected $message;
    public $result;

    public function __construct(OutputBuilder $builder)
    {
        if ($this->message && $this->template) {
            $this->data = $this->message;
            return $this->bind();
        }

        $this->template = $builder->template;
        $this->data = $builder->brackets ?: $builder->data;
        $this->message = $builder->message;

        $this->bind();
    }

    private function bind()
    {
        $this->result = $this->data ? $this->fillPlaceholders($this->template, $this->data) : $this->template;
    }

    private function fillPlaceholders($string, array $array)
    {
        if (! count($array)) {
            return $string;
        }

        $content = str_replace(array_keys($array), array_values($array), $string);

        return $content;
    }

}
