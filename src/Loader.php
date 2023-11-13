<?php

namespace AdiosWireframe;

class Loader {

  public $twig;
  public array $options = [];
  public array $config = [];
  public array $data = [];

  public function __construct(array $options, array $config, $data) {
    $this->options = $options;
    $this->config = $config;
    $this->data = $data;

    $this->twig = new \Twig\Environment(
      new \Twig\Loader\FilesystemLoader([__DIR__ . '/templates', (string) ($options['wireframesDir'] ?? '')]),
      [
        'cache' => FALSE,
        'debug' => TRUE,
      ]
    );
  }

  public function render(string $wireframe): string {
    $html = '';
    $wireframe = $wireframe ?? 'index';
    $isWindow = (bool) ($this->options['isWindow'] ?? false);

    $wireframeContentHtml = $this->twig->load($wireframe . '.twig')->render([
      'config' => $this->config,
      'data' => $this->data,
    ]);

    if ($isWindow) {
      $html = $wireframeContentHtml;
    } else {
      $html = $this->twig->load('desktop.twig')->render([
        'config' => $this->config,
        'data' => $this->data,
        'desktopContentHtml' => $wireframeContentHtml,
      ]);
    }

    return $html;
  }
}