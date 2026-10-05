<?php
require __DIR__.'/../inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=(string)($_POST['action']??'');
    $id=(int)($_POST['id']??0);
    try {
        if ($action==='create') {
            $name=trim((string)($_POST['beneficiary_name']??''));
            $story=trim((string)($_POST['story']??''));
            $img=null;
            if (!empty($_FILES['image']['name'])) $img=store_public_image($_FILES['image']);
            $stmt=$db->prepare('INSERT INTO testimonials (beneficiary_name,story,image_original_name,image_stored_name,image_mime_type) VALUES (?,?,?,?,?)');
            $stmt->execute([$name?:null,$story?:null,$img['original_name']??null,$img['stored_name']??null,$img['mime_type']??null]);
            flash('success','Testimonial added.');
        } elseif ($action==='update' && $id>0) {
            $stmt=$db->prepare('SELECT * FROM testimonials WHERE id=?');$stmt->execute([$id]);$old=$stmt->fetch();
            if (!$old) throw new RuntimeException('Testimonial not found.');
            $name=trim((string)($_POST['beneficiary_name']??''));
            $story=trim((string)($_POST['story']??''));
            $orig=$old['image_original_name'];$stored=$old['image_stored_name'];$mime=$old['image_mime_type'];
            if (!empty($_POST['remove_image'])) { delete_media_file($stored); $orig=$stored=$mime=null; }
            if (!empty($_FILES['image']['name'])) { $img=store_public_image($_FILES['image']); delete_media_file($stored); $orig=$img['original_name'];$stored=$img['stored_name'];$mime=$img['mime_type']; }
            $db->prepare('UPDATE testimonials SET beneficiary_name=?,story=?,image_original_name=?,image_stored_name=?,image_mime_type=? WHERE id=?')->execute([$name?:null,$story?:null,$orig,$stored,$mime,$id]);
            flash('success','Testimonial updated.');
        } elseif ($action==='delete' && $id>0) {
            $stmt=$db->prepare('SELECT image_stored_name FROM testimonials WHERE id=?');$stmt->execute([$id]);$stored=$stmt->fetchColumn();
            delete_media_file($stored?:null);
            $db->prepare('DELETE FROM testimonials WHERE id=?')->execute([$id]);
            flash('success','Testimonial deleted.');
        }
    } catch (Throwable $e) { flash('error',$e->getMessage()); }
    redirect('gadmin8f3k2p9x7m4q6v1c/testimonials.php');
}
$items=$db->query('SELECT * FROM testimonials ORDER BY created_at DESC,id DESC')->fetchAll();
$adminTitle='Testimonials';require __DIR__.'/_header.php';
?>
<section class="panel"><h2>Add testimonial</h2><p class="muted">Image, beneficiary name, and story are all optional. A completely blank entry will remain hidden on the public website until content is added.</p>
<form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="create"><div class="form-grid">
<div class="field"><label>Beneficiary name</label><input name="beneficiary_name" maxlength="180" placeholder="Optional"></div>
<div class="field"><label>Beneficiary image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"><small>Optional. JPG, PNG, WEBP or GIF, up to 20 MB.</small></div>
<div class="field full"><label>Brief story</label><textarea name="story" placeholder="Optional"></textarea></div>
</div><p><button class="btn btn-primary">Add testimonial</button></p></form></section>
<section class="panel"><h2>Published testimonials</h2><?php if(!$items):?><div class="empty">No testimonials yet.</div><?php else:?>
<?php foreach($items as $t):?><form method="post" enctype="multipart/form-data" class="admin-media-item"><?=csrf_field()?><input type="hidden" name="id" value="<?=$t['id']?>"><div class="admin-media-preview"><?php if($t['image_stored_name']):?><img src="<?=h(app_url('media.php?type=testimonial&id='.(int)$t['id']))?>" alt=""><?php else:?><span>No image</span><?php endif;?></div><div class="admin-media-fields"><div class="form-grid"><div class="field"><label>Beneficiary name</label><input name="beneficiary_name" value="<?=h($t['beneficiary_name'])?>"></div><div class="field"><label>Replace image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"><?php if($t['image_stored_name']):?><label class="inline-check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label><?php endif;?></div><div class="field full"><label>Brief story</label><textarea name="story"><?=h($t['story'])?></textarea></div></div><div class="actions"><button class="btn btn-primary" name="action" value="update">Save changes</button><button class="btn btn-danger" name="action" value="delete" onclick="return confirm('Delete this testimonial?')">Delete</button></div><div class="meta">Added <?=h(date('M j, Y',strtotime($t['created_at'])))?></div></div></form><?php endforeach;?>
<?php endif;?></section>
<?php require __DIR__.'/_footer.php';?>
