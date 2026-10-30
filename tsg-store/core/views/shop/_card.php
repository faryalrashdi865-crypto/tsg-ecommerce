<?php $price = (float)$p['final_price']; $onSale = $price < (float)$p['price']; ?>
<article class="card">
  <a class="card-img" href="<?= e(url('product/' . $p['slug'])) ?>"><img loading="lazy" src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>">
    <?php if ($onSale): ?><span class="tag tag-sale">Sale</span><?php endif; ?>
    <?php if (!(int)$p['in_stock']): ?><span class="tag tag-out">Out of stock</span><?php endif; ?>
  </a>
  <div class="card-body">
    <a class="card-title" href="<?= e(url('product/' . $p['slug'])) ?>"><?= e($p['name']) ?></a>
    <div class="price"><?php if ($onSale): ?><s><?= e(money($p['price'])) ?></s> <?php endif; ?><strong><?= e(money($price)) ?></strong><?php if (module_on('units') && $p['unit']): ?> <small>/ <?= e($p['unit']) ?></small><?php endif; ?></div>
  </div>
</article>
