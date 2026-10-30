<section class="narrow"><h1>Create account</h1>
<form method="post" class="stack"><?= csrf_field() ?>
  <?php $f = fn($k) => !empty($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : ''; ?>
  <label>Full name<input type="text" name="name" value="<?= e(post('name')) ?>" required><?= $f('name') ?></label>
  <label>Email<input type="email" name="email" value="<?= e(post('email')) ?>" required><?= $f('email') ?></label>
  <label>Phone (optional)<input type="tel" name="phone" value="<?= e(post('phone')) ?>"></label>
  <label>Password (8+ characters, letters and numbers)<input type="password" name="password" required><?= $f('password') ?></label>
  <label>Confirm password<input type="password" name="password2" required></label>
  <button class="btn" type="submit">Create account</button>
</form>
<p>Already registered? <a href="<?= e(url('account/login')) ?>">Log in</a></p></section>
