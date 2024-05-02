<?php

namespace AdiosWireframe;

class Loader {

  public $twig;
  public array $options = [];
  public array $config = [];
  public array $data = [];
  public array $routing = [];

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
    $this->twig->addFunction(new \Twig\TwigFunction(
      'dump',
      function ($var) {
        return var_dump($var);
      }
    ));
  }

  /**
   * Removes special characters from string
   *
   * @param  string $string Original string
   * @return string String with removed special characters
   */
  public function rmspecialchars(string $string): string {
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
  public function rmdiacritic(string $string): string {
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
  public function str2url(string $string, bool $replaceSlashes = TRUE): string {
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

  public function isUserAuthed(): bool {
    return !empty($_SESSION['user']) && $_SESSION['user'] !== null && $_SESSION['user'] !== '_public';
  }

  public function userAuth(string $user, string $pass): ?string {
    $_SESSION['user'] = NULL;

    if (
      empty($this->config['auth']['user'])
      || empty($this->config['auth']['pass'])
    ) {
      $_SESSION['user'] = '_public';
    }

    if (
      $user == $this->config['auth']['user']
      && $pass == $this->config['auth']['pass']
    ) {
      $_SESSION['user'] = $user;
    }

    return $_SESSION['user'];
  }

  public function userDeauth() {
    unset($_SESSION['user']);
  }

  public function setRouting(array $routing): void {
    $this->routing = $routing;
  }

  public function replaceRouteVariables($routeParams, $variables) {
    if (is_array($routeParams)) {
      foreach ($routeParams as $paramName => $paramValue) {

        if (is_array($paramValue)) {
          $routeParams[$paramName] = $this->replaceRouteVariables($paramValue, $variables);
        } else {
          foreach ($variables as $k2 => $v2) {
            $routeParams[$paramName] = str_replace('$'.$k2, $v2, $routeParams[$paramName]);
          }
        }
      }
    }

    return $routeParams;
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

    $params = $_GET;

    $hideDesktop = FALSE;

    foreach ($this->routing as $routePattern => $route) {
      if (preg_match($routePattern, $wireframe, $m)) {
        $wireframe = $route['wireframe'];
        $hideDesktop = $route['hideDesktop'] ?? FALSE;

        $route['params'] = $this->replaceRouteVariables($route['params'], $m);

        foreach ($route['params'] as $k => $v) {
          $params[$k] = $v;
        }
      }
    }

    $renderParams = [
      'config' => $this->config,
      'data' => $this->data,
      'userAuthed' => $this->isUserAuthed(),
      'params' => $params,
      '_COOKIE' => $_COOKIE,
    ];

    $wireframeContentHtml = $this->twig->load($wireframe . '.twig')->render($renderParams);

    if ($isPublicWebsite || $isWindow || $hideDesktop) {
      $html = $wireframeContentHtml;
    } else {

      $renderParams['content'] = $wireframeContentHtml;
      if (
        !empty($this->config['auth']['user'])
        && !empty($this->config['auth']['pass'])
        && !$this->isUserAuthed()
      ) {
        $html = $this->twig->load('login.twig')->render($renderParams);
      } else {
        $html = $this->twig->load($this->config['mainTemplate'] . '.twig')->render($renderParams);
      }
    }

    return $html;
  }
}