<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package     LC\Helpers\File
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for file processing system
 */
class File extends \SplFileInfo
{
    /** @var string The uniqued name string for this instance */
    private $name;
    /** @var string The uniqued string ID to append to the file name */
    private $uniqueId;
    /** @var array The dimension to be created for image upload */
    private $dimensions;
    /** @var string The upload directory path */
    private $uploadPath;
    /** @var const Type of file resize */
    private $resize;
    /** @var string The original uploaded file name */
    private $originalFileName;
    /** @var string The file name generated */
    private $fileName;
    /** @var array The uploaded file information */
    private $uploads;
    /** @var array The file upload error information */
    private $error;
    /** @var array The image filter setting */
    private $imageFilterSet;

    /**
     * Constructor
     * @param string $fileName Path to the file
     */
    public function __construct($fileName = '')
    {
        $this->name = $fileName;
        $this->uploadPath = FILE . 'tmp' . _DS_;
        $this->defaultImageFilterSet();
        if ($fileName) {
            parent::__construct($fileName);
        }
    }
    /**
     * Set default image filter set and merge with user's options
     * @return object File
     */
    private function defaultImageFilterSet()
    {
        $this->imageFilterSet = array(
            'maxDimension' => '800x600',
            'resizeMode'   => FILE_RESIZE_BOTH,
            'jpgQuality'   => 75
        );
        $this->imageFilterSet = array_merge($this->imageFilterSet, _cfg('imageFilterSet'));
        $this->setImageResizeMode($this->imageFilterSet['resizeMode']);
        return $this;
    }
    /**
     * Set image resize mode
     * @param  const  FILE_RESIZE_BOTH, FILE_RESIZE_WIDTH or FILE_RESIZE_HEIGHT
     * @return object File
     */
    private function setImageResizeMode($value)
    {
        if (in_array($value, array(FILE_RESIZE_BOTH, FILE_RESIZE_WIDTH, FILE_RESIZE_HEIGHT))) {
            $this->imageFilterSet['resizeMode'] = $value;
        } else {
            $this->imageFilterSet['resizeMode'] = FILE_RESIZE_BOTH;
        }
        return $this;
    }
    /**
     * Setter for the class properties
     * @param string $key The property name
     * @param mixed $value The value to be set
     * @return void
     */
    public function set($key, $value)
    {
        if ($key === 'resize' || $key === 'resizeMode') {
            $this->setImageResizeMode($value);
            return $this;
        }

        if ($key === 'maxDimension') {
            $this->imageFilterSet['maxDimension'] = $value;
            return $this;
        }

        if ($key === 'jpgQuality') {
            $this->imageFilterSet['jpgQuality'] = $value;
            return $this;
        }

        # if $uniqueId is explicitly given and $name was not explicity given
        # make $name and $uniqueId same
        if ($key === 'uniqueId' && $value & $this->name === $this->uniqueId) {
            $this->name = $value;
        }

        if ($key === 'uploadDir' || $key === 'uploadPath') {
            $value = rtrim(rtrim($value, '/'), _DS_) . _DS_;
            $this->uploadPath = $value;
        }

        $this->{$key} = $value;
        return $this;
    }
    /**
     * Getter for the class properties
     * @param string $key The property name
     * @return mixed $value The value of the property or null if $name does not exist.
     */
    public function get($key)
    {
        if ($key === 'uploadDir') {
            return $this->uploadPath;
        }
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return null;
    }
    /**
     * Getter for the orignal uploaded file name
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }
    /**
     * Getter for the file name generated
     */
    public function getFileName()
    {
        return $this->fileName;
    }
    /**
     * Getter for the property `error`
     * @return array The array of error information
     *
     *     array(
     *       'code' => 'Error code',
     *       'message' => 'Error message'
     *     )
     *
     */
    public function getError()
    {
        return $this->error;
    }
    /**
     * Get file upload error message for the given error code
     * @param  int $code The error code
     * @return string The error message
     */
    public function getErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = _t('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = _t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = _t('The uploaded file was only partially uploaded.');
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = _t('No file was uploaded.');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = _t('Missing a temporary folder.');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = _t('Failed to write file to disk.');
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = _t('File upload stopped by extension.');
                break;
            case FILE_UPLOAD_ERR_MOVE:
                $message = _t('The uploaded file is not valid.');
                break;
            case FILE_UPLOAD_ERR_IMAGE_CREATE:
                $message = _t('Failed to create image from the uploaded file.');
                break;
            default:
                $message = _t('Unknown upload error.');
                break;
        }
        return $message;
    }
    /**
     * Move the uploaded file into the given directory.
     * If the uploaded file is image, this will create the various images according to the given $dimension
     *
     * @param string|array $file The name 'xxx' from $_FILES['xxx']
     *    or The array of uploaded file information from $_FILES['xxx']
     *
     * @return  array The array of the uploaded file information:
     *
     *     array(
     *       'name'     => 'Name of the input element',
     *       'fileName' => 'The uploaded file name',
     *       'originalFileName' => 'The original file name user selected',
     *       'extension'=> 'The selected and uploaded file extension',
     *       'dir'      => 'The uploaded directory',
     *     )
     *
     */
    public function upload($file)
    {
        if (is_string($file)) {
            if (!isset($_FILES[$file])) {
                $this->error = array(
                    'code' => UPLOAD_ERR_NO_FILE,
                    'message' => $this->getErrorMessage(UPLOAD_ERR_NO_FILE)
                );
                return null;
            }
            $this->name = $file;
            $file = $_FILES[$file];
        }

        if (!isset($file['name']) || !isset($file['tmp_name'])) {
            $this->error = array(
                'code' => UPLOAD_ERR_NO_FILE,
                'message' => $this->getErrorMessage(UPLOAD_ERR_NO_FILE)
            );
            return null;
        }

        $fileName     = stripslashes($file['name']);
        $uploadedFile = $file['tmp_name'];
        $info         = pathinfo($fileName);
        $extension    = strtolower($info['extension']);
        $uploaded     = null;
        $path         = $this->uploadPath;
        $dimensions   = $this->dimensions;

        if ($fileName && $file['error'] === UPLOAD_ERR_OK) {
            $this->originalFileName = $fileName;
            $newFileName = $this->getNewFileName();

            if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                # non-image file
                $uploaded = $this->move($uploadedFile, $newFileName);
            } else {
                # image file
                if (isset($this->imageFilterSet['maxDimension']) && $this->imageFilterSet['maxDimension']) {
                    # Upload the primary image by the configured dimension in config
                    $uploaded = $this->resizeImageByDimension($this->imageFilterSet['maxDimension'], $uploadedFile, $newFileName, $extension);
                } else {
                    $uploaded = $this->move($uploadedFile, $newFileName);
                }
                # if the thumbnail dimensions are defined, create them
                if (is_array($this->dimensions) && count($this->dimensions)) {
                    $this->resizeImageByDimension($this->dimensions, $uploadedFile, $newFileName, $extension);
                }
            }
        } else {
            $this->error = array(
                'code' => $file['error'],
                'message' => $this->getErrorMessage($file['error'])
            );
        }

        if ($uploaded) {
            $this->uploads = array(
                'name'              => $this->name,
                'fileName'          => $uploaded,
                'originalFileName'  => $this->originalFileName,
                'extension'         => $extension,
                'dir'               => $this->get('uploadDir')
            );
        }

        return $this->uploads;
    }
    /**
     * Get a new unique file name
     *
     * @return string The file name
     */
    private function getNewFileName()
    {
        $this->fileName = $this->getUniqueId() . '.' . $this->guessExtension();
        return $this->fileName;
    }
    /**
     * Get a unique id string
     * @return string
     */
    private function getUniqueId()
    {
        return ($this->uniqueId) ? $this->uniqueId : uniqid();
    }
    /**
     * Return the extension of the original file name
     * @param  string $file The optional file name; if it is not given, the original file name will be used
     * @return string The extension or an empty string if there is no file
     */
    public function guessExtension($file = '')
    {
        $file = ($file) ? $file : $this->originalFileName;
        if ($this->originalFileName) {
            $info = pathinfo($this->originalFileName);
            return $info['extension'];
        } else {
            return '';
        }
    }
    /**
     * Move the uploaded file to the new location with new file name
     * @param  string $file         The source file
     * @param  string $newFileName  The new file name
     * @return string The new file name or null if any error occurs
     */
    protected function move($file, $newFileName)
    {
        $targetDir = $this->uploadPath;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        if (@move_uploaded_file($file, $targetDir . $newFileName)) {
            return $newFileName;
        } else {
            $this->error = array(
                'code' => FILE_UPLOAD_ERR_MOVE,
                'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_MOVE)
            );
            return null;
        }
    }
    /**
     * Resize the image file into the given width and height
     * @param  string|array $dimensions   The dimension or array of dimensions, e.g., '400x250' or array('400x250', '200x150')
     * @param  string       $file         The source file
     * @param  string       $newFileName  The new file name to be created
     * @param  string       $extension    The file extension
     * @return string       The new file name or null if any error occurs
     */
    protected function resizeImageByDimension($dimensions, $file, $newFileName, $extension = null)
    {
        $dimensions = is_string($dimensions) ? array($dimensions) : $dimensions;
        $extension = ($extension) ? $extension : strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension == "jpg" || $extension == "jpeg") {
            $img = imagecreatefromjpeg($file);
        } elseif ($extension == "png") {
            $img = imagecreatefrompng($file);
        } elseif ($extension == "gif") {
            $img = imagecreatefromgif($file);
        }

        if (isset($img) && $img) {
            if (isset($this->imageFilterSet['jpgQuality']) && is_numeric($this->imageFilterSet['jpgQuality'])) {
                $jpgQuality = $this->imageFilterSet['jpgQuality'];
            } else {
                $jpgQuality = 75;
            }

            foreach ($dimensions as $dimension) {
                $resize = explode('x', $dimension);
                $resizeWidth  = $resize[0];
                $resizeHeight = $resize[1];

                if ($this->imageFilterSet['resizeMode'] == FILE_RESIZE_WIDTH) {
                    $tmp = File::resizeImageWidth($img, $file, $resizeWidth);
                } elseif ($this->imageFilterSet['resizeMode'] == FILE_RESIZE_HEIGHT) {
                    $tmp = File::resizeImageHeight($img, $file, $resizeHeight);
                } else {
                    $tmp = File::resizeImageBoth($img, $file, $resizeWidth, $resizeHeight);
                }

                $targetDir = (is_string(func_get_arg(0))) ? $this->uploadPath : $this->uploadPath . $dimension . _DS_;
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                $targetFileName = $targetDir . $newFileName;

                if ($extension == "gif") {
                    imagegif($tmp, $targetFileName);
                } elseif ($extension == "png") {
                    imagesavealpha($tmp, true);
                    imagepng($tmp, $targetFileName);
                } else {
                    imagejpeg($tmp, $targetFileName, $jpgQuality);
                }

                imagedestroy($tmp);
            }
            if ($img) {
                imagedestroy($img);
                return $newFileName;
            }
        } else {
            $this->error = array(
                'code' => FILE_UPLOAD_ERR_IMAGE_CREATE,
                'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_IMAGE_CREATE)
            );
        }
        return null;
    }
    /**
     * Resize an image to a desired width and height by given width
     *
     * @param resource $img The image resource identifier
     * @param string $file The image file name
     * @param int $newWidth The new width to resize
     *
     * @return resource An image resource identifier on success, FALSE on errors
     */
    public static function resizeImageWidth(&$img, $file, $newWidth)
    {
        list($width, $height) = getimagesize($file);
        $newHeight = ($height/$width) * $newWidth;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($tmp, false);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $tmp;
    }
    /**
     * Resize an image to a desired width and height by given height
     *
     * @param resource $img The image resource identifier
     * @param string $file The image file name
     * @param int $newHeight The new height to resize
     *
     * @return resource An image resource identifier on success, FALSE on errors
     */
    public static function resizeImageHeight(&$img, $file, $newHeight)
    {
        list($width, $height) = getimagesize($file);
        $newWidth = ($width/$height) * $newHeight;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($tmp, false);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $tmp;
    }
    /**
     * Resize an image to a desired width and height by given width and height
     *
     * @param resource $img The image resource identifier
     * @param string $file The image file name
     * @param int $newWidth The new width to resize
     * @param int $newHeight The new height to resize
     *
     * @return resource An image resource identifier on success, FALSE on errors
     */
    public static function resizeImageBoth(&$img, $file, $newWidth, $newHeight)
    {
        list($width, $height) = getimagesize($file);

        $scale = min($newWidth/$width, $newHeight/$height);
        # If the image is larger than the max shrink it
        if ($scale < 1) {
            # new width for the image
            $newWidth = floor($scale * $width);
            # new heigth for the image
            $newHeight = floor($scale * $height);
        } else {
        # if the image is small than than the resized width and height
            $newWidth = $width;
            $newHeight = $height;
        }

        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($tmp, false);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $tmp;
    }
    /**
     * Display an image fitting into the desired dimension
     *
     * @param string $fileName The file name with an absolute web path
     * @param string $caption The image caption
     * @param int $dimension The actual image dimension in "widthxheight"
     * @param string $desiredDimension The desired dimension in "widthxheight"
     * @param array $attributes The HTML attributes in array like key => value
     *
     * @return string The <img> tag
     */
    public static function img($fileName, $caption, $dimension, $desiredDimension = '0x0', $attributes = array())
    {
        $regex = '/^[0-9]+x[0-9]+$/i'; # check the format of "99x99" for the dimensions
        if (!preg_match($regex, $dimension)) {
            echo '';
            return null;
        }
        if (!preg_match($regex, $desiredDimension)) {
            $desiredDimension = '0x0';
        }
        list($imgWidth, $imgHeight) = explode('x', strtolower($dimension));
        list($desiredWidth, $desiredHeight) = explode('x', strtolower($desiredDimension));

        if ($imgWidth > $desiredWidth || $imgHeight > $desiredHeight) {
            # scale down
            if ($desiredWidth == 0 && $desiredHeight > 0) {
                # resized to height
                $desiredWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
                $imgWidth     = $desiredWidth;
                $imgHeight    = $desiredHeight;
            } elseif ($desiredWidth > 0 && $desiredHeight == 0) {
                # resized to width
                $desiredHeight  = floor(($imgHeight/$imgWidth) * $desiredWidth);
                $imgWidth       = $desiredWidth;
                $imgHeight      = $desiredHeight;
            } elseif ($desiredWidth > 0 && $desiredHeight > 0) {
                # resized both
                $scale = min($desiredWidth/$imgWidth, $desiredHeight/$imgHeight);
                # new width for the image
                $imgWidth  = floor($scale * $imgWidth);
                # new heigth for the image
                $imgHeight = floor($scale * $imgHeight);
                if ($imgWidth < $desiredWidth || $imgHeight < $desiredHeight) {
                    $wDiff = $desiredWidth - $imgWidth;
                    $hDiff = $desiredHeight - $desiredWidth;
                    if ($wDiff > $hDiff) {
                        # resize to width
                        $imgHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
                        $imgWidth  = $desiredWidth;
                    } else {
                        # resize to height
                        $imgWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
                        $imgHeight = $desiredHeight;
                    }
                }
            } else {
                # if the desired dimension is not given
                $desiredWidth = $imgWidth;
                $desiredHeight = $imgHeight;
            }
        }

        $style = '';
        if ($imgWidth > $desiredWidth) {
            $marginH = floor(($imgWidth - $desiredWidth)/2);
            $style = 'margin-left:-'.$marginH.'px';
        }
        if ($imgHeight > $desiredHeight) {
            $marginV = floor(($imgHeight - $desiredHeight)/2);
            $style = 'margin-top:-'.$marginV.'px';
        }
        if (isset($attributes['style']) && $attributes['style']) {
            $style .= $attributes['style'];
        }
        $attributes['src']    = $fileName;
        $attributes['alt']    = _h($caption);
        $attributes['title']  = _h($caption);
        $attributes['width']  = $imgWidth;
        $attributes['height'] = $imgHeight;
        $attributes['style']  = $style;

        $attrHTML = '';
        foreach ($attributes as $key => $value) {
            $attrHTML .= ' ' . $key . '="' . $value .'"';
        }
        return '<img '.$attrHTML.' />';
    }
}

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for ajax-like file upload with instant preview if the preview placeholder is provided
 * @since PHPLucidFrame v 1.3.0
 */
class AsynFileUploader
{
    /** @var string The input name or the POST name */
    private $name;
    /** @var string The HTML id of the file browsing button */
    private $id;
    /** @var string The input label name that shown to the user */
    private $label;
    /** @var string The button caption */
    private $caption;
    /** @var string The uploaded file name */
    private $value;
    /** @var array The array of hidden values to be posted to the callbacks */
    private $hidden;
    /** @var string The directory path where the file to be uploaded permenantly */
    private $uploadDir;
    /** @var array The allowed file extensions; defaults to jpg, jpeg, png, gif */
    private $extensions;
    /** @var int The maximum file size allowed to upload in MB */
    private $maxSize;
    /** @var int The maximum file dimension */
    private $dimension;
    /** @var string URL that handles the file uploading process */
    private $uploadHandler;
    /** @var array Array of HTML ID of the buttons to be disabled while uploading */
    private $buttons;
    /** @var boolean Enable ajax file delete or not */
    private $isDeletable;
    /** @var boolean The uploaded file name is displayed or not */
    private $fileNameIsDisplayed;
    /** @var string The hook name that handles file upload process interacting the database layer */
    private $onUpload;
    /** @var string The hook name that handles file deletion process interacting the database layer */
    private $onDelete;

    /**
     * Constructor
     *
     * @param string/array anonymous The input file name or The array of property/value pairs
     */
    public function __construct()
    {
        $this->name                 = 'file';
        $this->id                   = '';
        $this->label                = _t('File');
        $this->caption              = _t('Choose File');
        $this->value                = array();
        $this->hidden               = array();
        $this->maxSize              = 10;
        $this->extensions           = array();
        $this->uploadDir            = FILE . 'tmp' . _DS_;
        $this->buttons              = array();
        $this->dimensions           = '';
        $this->uploadHandler        = WEB_ROOT . 'inc/asyn-file-uploader.php';
        $this->isDeletable          = true;
        $this->fileNameIsDisplayed  = true;
        $this->onUpload             = '';
        $this->onDelete             = '';

        if (func_num_args()) {
            $arg = func_get_arg(0);
            if (is_string($arg)) {
                $this->name = $arg;
            } elseif (is_array($arg)) {
                foreach ($arg as $key => $value) {
                    if (isset($this->{$key})) {
                        $this->{$key} = $value;
                    }
                }
            }
        }
    }
    /**
     * Setter for the property `name`
     * @param string $name The unique name for the file input element
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * Setter for the property `id`
     * @param string $id The unique HTML id for the file browsing button
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * Setter for the property `label`
     * @param string $label The caption name for the file input to use in validation error
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    /**
     * Setter for the property `caption`
     * @param string $caption The caption for image uploaded
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    }
    /**
     * Setter for the property `value`
     * @param array $value The file name saved in the database
     * @param int   $value The ID related to the file name saved in the database
     */
    public function setValue($value, $id = 0)
    {
        $this->value = array(
            $id => $value
        );
    }
    /**
     * Getter for the property `value`
     */
    public function getValue()
    {
        return is_array($this->value) ? current($this->value) : $this->value;
    }
    /**
     * Getter for the id saved in db related to the value
     */
    public function getValueId()
    {
        if (is_array($this->value)) {
            return current(array_keys($this->value));
        }
        return 0;
    }
    /**
     * Setter for the property `hidden`
     */
    public function setHidden($key, $value = '')
    {
        if (!in_array($key, array('id', 'dimensions', 'fileName', 'uniqueId'))) {
            # skip for reserved keys
            $this->hidden[$key] = $value;
        }
    }
    /**
     * Setter for the property `uploadDir`
     * @param string $dir The directory where the file will be uploaded. Default to /files/tmp/
     */
    public function setUploadDir($dir)
    {
        $this->uploadDir = $dir;
    }
    /**
     * Setter for the property `maxSize`
     * @param int $size The maximum file size allowed in MB
     */
    public function setMaxSize($size)
    {
        $this->maxSize = $size;
    }
    /**
     * Setter for the property `extensions`
     * @param array $extensions The array of extensions such as `array('jpg', 'png', 'gif')`
     */
    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
    }
    /**
     * Setter for the property `dimensions`
     * @param array $dimensions The array of extensions such as `array('600x400', '300x200')`
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }
    /**
     * Setter for the property `buttons`
     * @param string $arg1[,$arg2,$arg3,...] The HTML element ID for each button
     */
    public function setButtons()
    {
        $this->buttons = func_get_args();
    }
    /**
     * Setter for the property `isDeletable`
     * @param boolean $value If the delete button is provided or not
     */
    public function isDeletable($value)
    {
        $this->isDeletable = $value;
    }
    /**
     * Setter for the property `fileNameIsDisplayed`
     * @param boolean $value If the uploaded file name is displayed next to the button or not
     */
    public function isFileNameDisplayed($value)
    {
        $this->fileNameIsDisplayed = $value;
    }
    /**
     * Setter for the `onUpload` hook
     * @param string $callable The callback PHP function name
     */
    public function setOnUpload($callable)
    {
        $this->onUpload = $callable;
    }
    /**
     * Setter for the `onDelete` hook
     * @param string $callable The callback PHP function name
     */
    public function setOnDelete($callable)
    {
        $this->onDelete = $callable;
    }
    /**
     * Setter for the proprty `uploadHandler`
     * @param string $url The URL where file upload will be handled
     */
    private function setUploadHandler($url)
    {
        $this->uploadHandler = $url;
    }
    /**
     * Display file input HTML
     * @param array $attributes The HTML attribute option for the button
     *
     *     array(
     *       'class' => '',
     *       'id' => '',
     *       'title' => ''
     *     )
     *
     */
    public function html($attributes = array())
    {
        $name = $this->name;
        $maxSize = $this->maxSize * 1024 * 1024; # convert to bytes

        # HTML attribute preparation for the file browser button
        $attrHTML = array();
        $htmlIdForButton = false;
        $htmlClassForButton = false;
        foreach ($attributes as $attrName => $attrVal) {
            $attrName = strtolower($attrName);
            if ($attrName === 'class' && $attrVal) {
                $htmlClassForButton = true;
                $attrVal = 'asynfileuploader-button '.$attrVal;
            }
            if ($attrName === 'id' && $attrVal) {
                $this->id = $attrVal;
                $htmlIdForButton = true;
            }
            $attrHTML[] =  $attrName.'="'.$attrVal.'"';
        }
        if ($htmlIdForButton === false) {
            $this->id = 'asynfileuploader-button-'.$name;
            $attrHTML[] = 'id="'.$this->id.'"';
        }
        if ($htmlClassForButton === false) {
            $attrHTML[] = 'class="asynfileuploader-button button"';
        }
        $buttonAttrHTML = implode(' ', $attrHTML);

        $args   = array();
        $args[] = 'name=' . $name;
        $args[] = 'id=' . $this->id;
        $args[] = 'label=' . $this->label;
        $args[] = 'dir=' . base64_encode($this->uploadDir);
        $args[] = 'buttons=' . implode(',', $this->buttons);
        $args[] = 'phpCallback=' . $this->onUpload;
        $args[] = 'exts=' . implode(',', $this->extensions);
        $args[] = 'maxSize=' . $maxSize;
        if ($this->dimensions) {
            $args[] = 'dimensions=' . implode(',', $this->dimensions);
        }
        $handlerURL = $this->uploadHandler.'?'.implode('&', $args);

        # If setValue(), the file information is pre-loaded
        $id             = '';
        $value          = '';
        $currentFile    = '';
        $currentFileURL = '';
        $extension      = '';
        $uniqueId       = '';
        $dimensions     = array();
        $webUploadDir   = str_replace('\\', '/', str_replace(ROOT, WEB_ROOT, $this->uploadDir));

        if ($this->value && file_exists($this->uploadDir . $value)) {
            $value = $this->getValue();
            $id = $this->getValueId();
            $currentFile = basename($this->uploadDir . $value);
            $currentFileURL  = $webUploadDir . $value;
            $extension = pathinfo($this->uploadDir . $value, PATHINFO_EXTENSION);
            if (is_array($this->dimensions) && count($this->dimensions)) {
                $dimensions = $this->dimensions;
            }
        }

        # If the generic form POST, the file information from POST is pre-loaded
        # by overwriting `$this->value`
        if (count($_POST) && isset($_POST[$name]) && $_POST[$name] &&
            isset($_POST[$name.'-fileName']) && $_POST[$name.'-fileName']) {
            $post    = _post($_POST);
            $value   = $post[$name];
            $id      = isset($post[$name.'-id']) ? $post[$name.'-id'] : '';

            if (file_exists($this->uploadDir . $value)) {
                $currentFile = $value;
                $currentFileURL  = $webUploadDir . $value;
                $extension = pathinfo($this->uploadDir . $value, PATHINFO_EXTENSION);
                $uniqueId  = $post[$name.'-uniqueId'];
            }

            if (isset($post[$name.'-dimensions']) && is_array($post[$name.'-dimensions']) && count($post[$name.'-dimensions'])) {
                $dimensions = $post[$name.'-dimensions'];
            }
        }

        $preview = ($currentFile) ? true : false;
        ?>
        <div class="asynfileuploader" id="asynfileuploader-<?php echo $name; ?>">
            <div id="asynfileuploader-value-<?php echo $name; ?>">
                <input type="hidden" name="<?php echo $name; ?>" value="<?php if ($value) { echo $value; } ?>" />
                <input type="hidden" name="<?php echo $name; ?>-id" value="<?php if ($id) echo $id; ?>" />
                <?php foreach ($dimensions as $d) { ?>
                    <input type="hidden" name="<?php echo $name; ?>-dimensions[]" value="<?php echo $d; ?>" />
                <?php } ?>
            </div>
            <div id="asynfileuploader-hiddens-<?php echo $name; ?>">
                <?php foreach ($this->hidden as $hiddenName => $hiddenValue) { ?>
                    <input type="hidden" name="<?php echo $name; ?>-<?php echo $hiddenName; ?>" value="<?php echo $hiddenValue; ?>" />
                <?php } ?>
            </div>
            <input type="hidden" name="<?php echo $name; ?>-dir" value="<?php echo base64_encode($this->uploadDir); ?>" />
            <input type="hidden" name="<?php echo $name; ?>-fileName" id="asynfileuploader-fileName-<?php echo $name; ?>" value="<?php echo $currentFile; ?>" />
            <input type="hidden" name="<?php echo $name; ?>-uniqueId" id="asynfileuploader-uniqueId-<?php echo $name; ?>" value="<?php echo $uniqueId; ?>" />
            <div id="asynfileuploader-progress-<?php echo $name; ?>" class="asynfileuploader-progress">
                <div></div>
            </div>
            <div <?php echo $buttonAttrHTML; ?>>
                <span><?php echo $this->caption; ?></span>
                <iframe id="asynfileuploader-frame-<?php echo $name; ?>" src="<?php echo $handlerURL; ?>" frameborder="0" scrolling="no" style="overflow:hidden;"></iframe>
            </div>
            <div class="asynfileuploader-file-info">
                <?php if ($this->fileNameIsDisplayed) { ?>
                    <span id="asynfileuploader-name-<?php echo $name; ?>">
                    <?php if ($currentFile) { ?>
                        <a href="<?php echo $currentFileURL; ?>" target="_blank" rel="nofollow"><?php echo $currentFile ?></a>
                    <?php } ?>
                    </span>
                <?php } ?>
                <span id="asynfileuploader-delete-<?php echo $name; ?>" class="asynfileuploader-delete" <?php if (!$currentFile) echo 'style="display:none"'; ?>>
                <?php if ($this->isDeletable) { ?>
                    <a href="javascript:" rel="<?php echo $this->onDelete; ?>" title="Delete">
                        <span>Delete</span>
                    </a>
                <?php } ?>
                </span>
            </div>
            <span class="asynfileuploader-error" id="asynfileuploader-error-<?php echo $name; ?>"></span>
            <script type="text/javascript">
                LC.AsynFileUploader.init('<?php echo $name; ?>');
                <?php
                    if ($preview) {
                        $json = array(
                            'name'      => $name,
                            'value'     => $value,
                            'fileName'  => $currentFile,
                            'url'       => $currentFileURL,
                            'extension' => $extension,
                            'caption'   => $this->label
                        );
                        echo 'LC.AsynFileUploader.preview(' . json_encode($json) . ');';
                    }
                ?>
            </script>
        </div>
        <?php
    }
    /**
     * Get the upload directory name from REQUEST
     * @param
     */
    public static function getDirFromRequest($name)
    {
        return isset($_REQUEST[$name.'-dir']) ? _sanitize(base64_decode($_REQUEST[$name.'-dir'])) : '';
    }
}
