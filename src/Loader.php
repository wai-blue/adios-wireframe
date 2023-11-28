<?php

namespace AdiosWireframe;

class Loader {

  public $twig;
  public array $options = [];
  public array $config = [];
  public array $data = [];

  public function __construct(array $options, array $config, array $data) {
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

    $this->twig->addFunction(new \Twig\TwigFunction(
      'str2url',
      function ($string) {
        return $this->str2url($string);
      }
    ));
  }

  /**
   * Removes special characters from string
   *
   * @param  string $string Original string
   * @return string String with removed special characters
   */
  public function rmspecialchars($string) {
    $from = ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '{', '}', '[', ']', ':', '|', ';', "'", '\\', ',', '.', '/', '<', '>', '?'];
    foreach ($from as $char) {
      $string = str_replace($char, '', $string);
    }

    return $string;
  }

  /**
   * Removes punctuation characters from string
   *
   * @param  string $string Original string
   * @return string String with removed punctuation characters
   */
  public function rmdiacritic($string) {
    $from = ['ŕ', 'ě', 'š', 'č', 'ř', 'š', 'ž', 'ť', 'ď', 'ľ', 'ĺ', 'ý', 'á', 'í', 'ä', 'é', 'ú', 'ü', 'ö', 'ô', 'ó', 'ň', 'Ě', 'Š', 'Č', 'Ř', 'Š', 'Ť', 'Ď', 'Ľ', 'Ĺ', 'Ž', 'Ý', 'Á', 'Í', 'É', 'Ú', 'Ü', 'Ó', 'Ó', 'Ň'];
    $to = ['r', 'e', 's', 'c', 'r', 's', 'z', 't', 'd', 'l', 'l', 'y', 'a', 'i', 'a', 'e', 'u', 'u', 'o', 'o', 'o', 'n', 'E', 'S', 'C', 'R', 'S', 'T', 'D', 'L', 'L', 'Z', 'Y', 'A', 'I', 'E', 'U', 'U', 'O', 'O', 'N'];

    return str_replace($from, $to, $string);
  }
  
  /**
   * Convert string with to URL-compatible string
   *
   * @param  string $string Original string
   * @param  bool $replaceSlashes If TRUE, slashes are replaced with hyphenation
   * @return string URL-compatible string
   */
  public function str2url($string, $replaceSlashes = TRUE) {
    if ($replaceSlashes) {
      $string = str_replace('/', '-', $string);
    }

    $string = preg_replace('/ |^(a-z0-9)/', '-', strtolower($this->rmspecialchars($this->rmdiacritic($string))));

    $string = preg_replace('/[^(\x20-\x7F)]*/', '', $string);
    $string = preg_replace('/[^(\-a-z0-9)]*/', '', $string);
    $string = trim($string, '-');

    while (strpos($string, '--')) {
      $string = str_replace('--', '-', $string);
    }

    return $string;
  }

  /**
   * Render the template
   *
   * @param  string $wireframe Name of the wireframe template
   * @return string Wireframe content in HTML
   */
  public function render(string $wireframe): string {
    $html = '';
    $wireframe = $wireframe ?? 'index';
    $isWindow = (bool) ($this->options['isWindow'] ?? false);
    $isPublicWebsite = (bool) ($this->options['isPublicWebsite'] ?? false);

    $wireframeContentHtml = $this->twig->load($wireframe . '.twig')->render([
      'config' => $this->config,
      'data' => $this->data,
    ]);

    if ($isPublicWebsite || $isWindow) {
      $html = $wireframeContentHtml;
    } else {
      $html = $this->twig->load($this->config['mainTemplate'] . '.twig')->render([
        'config' => $this->config,
        'data' => $this->data,
        'content' => $wireframeContentHtml,
      ]);
    }

    return $html;
  }
}