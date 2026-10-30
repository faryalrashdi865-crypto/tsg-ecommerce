<section class="narrow"><h1>Log in</h1>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="stack"><?= csrf_field() ?>
  <label>Email<input type="email" name="email" value="<?= e(post('email')) ?>" required autofocus></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn" type="submit">Log in</button>
</form>
<p>New customer? <a href="<?= e(url('account/register')) ?>">Create an account</a></p></section>
