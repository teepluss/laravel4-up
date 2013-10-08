<?php namespace Teepluss\Up\Attachments\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Teepluss\Up\Attachments\AttachmentInterface;

class Attachment extends Model implements AttachmentInterface {

    /**
     * DB table.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Model event.
     *
     * @return void
     */
    public static function boot()
    {
        static::deleted(function($attachment)
        {
            // Auto remove relates table.
            $attachment->relates()->delete();
        });
    }

    /**
     * Method to save data to db.
     *
     * You can pass extra parameter by extending this class
     * and change config attachment model.
     *
     * @param Attachment $result
     */
    public function add($result)
    {
        // If you want to add something else.
        // $result = array_merge($result, array(
        //     'user_id' => Auth::user()->id
        // ));

        //$attachment = new static();

        $this->fill($result);

        return $this->save();
    }

    /**
     * Attachment has many relates.
     *
     * @return object
     */
    public function relates()
    {
        $attachmentRelatesModel = \Config::get('up::attachmentRelates.model');

        if ( ! $attachmentRelatesModel)
        {
            $attachmentRelatesModel = '\Teepluss\Up\AttachmentRelates\Eloquent\AttachmentRelate';
        }

        return $this->hasMany($attachmentRelatesModel);
    }

}