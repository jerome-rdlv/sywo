# README

How to use this:

* Create a new Kernel class, child of `\Rdlv\WordPress\Sywo\Kernel`
* If needed (no composer.json at project/plugin root) override `getProjectDir()`
* Create a `Resources/bundles.php` file, or override `registerBundles()`
* Parameters:
    * `kernel.secret`

An application (Kernel class) may add customization by overriding the `build` method.

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

