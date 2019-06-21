<?php

require __DIR__ . '/../vendor/autoload.php';

$view = new \mon\template\View;

echo $view->setExt('php')->fetch('template', ['title' => 'Test View']);