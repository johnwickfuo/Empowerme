<?php
require __DIR__.'/../inc/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
    try {
        if ($action==='create') {
            $name=trim((string)($_POST['name']??''));
            if ($name==='') throw new RuntimeException('Enter the sponsor name.');
            if (empty($_FILES['logo']['name'])) throw new RuntimeException('Choose the sponsor logo.');
            $img=store_public_image($_FILES['logo']);
            $db->prepare('INSERT INTO sponsors (name,logo_original_name,logo_stored_name,logo_mime_type) VALUES (?,?,?,?)')->execute([$name,$img['original_name'],$img['stored_name'],$img['mime_type']]);
            flash('success','Sponsor added.');
        } elseif ($action==='update' && $id>0) {
            $name=trim((string)($_POST['name']??''));if($name==='')throw new RuntimeException('Enter the sponsor name.');
            $stmt=$db->prepare('SELECT * FROM sponsors WHERE id=?');$stmt->execute([$id]);$old=$stmt->fetch();if(!$old)throw new RuntimeException('Sponsor not found.');
            $orig=$old['logo_original_name'];$stored=$old['logo_stored_name'];$mime=$old['logo_mime_type'];
            if(!empty($_FILES['logo']['name'])){$img=store_public_image($_FILES['logo']);delete_media_file($stored);$orig=$img['original_name'];$stored=$img['stored_name'];$mime=$img['mime_type'];}
            $db->prepare('UPDATE sponsors SET name=?,logo_original_name=?,logo_stored_name=?,logo_mime_type=? WHERE id=?')->execute([$name,$orig,$stored,$mime,$id]);
            flash('success','Sponsor updated.');
        } elseif ($action==='delete' && $id>0) {
            $stmt=$db->prepare('SELECT logo_stored_name FROM sponsors WHERE id=?');$stmt->execute([$id]);$stored=$stmt->fetchColumn();delete_media_file($stored?:null);$db->prepare('DELETE FROM sponsors WHERE id=?')->execute([$id]);flash('success','Sponsor deleted.');
        }
    } catch(Throwable $e){flash('error',$e->getMessage());}
    redirect('gadmin8f3k2p9x7m4q6v1c/sponsors.php');
}
$items=$db->query('SELECT * FROM sponsors ORDER BY created_at DESC,id DESC')->fetchAll();$adminTitle='Sponsors';require __DIR__.'/_header.php';
?>
<section class="panel"><h2>Add sponsor</h2><form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="create"><div class="form-grid"><div class="field"><label>Sponsor name</label><input name="name" maxlength="180" required></div><div class="field"><label>Sponsor logo</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" required><small>JPG, PNG, WEBP or GIF, up to 20 MB.</small></div></div><p><button class="btn btn-primary">Add sponsor</button></p></form></section>
<section class="panel"><h2>Current sponsors</h2><?php if(!$items):?><div class="empty">No sponsors yet.</div><?php else:?>
<div class="admin-sponsor-list"><?php foreach($items as $s):?><form method="post" enctype="multipart/form-data" class="admin-media-item"><?=csrf_field()?><input type="hidden" name="id" value="<?=$s['id']?>"><div class="admin-media-preview sponsor-preview"><img src="<?=h(app_url('media.php?type=sponsor&id='.(int)$s['id']))?>" alt=""></div><div class="admin-media-fields"><div class="form-grid"><div class="field"><label>Sponsor name</label><input name="name" value="<?=h($s['name'])?>" required></div><div class="field"><label>Replace logo</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif"></div></div><div class="actions"><button class="btn btn-primary" name="action" value="update">Save changes</button><button class="btn btn-danger" name="action" value="delete" onclick="return confirm('Delete this sponsor?')">Delete</button></div></div></form><?php endforeach;?></div>
<?php endif;?></section>
<?php require __DIR__.'/_footer.php';?>
