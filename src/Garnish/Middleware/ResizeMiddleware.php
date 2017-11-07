<?php

namespace Creativestyle\Garnish\Middleware;


use Creativestyle\Garnish\Fetcher\FetchedFile;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;

/**
 * Resizes (thumbnalizes) pictures on-the-fly.
 *
 * Parameters:
 *
 * w - desired width
 * h - desired height
 * s - mode of operation, one of: cover, contain or exact
 *
 * cover - the WxH viewport is covered by the picture whole & cropped
 * contain - the picture is fit whole into the WxH viewport
 * (default) - the output picture will be exactly WxH without preserving aspect ratio
 *
 * If only one W or H is given then the picture is resized to fit preserving the aspect ratio. With the mode
 * ignored.
 */
class ResizeMiddleware implements MiddlewareInterface
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @param ImagineInterface $imagine
     */
    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function process(FetchedFile $fetchedFile, array $parameters)
    {
        $width = isset($parameters['w']) ? (int)$parameters['w'] : null;
        $height = isset($parameters['h']) ? (int)$parameters['h'] : null;
        $mode = isset($parameters['s']) ? $parameters['s'] : null;


        if (!$width && !$height) {
            return $fetchedFile;
        }

        $image = $this->imagine->open($fetchedFile->getFilename());
        $imageSize = $image->getSize();

        $targetSize = null;
        $cropStart = null;
        $computedSize = null;

        if ($width && $height) {
            $targetSize = new Box($width, $height);

            if ($mode === 'cover') {
                $wRatio = $targetSize->getWidth() / $imageSize->getWidth();
                $hRatio = $targetSize->getHeight() / $imageSize->getHeight();

                if ($wRatio > $hRatio) {
                    $computedSize = $imageSize->widen($width);
                    $cropStart = new Point(0, ($computedSize->getHeight() - $targetSize->getHeight()) / 2);
                } else {
                    $computedSize = $imageSize->heighten($height);
                    $cropStart = new Point(($computedSize->getWidth() - $targetSize->getWidth()) / 2, 0);
                }

            } elseif ($mode === 'contain') {
                $iRatio = $imageSize->getWidth() / $imageSize->getHeight();
                $tRatio = $targetSize->getWidth() / $targetSize->getHeight();

                if ($iRatio > $tRatio) {
                    $computedSize = $imageSize->widen($width);
                } else {
                    $computedSize = $imageSize->heighten($height);
                }
            } else {
                $computedSize = $targetSize;
            }
        }

        if (!$height) {
            $computedSize = $imageSize->widen($width);
        }

        if (!$width) {
            $computedSize = $imageSize->heighten($height);
        }

        $image->resize($computedSize, ImageInterface::FILTER_LANCZOS);

        if ($cropStart) {
            $image->crop($cropStart, $targetSize);
        }

        $image->save($fetchedFile->getFilename(), [
            'quality' => 93
        ]);

        return $fetchedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function parameterNames()
    {
        return [
            'w',
            'h',
            's'
        ];
    }
}