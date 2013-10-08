<?php namespace Teepluss\Up\AttachmentRelates\Eloquent;

use Illuminate\Database\Eloquent\Model;

class AttachmentRelate extends Model {

    /**
     * DB table.
     *
     * @var string
     */
    protected $table = 'attachment_relates';

    /**
     * We don't need timestamps.
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * Feel free to add anything.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Morph to.
     *
     * @return AttachmentRelate
     */
    public function fileable()
    {
        return $this->morphTo();
    }

    /**
     * Belongs to attachment.
     *
     * @return Attachment
     */
    public function attachment()
    {
        $attachmentModel = \Config::get('up::attachments.model');

        if ( ! $attachmentModel)
        {
            $attachmentModel = '\Teepluss\Up\Attachments\Eloquent\Attachment';
        }

        return $this->belongsTo($attachmentModel);
    }

}