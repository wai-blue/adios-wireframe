<?php

$wireframe = $_REQUEST['wireframe'] ?? 'index';
$isWindow = (bool) ($_REQUEST['__IS_WINDOW__'] ?? false);

require('vendor/autoload.php');
require('config.php');

$twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/src');

$twig = new \Twig\Environment($twigLoader, [
  'cache' => FALSE,
  'debug' => TRUE,
]);

$wireframeContentHtml = $twig->load('wireframes/' . $wireframe . '.twig')->render();

if ($isWindow) {
  echo $twig->load('window.twig')->render([
    'config' => $config,
    'windowContentHtml' => $wireframeContentHtml,
  ]);
} else {
  echo $twig->load('desktop.twig')->render([
    'config' => $config,
    'desktopContentHtml' => $wireframeContentHtml,
  ]);
}