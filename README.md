# How to install

## Create an empty folder
Create an empty folder, e.g. `my-adios-app-wireframe`.

## Composer

  1. Create `composer.json` file, see below
  2. Run `composer install` in that folder.
  3. Create `config.php` file, see below.
  4. Create `index.php` file, see below.
  5. Copy a `logo.png` file containing logo of your project.
  6. For Apache web brower, create `.htaccess` file, see below.
  7. Open the `index.php` in your browser.
  8. Create `wireframes` folder and add your wireframes TWIG files. Start with `index.twig`, see example below.


**composer.json**
```
{
  "require": {
    "wai-blue/adios-wireframe": "dev-main"
  }
}
```

**config.php**
```php
$config = [];
$config['skin'] = 'wireframe'; // Optional. If empty, default skin will be used. Options: blue, cyan, green, wireframe
$config['projectName'] = 'My ADIOS app';
$config['rewriteBase'] = '/my-adios-app-wireframe';
$config['sidebarItems'] = [
  '' => ['text' => 'Home', 'icon' => 'fas fa-home'],
  'contacts' => ['text' => 'Contacts', 'icon' => 'fas fa-user'],
  'calendar' => ['text' => 'Calendar', 'icon' => 'fas fa-calendar'],
];
```

**index.php**
```php
require('vendor/autoload.php');
require('config.php');

$wireframe = $_REQUEST['wireframe'] ?? 'index';

$options = [
  'wireframesDir' => __DIR__ . '/wireframes',
  'isWindow' => (bool) ($_REQUEST['__IS_WINDOW__'] ?? false),
];

$loader = new \AdiosWireframe\Loader($options, $config);
echo $loader->render($wireframe);
```

**wireframes/index.twig**
```twig
<div class="row">
  <div class="col-6">
    <div class="row">
      {{ include('components/card.twig', {
        'title': 'Most active contacts',
        'content': include('components/table.twig', {
          'columns': [
            'Given Name',
            'Last Name',
            'Age'
          ]
        })
      }) }}
    </div>
  </div>
  <div class="col-6">
  </div>
</div>
```

**.htaccess**
```
RewriteEngine On

RewriteRule ^assets/(.*)$ vendor/wai-blue/adios-wireframe/src/assets/$1

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?wireframe=$1&%{QUERY_STRING}
```
