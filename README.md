## UP for Laravel 4

UP is a file uploader with polymorphic relations.

### Installation

- [UP on Packagist](https://packagist.org/packages/teepluss/up)
- [UP on GitHub](https://github.com/teepluss/laravel-up)

To get the lastest version of Theme simply require it in your `composer.json` file.

~~~
"teepluss/up": "dev-master"
~~~

You'll then need to run `composer install` to download it and have the autoloader updated.

Once Theme is installed you need to register the service provider with the application. Open up `app/config/app.php` and find the `providers` key.

~~~
'providers' => array(

    ''Teepluss\Up\UpServiceProvider''

)
~~~

UP also ships with a facade which provides the static syntax for creating collections. You can register the facade in the `aliases` key of your `app/config/app.php` file.

~~~
'aliases' => array(

    'UP' => 'Teepluss\Up\Facades\UP'

)
~~~

Publish config using artisan CLI.

~~~
php artisan config:publish teepluss/up
~~~

## Usage

..... To be continue .....