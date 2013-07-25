<?php namespace Teepluss\Up;

use Illuminate\Support\ServiceProvider;
use Teepluss\Up\Attachments\Eloquent\Provider as AttachmentProvider;

class UpServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register package.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('teepluss/up');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAttachmentProvider();
		$this->registerUploader();
		$this->registerUp();
	}

	/**
	 * Register attachment provider.
	 *
	 * @return void
	 */
	protected function registerAttachmentProvider()
	{
		$this->app['up.attachment'] = $this->app->share(function($app)
		{
			$model = $app['config']->get('up::attachments.model');

			return new AttachmentProvider($model);
		});
	}

	/**
	 * Register uploader adapter.
	 *
	 * @return void
	 */
	public function registerUploader()
	{
		$this->app['up.uploader'] = $this->app->share(function($app)
		{
			return new Uploader($app['config'], $app['request'], $app['files']);
		});
	}

	/**
	 * Register core class.
	 *
	 * @return void
	 */
	protected function registerUp()
	{
		$this->app['up'] = $this->app->share(function($app)
		{
			$app['up.loaded'] = true;

			return new Up($app['up.attachment'], $app['up.uploader']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('attach', 'up');
	}

}