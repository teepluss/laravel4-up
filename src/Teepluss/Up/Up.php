<?php namespace Teepluss\Up;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Teepluss\Up\Uploader as Uploader;
use Teepluss\Up\Attachments\ProviderInterface as AttachmentProviderInterface;

class Up {

    /**
     * Config from main.
     *
     * @var array
     */
    public $config;

    /**
     * Inject config.
     *
     * @var array
     */
    public $inject = array();

    /**
     * Attachment Provider.
     *
     * @var Attachment
     */
    protected $attachmentProvider;

    /**
     * Uploader Driver.
     *
     * @var Uploader
     */
    protected $uploader;

    /**
     * Uploader with initialize.
     *
     * @var Uploader
     */
    protected $uploadInit;

    /**
     * Attachment Id.
     *
     * @var integer
     */
    protected $attachmentId;

    /**
     * Create Up instance.
     *
     * @param Repository                  $config
     * @param AttachmentProviderInterface $attachmentProvider
     * @param Uploader                    $uploader
     */
    public function __construct(Repository $config, AttachmentProviderInterface $attachmentProvider, Uploader $uploader)
    {
        $this->config = $config->get('up::config');

        $this->attachmentProvider = $attachmentProvider;

        $this->uploader = $uploader;
    }

    /**
     * Get attachment provider.
     *
     * @return Attachment
     */
    public function getAttachmentProvider()
    {
        return $this->attachmentProvider;
    }

    /**
     * Inject config to uploader core.
     *
     * @param  array  $inject
     * @return Uploader
     */
    public function inject($inject = array())
    {
        $this->inject = $inject;

        return $this;
    }

    /**
     * Init uploader.
     *
     * @param  Object $model Morph model
     * @return Uploader
     */
    public function uploadInit($model = null, $addition = array())
    {
        $attachmentProvider = $this->getAttachmentProvider();

        $config = array_merge(array(
            'onUpload' => function($result) use ($attachmentProvider, $model, $addition)
            {
                $data = array(
                    'id'        => $result['fileName'],
                    'master'    => $result['master'],
                    'scale'     => $result['scale'],
                    'path'      => $result['subpath'],
                    'name'      => $result['fileBaseName'],
                    'location'  => $result['filePath'],
                    'size'      => $result['fileSize'],
                    'mime'      => $result['mime'],
                    'dimension' => $result['dimension']
                );

                $data = array_merge($data, $addition);

                $attachmentProvider->create($data);

                // Add to morph here.
                if ($result['master'] == null and is_object($model))
                {
                    $model->files()->create(array('attachment_id' => $result['fileName']));
                }
            }
        ), $this->inject);

        $uploadInit = $this->uploader->inject($config);

        return $uploadInit;
    }

    /**
     * Upload file.
     *
     * @param  Object $model
     * @param  string $input
     * @param  array  $options
     * @return Up
     */
    public function upload($model, $input, $addition = array())
    {
        if ( ! is_object($model)) return;

        // The model is not set up morph.
        if ( ! method_exists($model, 'files'))
        {
            throw new \Exception('The model is not morph with AttachmentRelate.');
        }

        // Using uploader to upload, then insert to db.
        $this->uploadInit = $this->uploadInit($model, $addition)->add($input)->upload();

        return $this;
    }

    /**
     * Chaining uploader to resizing.
     *
     * @return Up
     */
    public function resize()
    {
        $this->uploadInit->resize();

        return $this;
    }

    /**
     * Get uploaded results.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->uploadInit->onComplete();
    }

    /**
     * Get only master result.
     *
     * @return mixed
     */
    public function getMasterResult()
    {
        $results = $this->getResults();

        return isset($results['original']) ? $results['original'] : false;
    }

    /**
     * Remove file from storage and db.
     *
     * @param  integer  $attachmentId
     * @param  boolean  $recursive
     * @return mixed
     */
    public function remove($attachmentId, $recursive = true)
    {
        // Prepare return.
        $results = array();

        // No data to remove
        if (empty($attachmentId))
        {
            return $result;
        }

        // Find a master.
        $sql = $this->getAttachmentProvider()->createModel()->whereId($attachmentId);

        // If recursive find related.
        if ($recursive == true)
        {
            $sql = $sql->orWhere(function($query) use ($attachmentId)
            {
                $query->whereMaster($attachmentId);
            });
        }

        // Get files.
        $files = $sql->get();

        if (count($files)) foreach ($files as $file)
        {
            // Input is a name with extension, but don't need any path.
            $input = $file->name;

            // Subpath from db.
            $subpath = trim($file->path, '/');

            // Inject a config, then remove a file related.
            $this->uploader->inject(array(
                'subpath'  => $subpath,
                'onRemove' => function($result) use ($file, &$results)
                {
                    $file->delete(false);

                    \Cache::forget('attachment-'.$file->getKey());

                    array_push($results, $result);
                }
            ))
            ->open($input)->remove();
        }

        return $results;
    }

    /**
     * Resize if scale if not exists.
     *
     * @param  integer $masterId
     * @param  string  $scale
     * @return mixed
     */
    public function resizeFromMasterFile($masterId, $scale)
    {
        $master = $this->getAttachmentProvider()->findById($masterId);

        $attachment = null;

        if ( ! empty($master) and $scale)
        {
            $config = array(
                'subpath' => trim($master->path, '/')
            );
            $this->uploadInit()->inject($config)->open($master->name)->resize($scale);

            $attachment = $this->getAttachmentProvider()->findById($master->id.'_'.$scale);
        }

        return $attachment;
    }

    /**
     * Look up file location.
     *
     * @param  integer $attachmentId
     * @return Up
     */
    public function lookup($attachmentId)
    {
        $this->attachmentId = $attachmentId;

        return $this;
    }

    /**
     * Chaining from lookup to scale.
     *
     * @param  string $scale
     * @return Up
     */
    public function scale($scale)
    {
        $this->attachmentId = $this->attachmentId.'_'.$scale;

        return $this;
    }

    /**
     * Render an image path with HTML.
     *
     * @return string
     */
    public function get()
    {
        $that = $this;

        // Using cache to reduce request.
        $ckey = 'attachment-'.$this->attachmentId;

        if ( ! $attachment = Cache::get($ckey))
        {
            $attachment = $that->getAttachmentProvider()->findById($that->attachmentId);

            Cache::put($ckey, $attachment, 60);
        }

        // Having scale, but not generate yet!
        if ( ! is_object($attachment) and strpos($this->attachmentId, '_'))
        {
            preg_match('|(.*)_(.*)|', $this->attachmentId, $matches);

            $attachment = $this->resizeFromMasterFile($matches[1], $matches[2]);
        }

        if (is_object($attachment))
        {
            $location = $attachment->getAttribute('location');

            if (preg_match('/^http/', $location))
            {
                return $location;
            }

            return $this->uploader->url($location);
        }

        $failure = array_get($this->config, 'placeholder');

        return ($failure instanceof Closure) ? $failure($this->attachmentId, $this) : false;
    }

    /**
     * To string will return path.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get() ?: '';
    }

}