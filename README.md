# README

How to use this:

* Create a new Kernel class, child of `\Rdlv\WordPress\Sywo\Kernel`
* If needed (no composer.json at project/plugin root) override `getProjectDir()`
* Create a `Resources/bundles.php` file, or override `registerBundles()`
* Parameters:
    * `kernel.secret`

An application (Kernel class) may add customization by overriding the `build`
method.

For example:

```php
class Kernel extends \Rdlv\WordPress\Sywo\Kernel
{
    protected function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container)
            {
                $container->getDefinition('twig.loader.native_filesystem')->setPublic(true);
            }
        });
    }
}
```

This will allow to override templates at runtime:

```php
$hooks->add('init', function (ContainerInterface $container) {
    /** @var FilesystemLoader $loader */
    $loader = $container->get('twig.loader.native_filesystem');
    $loader->prependPath(__DIR__ . '/templates');
});
```

## Form hooks

* form/build
* form/{name}/build
* form/view/build
* form/{name}/view/build
* form/view/finish
* form/{name}/view/finish

## Twig hooks

* twig/render

## Translations

## WiP notes

* Sywo defines a default `config` directory and loads `bundles.php` and
  `services.yaml` from there.
* `symfony/framework-bundle` is not mandatory
* For translation update command to work, both `symfony/framework-bundle`
  and `symfony/translation` are needed
* For twig, require `symfony/twig-bridge` and add bundles
  * `TwigBundle`
  * `TwigExtraBundle`
* Explain routing in WordPress page context
* Application may define multiple routes set and chose between then with the 
  `_router` set in `Request` attributes like so (show example).
* Sywo do not customize cache, log and build dirs, but you should