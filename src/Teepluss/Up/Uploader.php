<?php namespace Teepluss\Up;

use Closure;
use WideImage\WideImage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class Uploader {

    /**
     * Config from uploader.
     *
     * @var array
     */
    public $config;

    /**
     * Request.
     *
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * Files.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * File input.
     *
     * This can be path URL or $_FILES.
     *
     * @var mixed
     */
    protected $file;

    /**
     * Original file uploaded result or open file.
     *
     * @var array
     */
    protected $master;

    /**
     * Result last file uploaded.
     *
     * @var array
     */
    protected $result = array();

    /**
     * Result of all file uplaoded include resized.
     *
     * @var array
     */
    protected $results = array();

    /**
     * Create Uploader instance.
     *
     * @param Repository $config
     * @param Request    $request
     * @param Filesystem $files
     */
    public function __construct(Repository $config, Request $request, Filesystem $files)
    {
        // Get config from file.
        $this->config = $config->get('up::uploader');

        // Laravel request.
        $this->request = $request;

        // Laravel filesystem.
        $this->files = $files;
    }

    /**
     * Inject config.
     *
     * @param   array  $params
     * @return  Attach
     */
    public function inject($config = array())
    {
        if (is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }

        return $this;
    }

    /**
     * Add file to process.
     *
     * Input can be string URL or $_FILES
     *
     * @param   mixed  $file
     * @return  Attach
     */
    public function add($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Open the location path.
     *
     * $name don't need to include path.
     *
     * @param   string  $name
     * @return  Attach
     */
    public function open($name)
    {
        $location = $this->path($this->config['baseDir']).$name;

        // Generate a result to use as a master file.
        $result = $this->results($location);

        $this->master = $result;

        return $this;
    }

    /**
     * Hashed file name generate.
     *
     * Generate a uniqe name to be file name.
     *
     * @param   string  $file_name
     * @return  string
     */
    protected function name($filename)
    {
        // Get extension.
        $extension = $this->files->extension($filename);

        return md5(Str::random(30).time()).'.'.$extension;
    }

    /**
     * Find a base directory include appended.
     *
     * Destination dir to upload.
     *
     * @param   string  $base
     * @return  string
     */
    protected function path($base = null)
    {
        $path = $this->config['subpath'];

        // Path config can be closure.
        if ($path instanceof Closure)
        {
            return $path() ? $base.'/'.$path().'/' : $base.'/';
        }

        return $path ? $base.'/'.$path.'/' : $base.'/';
    }

    /**
     * Generate a view link.
     *
     * @param   string  $path
     * @return  string
     */
    public function url($path)
    {
        return $this->config['baseUrl'].$path;
    }

    /**
     * Uplaod a file to destination.
     *
     * @return Attach
     */
    public function upload()
    {
        // Find a base directory include appended.
        $path = $this->path($this->config['baseDir']);

        // Support old version.
        if (isset($this->config['remote']) and $this->config['remote'] == true)
        {
            $this->config['type'] = 'remote';
        }

        // Method to upload.
        $method = 'doUpload';

        switch ($this->config['type'])
        {
            case 'base64' : $method = 'doBase64'; break;
            case 'remote' : $method = 'doTransfer'; break;
        }

        // Call a method.
        $result = call_user_func_array(array($this, $method), array($this->file, $path));

        // If uploaded set a master add fire a result.
        if ($result !== false)
        {
            $this->master = $result;
            $this->addResult($result);
        }

        // Reset values.
        $this->reset();

        return $this;
    }

    /**
     * Upload from a file input.
     *
     * @param   SplFileInfo  $file
     * @param   string       $path
     * @return  mixed
     */
    protected function doUpload($file, $path)
    {
        if ( ! $file instanceof \SplFileInfo)
        {
            $file = $this->request->file($file);
        }

        // Original name.
        $origName = $file->getClientOriginalName();

        // Generate a file name with extension.
        $filename = $this->name($origName);

        if ($file->move($path, $filename))
        {
            $uploadPath = $path.$filename;

            return $this->results($uploadPath);
        }

        return false;
    }

    /**
     * Upload from a remote URL.
     *
     * @param   string  $file
     * @param   string  $path
     * @return  mixed
     */
    protected function doTransfer($url, $path)
    {
        // Craete upload structure directory.
        if ( ! is_dir($path))
        {
            mkdir($path, 0777, true);
        }

        // Original name.
        $origName = basename($url);

        // Generate a file name with extension.
        $filename = $this->name($url);

        // Get file binary.
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch,CURLOPT_CONNECTTIMEOUT,120);
        curl_setopt ($ch,CURLOPT_TIMEOUT,120);

        // Response returned.
        $bin = curl_exec($ch);

        curl_close($ch);

        // Path to write file.
        $uploadPath = $path.$filename;

        if ($this->files->put($uploadPath, $bin))
        {
            return $this->results($uploadPath);
        }

        return false;
    }

    /**
     * Upload from base64 image.
     *
     * @param  string $base64
     * @param  string $path
     * @return mixed
     */
    protected function doBase64($base64, $path)
    {
        // Craete upload structure directory.
        if ( ! is_dir($path))
        {
            mkdir($path, 0777, true);
        }

        $base64 = trim($base64);

        // Check pattern.
        if (preg_match('|^data:image\/(.*?);base64\,(.*)|', $base64, $matches))
        {
            $bin = base64_decode($matches[2]);

            $extension = $matches[1];

            $origName = 'base64-'.time().'.'.$extension;

            $filename = $this->name($origName);

            // Path to write file.
            $uploadPath = $path.$filename;

            if ($this->files->put($uploadPath, $bin))
            {
                return $this->results($uploadPath);
            }
        }

        return false;
    }

    /**
     * Add a new result uplaoded.
     *
     * @return void
     */
    protected function addResult($result)
    {
        // Fire a result to callback.
        $onUpload = $this->config['onUpload'];

        if ($onUpload instanceof Closure)
        {
            $onUpload($result);
        }

        $this->results[$result['scale']] = $result;
    }

    /**
     * Generate file result format.
     *
     * @param   string  $location
     * @param   string  $scale
     * @return  array
     */
    protected function results($location, $scale = null)
    {
        // Scale of original file.
        if (is_null($scale))
        {
            $scale = 'original';
        }

        // Try to get size of file.
        $fileSize = @filesize($location);

        // If cannot get size of file stop processing.
        if (empty($fileSize))
        {
            return false;
        }

        // Is this image?
        $isImage = false;

        // Get pathinfo.
        $pathinfo = pathinfo($location);

        // Append path without base.
        $path = $this->path();

        // Get an file extension.
        $fileExtension = $pathinfo['extension'];

        // File name without extension.
        $fileName = $pathinfo['filename'];

        // Base name include extension.
        $fileBaseName = $pathinfo['basename'];

        // Append path with file name.
        $filePath = $path.$fileBaseName;

        // Get mime type.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $location);

        // Dimension for image.
        $dimension = null;

        if (preg_match('|image|', $mime))
        {
            $isImage = true;

            $meta = getimagesize($location);

            $dimension = $meta[0].'x'.$meta[1];
        }

        // Master of resized file.
        $master = null;

        if ($scale !== 'original')
        {
            $master = str_replace('_'.$scale, '', $fileName);
        }

        return array(
            'isImage'       => $isImage,
            'scale'         => $scale,
            'master'        => $master,
            'subpath'       => $path,
            'location'      => $location,
            'fileName'      => $fileName,
            'fileExtension' => $fileExtension,
            'fileBaseName'  => $fileBaseName,
            'filePath'      => $filePath,
            'fileSize'      => $fileSize,
            'url'           => $this->url($filePath),
            'mime'          => $mime,
            'dimension'     => $dimension
        );
    }

    /**
     * Resize master image file.
     *
     * @param   array   $sizes
     * @return  Attach
     */
    public function resize($sizes = null)
    {
        // A master file to resize.
        $master = $this->master;

        // Master image valid.
        if ( ! is_null($master) and preg_match('|image|', $master['mime']))
        {
            // Path with base dir.
            $path = $this->path($this->config['baseDir']);

            // All scales available.
            $scales = $this->config['scales'];

            // If empty mean generate all sizes from config.
            if (empty($sizes))
            {
                $sizes = array_keys($scales);
            }

            // If string mean generate one size only.
            if (is_string($sizes))
            {
                $sizes = (array) $sizes;
            }

            if (count($sizes)) foreach ($sizes as $size)
            {
                // Scale is not in config.
                if ( ! array_key_exists($size, $scales)) continue;

                // Get width and height.
                list($w, $h) = $scales[$size];


                // Path with the name include scale and extension.
                $uploadPath = $path.$master['fileName'].'_'.$size.'.'.$master['fileExtension'];

                // Use WideImage to make resize and crop.
                WideImage::load($master['location'])
                    ->resize($w, $h, 'outside')
                    ->crop('center', 'middle', $w, $h)
                    ->saveToFile($uploadPath);

                // Add a result and fired.
                $result = $this->results($uploadPath, $size);


                // Add a result.
                $this->addResult($result);
            }
        }

        return $this;
    }

    /**
     * Remove master image file.
     *
     * @return  Attach
     */
    public function remove()
    {
        $master = $this->master;

        $stacks = array();

        if ( ! is_null($master))
        {
            $location = $master['location'];

            $this->files->delete($location);

            // Fire a result to callback.
            $onRemove = $this->config['onRemove'];

            if ($onRemove instanceof Closure)
            {
                $onRemove($master);
            }
        }

        return $this;
    }

    /**
     * Reset after uploaded master.
     *
     * @return void
     */
    protected function reset()
    {
        $this->file = null;
    }

    /**
     * Return all process results to callback.
     *
     * @return mixed
     */
    public function onComplete($closure = null)
    {
        return ($closure instanceof Closure) ? $closure($this->results) : $this->results;
    }

    /**
     * After end of all process fire results to callback.
     *
     * @return void
     */
    public function __destruct()
    {
        $onComplete = $this->config['onComplete'];

        if ($onComplete instanceof Closure)
        {
            $onComplete($this->results);
        }
    }

}