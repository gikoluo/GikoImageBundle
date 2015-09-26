<?php
namespace Giko\ImageBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Application\Sonata\MediaBundle\Entity\Media;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ImageController extends Controller
{
    /**
     * 上传图片
     * 
     * @Route("/u", name="image_upload")
     * @Method({"POST"})
     * @Template()
     * @ApiDoc(
	 *  resource=true,
	 *  description="上传照片",
	 *  section="Image",
	 *  parameters={
     *      {"name"="file", "dataType"="file", "required"=true, "description"="file"},
     *      {"name"="context", "dataType"="string", "required"=true, "description"="类型"},
     *      {"name"="flowFilename", "dataType"="string", "required"=true, "description"="文件名"}
     *  }
	 * )
     */
    public function uploadAction ()
    {
        /* @var Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $this->getRequest()->files->get('file');
        $context = $this->getRequest()->get('context', 'default');
        $provider = $this->getRequest()->get('provider', 'sonata.media.provider.image');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            print_r( $file );
            return new ServiceUnavailableHttpException(
                    json_encode(
                            array(
                                    'event' => 'uploader:error',
                                    'data' => array(
                                            'message' => 'Missing file.'
                                    )
                            )));
        }
        $mediaManager = $this->container->get('sonata.media.manager.media');
        $media = new Media();
        $media->setBinaryContent($file);
        $media->setContext($context);
        $media->setProviderName($provider);
        $media->setProviderName('sonata.media.provider.image');

        /*
         * If you have other providers in your app like vimeo /dailymotion etc
         * then do set here i have shown you the demo for adding image/file in
         * sonata media
         */
        /* other setters if you want to set like enabled/disable etc */
        $exif = @exif_read_data($file->getRealPath());//获取exif信息
        if (isset($exif['Orientation']) && $exif['Orientation'] == 6 && $exif['MimeType'] == "image/jpeg")
        {
            $this->rotate($file->getRealPath(), -90, 0);
        }
        $mediaManager->save($media);
        return $media;
    }

    /*
       图片旋转
     * $fileName 图片名字
     * $degrees 角度
     * */
    public function rotate($fileName, $degrees)
    {
        $source = imagecreatefromjpeg($fileName);
        $rotate = imagerotate($source, $degrees, 0);
        return imagejpeg($rotate, $fileName);
    }
    
    /**
     * 下载网络图片并改变大小
     *
     * @Route("/vurl/{url}/{size}", name="imageurl_view", requirements={"url"=".+"})
     * @Method({"GET"})
     * @ApiDoc(
     *  resource=true,
     *  description="查看图片. example: http://127.0.0.1:8000/i/v/http://www.baidu.com/abcd/320x100@2x ",
     *  section="Image"
     * )
     */
    public function viewurlAction ($url, $size)
    {
        $retinaTime = 1;
        
        if(strpos($size, "@3x") > 0) {
            $retinaTime = 3;
            $size = str_replace("@3x", "", $size);
        }
        else if(strpos($size, "@2x") > 0) {
            $retinaTime = 2;
            $size = str_replace("@2x", "", $size);
        }
        
        if (strpos($size, "x") > 0) {
            list($width, $height) = explode("x", $size); 
        }
        else {
            $width = $size;
            $height = $size;
        }
        $width *= $retinaTime;
        $height *= $retinaTime;
        
        $image = new \Imagick(  );
        $image->readImageBlob(file_get_contents($url));
        $image->setImageFormat('png');
        $imageprops = $image->getImageGeometry();
        if ($imageprops['width'] <= $width && $imageprops['height'] <= $height) {
            
        } else {
            $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
        }
        
        $headers = ['Content-Type' => 'image/png'];
        
        return new Response($image->getImageBlob(), 200, $headers);
    }
    
    
    
    /**
     * 查看图片
     *
     * @Route("/v/{id}/{size}", name="image_view")
     * @Method({"GET"})
     * @ApiDoc(
     *  resource=true,
     *  description="查看图片. example: http://127.0.0.1:8000/i/v/10307/320x100@2x ",
     *  section="Image"
     * )
     */
    public function viewAction ($id, $size)
    {
        $mediaManager = $this->get('sonata.media.manager.media');
        $media = $mediaManager->find($id);
        $retinaTime = 1;
        
        if(strpos($size, "@3x") > 0) {
            $retinaTime = 3;
            $size = str_replace("@3x", "", $size);
        }
        else if(strpos($size, "@2x") > 0) {
            $retinaTime = 2;
            $size = str_replace("@2x", "", $size);
        }
        
        if (strpos($size, "x") > 0) {
            list($width, $height) = explode("x", $size); 
        }
        else {
            $width = $size;
            $height = $size;
        }
        $width *= $retinaTime;
        $height *= $retinaTime;
        
        if($media == null) {
             throw $this->createNotFoundException('The Media does not exist');
        }
        
        //return $media->getBinaryContent();
        $provider = $this->get('sonata.media.pool')->getProvider($media->getProviderName());
        $in = $provider->getReferenceFile($media);
        
        $adapter = new LocalAdapter('/tmp');
        $filesystem = new Filesystem($adapter);
        
        $out = new File('tmp', $filesystem);
        $provider->getResizer()->resize($media, $in, $out, "png", array("width" => $width, "height" => $height, "quality" => 100 ));
        $headers = ['Content-Type' => 'image/png'];
        
        return new Response($out->getContent(), 200, $headers);
    }
}
