<?php foreach ($sections as $s): ?>
  <?php if ($s === 'banner' && $banners): ?>
  <section class="hero" data-slider>
    <?php foreach ($banners as $i => $b): ?>
    <div class="slide<?= $i === 0 ? ' active' : '' ?>"<?= $b['image'] ? ' style="background-image:url(' . e(url($b['image'])) . ')"' : '' ?>>
      <div class="slide-text">
        <?php if ($b['title']): ?><h1><?= e($b['title']) ?></h1><?php endif; ?>
        <?php if ($b['subtitle']): ?><p><?= e($b['subtitle']) ?></p><?php endif; ?>
        <?php if ($b['link']): ?><a class="btn btn-accent" href="<?= e(preg_match('#^https?://#', $b['link']) ? $b['link'] : url($b['link'])) ?>"><?= e($b['button_text'] ?: 'Shop now') ?></a><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </section>
  <?php elseif ($s === 'banner' && !$banners): ?>
  <section class="hero hero-plain"><div class="slide active"><div class="slide-text"><h1>Welcome to <?= e(setting('store_name')) ?></h1><p><?= e(setting('tagline')) ?></p><a class="btn btn-accent" href="<?= e(url('shop')) ?>">Shop now</a></div></div></section>
  <?php elseif ($s === 'categories' && $categories): ?>
  <section><h2>Shop by category</h2><div class="tiles">
    <?php foreach ($categories as $c): ?><a class="tile" href="<?= e(url('category/' . $c['slug'])) ?>"><strong><?= e($c['name']) ?></strong><small><?= (int)$c['n'] ?> products</small></a><?php endforeach; ?>
  </div></section>
  <?php elseif ($s === 'featured' && $featured): ?>
  <section><h2>Featured products</h2><div class="grid"><?php foreach ($featured as $p) echo render('shop/_card', ['p' => $p]); ?></div></section>
  <?php elseif ($s === 'new' && $new): ?>
  <section><h2>New arrivals</h2><div class="grid"><?php foreach ($new as $p) echo render('shop/_card', ['p' => $p]); ?></div><p class="center"><a class="btn btn-outline" href="<?= e(url('shop')) ?>">View all products</a></p></section>
  <?php endif; ?>
<?php endforeach; ?>
