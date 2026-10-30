<?php $store = setting('store_name'); $cartN = cart_count(); $c = customer(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') && ($title ?? '') !== $store ? $title . ' | ' . $store : $store) ?></title>
<?php if (!empty($meta_description)): ?><meta name="description" content="<?= e($meta_description) ?>"><?php endif; ?>
<?php if (setting('favicon')): ?><link rel="icon" href="<?= e(url(setting('favicon'))) ?>"><?php endif; ?>
<style><?= theme_css_vars() ?></style>
<link rel="stylesheet" href="<?= e(theme_asset('style.css')) ?>">
</head>
<body>
<?php if (setting('test_mode') === '1'): ?><div class="testbar">Test mode: this store is not live yet, orders placed now are for testing.</div><?php endif; ?>
<header class="site-header">
  <div class="wrap header-row">
    <a class="brand" href="<?= e(url()) ?>">
      <?php if (setting('logo')): ?><img src="<?= e(url(setting('logo'))) ?>" alt="<?= e($store) ?>"><?php else: ?><span><?= e($store) ?></span><?php endif; ?>
    </a>
    <form class="search" action="<?= e(url('shop')) ?>" method="get" role="search">
      <input type="search" name="q" placeholder="Search products" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search products">
      <button type="submit">Search</button>
    </form>
    <nav class="main-nav">
      <a href="<?= e(url('shop')) ?>">Shop</a>
      <a href="<?= e(url('track')) ?>">Track order</a>
      <?php if ($c): ?><a href="<?= e(url('account')) ?>">Account</a><?php else: ?><a href="<?= e(url('account/login')) ?>">Log in</a><?php endif; ?>
      <a class="cart-link" href="<?= e(url('cart')) ?>">Cart<?php if ($cartN): ?> <span class="badge"><?= $cartN ?></span><?php endif; ?></a>
    </nav>
  </div>
</header>
<main class="wrap">
  <?php foreach (flash() ?? [] as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
  <?= $content ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div><strong><?= e($store) ?></strong><p><?= e(setting('tagline')) ?></p><p class="muted"><?= e(setting('footer_text')) ?></p></div>
    <div><strong>Information</strong>
      <?php foreach (rows("SELECT title,slug FROM pages WHERE status='published' AND show_in_footer=1 ORDER BY position,id") as $pg): ?>
        <a href="<?= e(url('page/' . $pg['slug'])) ?>"><?= e($pg['title']) ?></a>
      <?php endforeach; ?>
    </div>
    <div><strong>Contact</strong>
      <?php if (setting('phone')): ?><span><?= e(setting('phone')) ?></span><?php endif; ?>
      <?php if (setting('contact_email')): ?><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a><?php endif; ?>
      <?php if (setting('address')): ?><span><?= e(setting('address')) ?></span><?php endif; ?>
      <?php if (setting('facebook')): ?><a href="<?= e(setting('facebook')) ?>" rel="noopener">Facebook</a><?php endif; ?>
      <?php if (setting('instagram')): ?><a href="<?= e(setting('instagram')) ?>" rel="noopener">Instagram</a><?php endif; ?>
    </div>
  </div>
  <div class="wrap copyright">&copy; <?= date('Y') ?> <?= e($store) ?></div>
</footer>
<?php if (module_on('whatsapp_button') && setting('whatsapp_number')): ?>
  <a class="wa-float" href="<?= e(whatsapp_link(setting('whatsapp_number'), 'Hello ' . $store . ', I have a question.')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">WhatsApp</a>
<?php endif; ?>
<script src="<?= e(url('assets/js/store.js')) ?>" defer></script>
</body>
</html>
