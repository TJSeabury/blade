<?php

namespace ArdentIntent\Blade;

/**
 * Illuminate/view
 *
 * Requires: illuminate/filesystem
 *
 * @source https://github.com/illuminate/view
 */
class Blade extends \ArdentIntent\Asingleton\AbstractSingleton
{
  // Configuration: Note that you can set several directories where your templates are located
  private $viewPaths = array();
  private $compiledViewsPath = '';
  private $filesystem = null;
  private $eventDispatcher = null;
  private $viewResolver = null;
  private $viewFinder = null;

  private $initialized = false;

  public function addViewPath(string $path)
  {
    $this->viewPaths = array_unique(array_merge(
      $this->viewPaths,
      array($path)
    ));
    return $this->viewPaths;
  }

  protected function __construct()
  {
    // We could declare the default view paths here, 
    // but let's leave that up to the user to do with 'addViewPath'.
    // $this->viewPaths[] = realpath(__DIR__ . '/frontend/views');
    // $this->viewPaths[] = realpath(__DIR__ . '/admin/views');

    // Let's declare the compiled view path to start.
    $uploads = wp_upload_dir();
    $this->compiledViewsPath =  $uploads['basedir'] . '/Blade/Compiled/';

    add_action('init', [$this, 'init']);
  }

  public function init()
  {
    // Dependencies
    $this->filesystem = new \Illuminate\Filesystem\Filesystem;
    $this->eventDispatcher = new \Illuminate\Events\Dispatcher(
      new \Illuminate\Container\Container
    );
    $this->viewResolver = new \Illuminate\View\Engines\EngineResolver;

    $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler(
      $this->filesystem,
      $this->compiledViewsPath
    );

    $this->viewResolver->register(
      'blade',
      function () use ($bladeCompiler) {
        return new \Illuminate\View\Engines\CompilerEngine(
          $bladeCompiler
        );
      }
    );

    $this->viewFinder = new \Illuminate\View\FileViewFinder(
      $this->filesystem,
      $this->viewPaths
    );

    $this->initialized = true;
  }

  public function render($viewName, $props)
  {
    if (false === $this->initialized) $this->init();

    // Create View Factory capable of rendering PHP and Blade templates
    $this->viewFactory = new \Illuminate\View\Factory(
      $this->viewResolver,
      $this->viewFinder,
      $this->eventDispatcher
    );

    // Return the view.
    return $this->viewFactory->make($viewName, $props)->render();
  }
}
