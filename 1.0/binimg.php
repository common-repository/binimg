<?
/*
Plugin Name: BINIMG
Version: 1.0
Plugin URI: http://www.verasoul.com 
Description: This plugin will detect all images in your WordPress that are not being used no more. It will mark all the images. What you only have to do is clic on "Delete". 
This plugin is beeing re-developed for spanish people. Compatible up to: Wordpress 3.0.2
*/
/*
Author of this idea and first plugin called DUI: Bob Carret
Author of this idea URI: http://www.bobhobby.com/
Based on: http://www.bobhobby.com/2008/02/24/delete-unused-image-files-plugin-for-wordpress/
*/
/*

Copyright (c) 2010 www.verasoul.com
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

Disclaimer: 
	Use at your own risk. No warranty expressed or implied is provided.
	This program is free software; you can redistribute it and/or modify 
	it under the terms of the GNU General Public License as published by 
	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 	See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Requeriments: WordPress 2.9.X or newer and PHP 5.1.x
Compatible: WordPress 3.0.2

Usage:
1. Download plugin and unzip
2. Upload the plugin folder (unzipped) to your WordPress plugins directory inside of wp-content.(i.e. www.domain.com/wp-content/plugin/BINIMG/binimg.php)
3. Activate it from the plugins menu inside of WordPress. 
4. Go to your Admin Control Panel -> BINIMG, all images are selecte by default. If want to delete them, just press delete button, locate at the end of the list.
*/

### Load WP-Config File If This File Is Called Directly
if (!function_exists('add_action')) {
	require_once('../../../wp-config.php');
}

//  First we gotta check all file images in Data Base
function NotUsedImage($FileName) {
    global $wpdb,$table_prefix;	
	$result = intval($wpdb->get_var("SELECT COUNT(*) FROM ".$table_prefix."posts WHERE post_content LIKE '%/$FileName%'"));
	return $result>0;
}

// Second, we get the list of images
function GetFileName($ImageDir)
{
	global $ImageCounter;
    $FileCnt = 0;	
	$UnUsedImages = array();
	// Check if its a directory		
	if (is_dir($ImageDir)) 
	{  
	  
         if ($DirHndl = opendir($ImageDir)) 
		 {		 	
            $files = array();
            while (($file = readdir($DirHndl))) 
			{
			    $path =	pathinfo($file);
                if (eregi('(jpg)|(gif)|(png)|(jpeg)', $path['extension']) || eregi('(thumbnail)',$path['basename'])) $files[] = $file;
            }
            closedir($DirHndl);
			//print_r($files);
			
			if (count($files)) 
			{											
				foreach ($files as $fn)
				{	 
					
					if (!NotUsedImage($fn)) 
					{												 
						$home = get_option('home');
						$upload_path = get_option('upload_path');
						$UnUsedImages[$FileCnt] = $fn.'::'.$home.'/'.$upload_path.'/'.$ImageDir.'/'.$fn;
						$FileCnt++;			
						$ImageCounter++;									 
					}									
				}			
			}
        } 
    }
  return $UnUsedImages;				
}

// select directory
function select_year_directory($year) 
{
    $Yeardirectory = array();
    $month = array('01','02','03','04','05','06','07','08','09','10','11','12');
    for ($i=0;$i<12;$i++)
	{
	   
	   $dir =getcwd();
       
       if (is_dir($dir.'/'.$year.'/'.$month[$i])) 
	   {		        
			 $Yeardirectory[$month[$i]] = GetFileName($year.'/'.$month[$i]);
	   }	   
	}
	return $Yeardirectory;
}

// check image in directory
function CheckInDir($Dir) {

	if ($dh = opendir($Dir)) 
	{
		while ($el = readdir($dh)) {
			$path = $el;
			
			if (is_dir($path) && $el != '.' && $el != '..') {
				$year_result[$path] = select_year_directory($path);								
				//echo $path.'<br/>'; //we´re able to display folders into uploads folder
			} 
		} 
		closedir($dh);
	}
	return $year_result;
}


//-----------------------------------------------------------------------------------
// add management in option menu 
function add_BINIMG_menu() {
	//if (function_exists('add_menu_page')) {  //is this check needed?   
		add_menu_page('BINIMG option', 'Bin Images', 10, basename(__FILE__), 'BINIMG_options', WP_PLUGIN_URL . '/binimg/recycle_bin.png');
	//}
}

// main option -----------
function BINIMG_options()
{
?>

<div class="wrap">
<table border="0">
<tr>
<td><div align="left">
<img src="<?php echo WP_PLUGIN_URL; ?>/binimg/recycle_bin48.png" alt="La Papelera de las imágenes" />
</div></td>
<td><h2>Ficheros de imagen en desuso</h2></td>
</tr>
</table>

<?
	if (array_key_exists('_submit_check', $_POST)) {
   		if (isset($_POST['im'])){
   			$im = $_POST['im'];
			
     		foreach($im as $DeImage) {
		    	$fdt = explode(":",$DeImage);	 			
				chdir("../");
				$upload_dir = get_option('upload_path');
				chdir($upload_dir.'/'.$fdt[0]);				
				if (@unlink($fdt[1])) {
					echo $fdt[0].'---> '.$fdt[1].' <font color="#FF0000">Este fichero ha sido eliminado del servidor.</font><br>';
				}else{
					echo $fdt[0].'---> '.$fdt[1].' <font color="#FF0000">No se ha podido eliminar. Por favor, cambia los permisos del directorio <font color="#000000">'.$fdt[0].'</font> a <font color="#000000">777</font></font><br>';
				}	
				chdir("../../../");
			}
			
			echo "<hr>";
		}
	}


	global $ImageCounter;
	chdir("../");
	$upload_dir = get_option('upload_path');
	chdir($upload_dir);
	$newdir = getcwd();
	$ImageCounter=0;
	$result = CheckInDir($newdir);

	?>
	
	<? echo "<b>Tienes un total de <font color='#FF0000'>".$ImageCounter."</font> ficheros de imagen sin usar.</b><br><br>"; ?>
	
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<?
	if (is_array($result)) 
	{
    	foreach ($result as $year => $monthArr) 
		{
	    	foreach ($monthArr as $month => $UnUsedImagesArr) 
			{	    	
		    	if (count($UnUsedImagesArr)<>0) 
				{
				
				echo '<table border="0">';
			    echo '<tr>';
				echo '<td><div align="left">';
                echo '<img src="/wp-content/plugins/binimg/folder.gif"/>';
                echo '</div>';
				echo '</td>';
                echo '<td>';
				echo '<b><font color="#007700">'.$month.'/'.$year.'</font></b><br>';
					foreach ($UnUsedImagesArr as $UnUsedImages) 
					{			
				    	$DIRURL = explode("::",$UnUsedImages);						
						echo '<input type="checkbox" name="im[]" value="'.$year.'/'.$month.':'.$DIRURL[0].'" checked="checked" />'.$DIRURL[0].	'&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$DIRURL[1].'" target="_blank">Ver Imagen</a><br>';						
					}
				echo '</td>';
                echo '</tr>';
                echo '</table>';
				echo '<hr>';
				} // end if		
			}		
		}	
	}	
?>

<input type="hidden" name="_submit_check" value="1"/> 
<input type="submit" name="submit" class="button" value="Eliminar Seleccionados" />

</form>
<br />
<br />
<br />
<em><a href="http://www.verasoul.com" target="_blank">Vera's Soul</a></em><br />
<em>Basado en el plugin de <a href="http://www.bobhobby.com/" target="_blank">BobHobby</a></em>
</div>
<?
}
add_action('admin_menu', 'add_BINIMG_menu');
?>