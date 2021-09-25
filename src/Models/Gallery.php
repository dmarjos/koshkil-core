<?php
namespace Koshkil\Core\Models;

use Koshkil\Core\Application;
use Koshkil\DBAL\Model;

class TModelGallery extends Model {

	protected $table="tbl_gallery";
	protected $dates=array('gal_fecha');
	protected $fillable=array(
		'gal_grupo',
		'gal_sesion',
		'gal_fecha',
		'gal_mime',
		'gal_temp_id',
		'gal_relacionado',
		'gal_tipo',
		'gal_archivo',
		'gal_uso',
		'gal_titulo',
		'gal_indice',
		'gal_descripcion',
		'gal_stillframe',
		'gal_url',
		'gal_estado'
	);

	public $indexField="gal_codigo";


	protected function setupTableStructure() {
		$this->structure=array(
			"fields"=>array(
				"gal_codigo"=>array("type"=>"int","length"=>"11","extra"=>"NOT NULL AUTO_INCREMENT",),
				"gal_grupo"=>array("type"=>"varchar","length"=>"64","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_sesion"=>array("type"=>"varchar","length"=>"128","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_fecha"=>array("type"=>"datetime","extra"=>"NOT NULL DEFAULT '0000-00-00 00:00:00'",),
				"gal_mime"=>array("type"=>"varchar","length"=>"64","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_temp_id"=>array("type"=>"varchar","length"=>"128","extra"=>"COLLATE latin1_spanish_ci",),
				"gal_relacionado"=>array("type"=>"int","length"=>"11","extra"=>"NOT NULL DEFAULT 0",),
				"gal_tipo"=>array("type"=>"enum","length"=>"'image','sound','document','vimeo','soundcloud','youtube'","extra"=>"NOT NULL DEFAULT 'image'",),
				"gal_uso"=>array("type"=>"varchar","length"=>"64","extra"=>"NOT NULL DEFAULT 'general'",),
				"gal_archivo"=>array("type"=>"varchar","length"=>"128","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_titulo"=>array("type"=>"varchar","length"=>"255","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_indice"=>array("type"=>"int","length"=>"11","extra"=>"NOT NULL DEFAULT 0"),
				"gal_descripcion"=>array("type"=>"text","length"=>"","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_stillframe"=>array("type"=>"varchar","length"=>"128","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_url"=>array("type"=>"varchar","length"=>"255","extra"=>"COLLATE latin1_spanish_ci NOT NULL",),
				"gal_estado"=>array("type"=>"varchar","length"=>"1","extra"=>"COLLATE latin1_spanish_ci NOT NULL DEFAULT '1'",),
			),
			"keys"=>array(
				array("key_name"=>"","primary"=>true,"fields"=>"gal_codigo",),
			),
			"initial_records"=>array(
			),

		);
	}

	public static function saveResources($group,$related) {
		Application::$db->execute("update tbl_galeria set gal_relacionado='{$related}', gal_temp_id='' where gal_grupo='{$group}' and (cast(gal_relacionado as char)='0' and gal_temp_id='".session_id()."')");
	}
	public function indexUp() {
		$newIndex=$this->gal_indice-1;
		if ($newIndex==0) return;

		Application::$db->execute("update tbl_galeria set gal_indice=gal_indice+1 where gal_indice='{$newIndex}' and gal_grupo='{$this->gal_grupo}'");

		$this->gal_indice=$newIndex;
		$this->update();
	}

	public static function nextIndex($group,$related,$type="image") {
		return self::lastIndex($group,$related,$type)+1;
	}

	public static function lastIndex($group,$related,$type="image") {
        $sql="select max(gal_indice) as ultimo from tbl_galeria where ".
			($type?(is_array($type)?"gal_tipo in ('".implode("', '",$type)."')":" gal_tipo='{$type}'")." and ":'').
			"gal_grupo='{$group}' and (cast(gal_relacionado as char)='{$related}' or (cast(gal_relacionado as char)='0' and gal_sesion='".session_id()."'))";
		$rec=Application::$db->getRow($sql);
		$maximo=intval($rec["ultimo"]);
		return $maximo;
	}

	public function indexDown() {
		$rec=Application::$db->getRow("select max(gal_indice) as ultimo from tbl_galeria where gal_grupo='{$this->gal_grupo}'");
		$maximo=$rec["ultimo"];

		$newIndex=$this->gal_indice+1;
		if ($newIndex>$maximo) return;

		Application::$db->execute("update tbl_items set gal_indice=gal_indice-1 where gal_indice='{$newIndex}' and gal_grupo='{$this->gal_grupo}'");

		$this->gal_indice=$newIndex;
		$this->update();
	}

	public function delete() {
		$fullPath=$this->fullPath(false);
		if(file_exists(Application::get('DOCUMENT_ROOT').$fullPath))
			@unlink(Application::get('DOCUMENT_ROOT').$fullPath);
		else if(file_exists(Application::get('DOCUMENT_ROOT').Application::getPath("/resources/uploads/{$this->gal_grupo}/{$this->gal_archivo}")))
			@unlink(Application::get('DOCUMENT_ROOT').Application::getPath("/resources/uploads/{$this->gal_grupo}/{$this->gal_archivo}"));
		else if(file_exists(Application::get('DOCUMENT_ROOT').Application::getPath("/resources/users-uploads/{$this->gal_grupo}/{$parent}/{$this->gal_archivo}")))
			@unlink(Application::get('DOCUMENT_ROOT').Application::getPath("/resources/users-uploads/{$this->gal_grupo}/{$parent}/{$this->gal_archivo}"));
		$this->initBuilder()->builder()->where("gal_codigo",$this->gal_codigo)->delete();
  		$sql="update tbl_galeria set gal_indice=gal_indice-1 where gal_grupo='{$this->gal_grupo}' and cast(gal_relacionado as char)='{$this->gal_relacionado}' and gal_indice>'{$this->gal_indice}'";
  		Application::$db->execute($sql);
		return;
	}

	public static function safeName($name, $path, $cut=75) {

		$extension=substr($name,strrpos($name,".")+1);
		$name=basename($name,".".$extension);
		$name=preg_replace('~&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|l‌​ig|quot|rsquo|orn|th);~i', '$1', htmlentities($name) );//;
		$name = preg_replace('/\n|\r/',' ',trim(strtolower($name)));
	    $name = preg_replace('/\.+/',' ',$name);
	    $name = preg_replace('/ +/','_',$name);
	    $name = preg_replace('/([^a-z0-9\._-])/','', $name);
	    $name = substr($name, 0, $cut);

	    $name=trim($name);
	    if (file_exists($path."/".$name.".".$extension)) {
	    	$idx=1;
	    	while (file_exists($path."/".$name."_".$idx.".".$extension))
	    		$idx++;
	    	$name.="_".$idx;
	    }
	    return (trim($name) == '' ? 'unknown' : trim($name)).".".$extension;
	}

	public function getThumb($fontawesome=false) {
		if (substr($this->gal_mime,0,6)=="image/")
            $galArchivo=Application::getlink("/uploads/{$this->gal_grupo}/{$this->gal_archivo}");
        else {
            $galArchivo=(!$fontawesome?Application::getlink("/resources/img/icons")."/":'').fileUtils::getFileIcon($this->gal_archivo,$fontawesome);
        }
		return $galArchivo;
	}

	public function fullPath($physical=false) {
		return ($physical?Application::get('DOCUMENT_ROOT'):'').Application::getLink("/resources/uploads/{$this->gal_grupo}/{$this->gal_archivo}");
	}

	public function userFullPath($usr_codigo=null) {
		if (!$usr_codigo) $usr_codigo=Application::$page->usuario->getParentUser();
		return Application::getLink("/resources/users-uploads/{$this->gal_grupo}/{$usr_codigo}/{$this->gal_archivo}");
	}

}
