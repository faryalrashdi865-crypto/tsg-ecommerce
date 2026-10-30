<section class="narrow"><h1>My profile</h1>
<form method="post" class="stack"><?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= e($c['name']) ?>" required></label>
  <label>Email<input value="<?= e($c['email']) ?>" disabled></label>
  <label>Phone<input name="phone" value="<?= e((string)$c['phone']) ?>"></label>
  <h3>Change password</h3><label>Current password<input type="password" name="current_password" autocomplete="current-password"></label>
  <label>New password<input type="password" name="new_password" autocomplete="new-password"></label>
  <button class="btn" type="submit">Save</button></form></section>
