<?php

namespace Cookbook\Output;

class OutputBuilder
{

    public $template = '';
    public $data = [];
    public $brackets = [];
    public $message = '';

    public function setTemplate($template, $folder = '')
    {
        $path = $folder ?: PATH_TEMPLATES;
        $templatePath = $path . $template;

        if (! file_exists($templatePath)) {
            die("Template file \"$template\" not found");
        }

        if (! $f = @fopen($templatePath, 'r')) {
            die("Failed open template file \"$template\"");
        }

        $this->template = fread($f, filesize($templatePath));
        fclose($f);

        return $this;
    }

    public function setData(array $data)
    {
        if (! empty($data)) {
            $this->data = $data;
        }

        return $this;
    }

    public function addBrackets(array $array)
    {
        foreach ($array as $k => $v) {
            $this->brackets['{' . $k . '}'] = $v;
        }

        return $this;
    }

    public function renderMessage(string $template = null)
    {
        $this->template = $template ?: MESSAGE_TEMPLATE;

        if (isset($_SESSION['message'])) {
            $this->message = array('{MESSAGE}' => $_SESSION['message']);
            unset($_SESSION['message']);
        }

        return $this;
    }

    public function build()
    {
        return new Output($this);
    }

}
