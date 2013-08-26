## UP for Laravel 4

UP is a file uploader with polymorphic relations.

### Installation

- [UP on Packagist](https://packagist.org/packages/teepluss/up)
- [UP on GitHub](https://github.com/teepluss/laravel4-up)

To get the lastest version of Theme simply require it in your `composer.json` file.

~~~
"teepluss/up": "dev-master"
~~~

You'll then need to run `composer install` to download it and have the autoloader updated.

Once Theme is installed you need to register the service provider with the application. Open up `app/config/app.php` and find the `providers` key.

~~~
'providers' => array(

    'Teepluss\Up\UpServiceProvider'

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

Migrate tables.

~~~
php artisan migrate --package=teepluss/up
~~~

## Usage

First you have to create a morph method for your model that want to use "UP".

~~~php
class Blog extends Eloquent {

    public function .....

    /**
     * Blog has many files upload.

     * @return AttachmentRelate
     */
    public function files()
    {
        return $this->morphMany('\Teepluss\Up\AttachmentRelates\Eloquent\AttachmentRelate', 'fileable');
    }

}
~~~

### After create a method "files", Blog can use "UP" to upload files.

Upload file and resizing.

~~~php
// Return an original file meta.
UP::upload(Blog::find(1), Input::file('userfile'))->getMasterResult();
UP::upload(User::find(1), Input::file('userfile'))->getMasterResult();

// Return all results files uploaded including resized.
UP::upload(Product::find(1), Input::file('userfile'))->resize()->getResults();

// If you have other fields in table attachments.
UP::upload(User::find(1), Input::file('userfile'), array('some_id' => 999))->getMasterResult();
~~~

Look up a file path.

~~~php
$blogs = Blog::with('files')->get();

foreach ($blogs as $blog)
{
    foreach ($blog->files as $file)
    {
        echo UP::lookup($file->attachment_id);

        // or lookup with scale from config.

        echo UP::lookup($file->attachment_id)->scale('l');
    }
}
~~~

Remove file(s) from storage.

~~~php
$attachmentId = 'b5540d7e6350589004e02e23feb3dc1f';

// Remove a single file.
UP::remove($attachmentId);

// Remove all files including resized.
UP::remove($attachmentId, true);
~~~

## Support or Contact

If you have some problem, Please contact teepluss@gmail.com

[![Alt Buy me a beer](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](
https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=admin%40jquerytips%2ecom&lc=US&item_name=Teepluss&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest)