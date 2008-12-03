<?php

/**
 * Thumbnail actions.
 *
 * @package    jsThumbnail
 * @subpackage jsThumbnailActions
 * @author     Julian Stricker <julian@julianstricker.com>
 * @version    
 */
class jsThumbnailActions extends sfActions
{
  /**
   * Output captcha image
   *
   */
  public function executeThumbnail()
  {
    $imgname = $this->getRequestParameter('img');
    $maxx=$this->getRequestParameter('maxx',128);
    $maxy=$this->getRequestParameter('maxy',128);
    $mode=$this->getRequestParameter('mode','normal');
    $info = getimagesize( $imgname );
    $ctime = filectime ( $imgname );
    $this->forward404Unless($info);
    $namespace=sfConfig::get('app_js_thumbnail_plugin_namespace', $this->getModuleName().'_thumbnails');
    $file_cache_dir = sfConfig::get('sf_cache_dir') . DIRECTORY_SEPARATOR . $namespace;
    
    if (method_exists('sfFileCache', 'setFileLocking')){ //symfony 1.0
      $cache = new sfFileCache($file_cache_dir); 
    }else{ //symfony 1.1
      $cache = new sfFileCache(array('cache_dir' => $file_cache_dir));
    }
    
    
    $cachename=md5($imgname.$maxx.$maxy.$mode.$ctime);
    if ($cache->has($cachename,$namespace)){
      //ist bereits im cache:
      $cached = $cache->get($cachename, $namespace);
      if (!empty($cached)) {
        header( "Content-Type: image/jpeg" );
        echo unserialize($cached);
      }
    }else{
      //thumbnail erstellen:
      
      if ( $info[2] == 1 ) { //Original ist ein GIF
				$oimage = imagecreatefromgif( $imgname );
			} else if ( $info[2] == 2 ) { //Original ist ein JPG
				$oimage = imagecreatefromjpeg( $imgname );
			} else if ( $info[2] == 3 ) { //Original ist ein PNG
				$oimage = imagecreatefrompng( $imgname );
			}else{
			 $this->forward404();
			}
			$ogrx = $info[0];
			$ogry = $info[1];
			if ($mode=='crop'){
			 if ( $ogrx / $maxx > $ogry / $maxy ) { //Breitformat
  				$ngry = $maxy;
  				$ngrx = ( $ogrx * $maxy ) / $ogry;
  			} else { //Hochformat
  				$ngrx = $maxx;
  				$ngry = ( $ogry * $maxx ) / $ogrx;
  			}			
			}else{
  			if ( $ogrx / $maxx > $ogry / $maxy ) { //Breitformat
  				$ngrx = $maxx;
  				$ngry = ( $ogry * $maxx ) / $ogrx;
  			} else { //Hochformat
  				$ngry = $maxy;
  				$ngrx = ( $ogrx * $maxy ) / $ogry;
  			}
			}
			if ( $info[2] == 2 || $info[2] == 3 ) { //PNG, JPG
			  if ($mode=='normal'){
				  $image = imagecreatetruecolor( $ngrx, $ngry );
				}else{
				  $image = imagecreatetruecolor( $maxx, $maxy );
				}
			} else { //GIF
				if ($mode=='normal'){
				  $image = imagecreate( $ngrx, $ngry );
				}else{
				  $image = imagecreate( $maxx, $maxy );
				}
			}
			if ( $info[2] == 3 ) { //PNG
				imagesavealpha( $image, true );
				$farbe_body = imagecolorallocate( $image, 255, 255, 255 );
				$trans = imagecolortransparent( $image, $farbe_body );
			} else if ( $info[2] == 1 ) { //GIF
				$farbe_body = imagecolorallocate( $image, 255, 255, 255 );
			}
			if ($mode=='normal'){
			  imagecopyresampled( $image, $oimage, 0, 0, 0, 0, $ngrx, $ngry, $ogrx, $ogry );
			}else if ($mode=='crop'){
			  imagecopyresampled( $image, $oimage, -($ngrx-$maxx)/2, -($ngry-$maxy)/2, 0, 0, $ngrx, $ngry, $ogrx, $ogry );
			}else if ($mode=='stretch'){
			  imagecopyresampled( $image, $oimage, 0, 0, 0, 0, $maxx, $maxy, $ogrx, $ogry );
			}
			
			
			ob_start(); // start a new output buffer
        imagejpeg( $image, NULL, 100 );
        $ImageData = ob_get_contents();
      ob_end_clean; // stop this output buffer
      $cache->set($cachename, $namespace, serialize($ImageData));
			header( "Content-Type: image/jpeg" );
      imagejpeg( $image, NULL, 100 );
      imagedestroy( $image );
			imagedestroy( $oimage );
    }
    return sfView::NONE;
  }
}
