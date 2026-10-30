/* Storefront behaviour: hero slider, gallery, payment panels and live checkout totals. No dependencies. */
(function () {
  'use strict';
  // Hero slider
  document.querySelectorAll('[data-slider]').forEach(function (box) {
    var slides = box.querySelectorAll('.slide'), i = 0;
    if (slides.length < 2) return;
    setInterval(function () { slides[i].classList.remove('active'); i = (i + 1) % slides.length; slides[i].classList.add('active'); }, 5000);
  });
  // Product gallery: thumbnails and click-to-zoom
  var main = document.getElementById('mainImg');
  if (main) {
    document.querySelectorAll('[data-thumb]').forEach(function (t) { t.addEventListener('click', function () { main.src = t.src; }); });
    main.addEventListener('click', function () { main.parentNode.classList.toggle('zoom'); });
  }
  // Checkout
  var form = document.getElementById('checkout-form');
  if (!form) return;
  var quoteUrl = form.getAttribute('data-quote');
  var btnUpdate = document.getElementById('update-rates');
  if (btnUpdate) btnUpdate.style.display = 'none';
  var ratesBox = document.getElementById('rates');
  function val(n) { var el = form.elements[n]; return el ? el.value : ''; }
  function checked(n) { var el = form.querySelector('input[name="' + n + '"]:checked'); return el ? el.value : ''; }
  function setText(id, t) { var el = document.getElementById(id); if (el) el.textContent = t; }
  function showPm() {
    form.querySelectorAll('.pm').forEach(function (p) { var r = p.querySelector('input[name="pm"]'); p.classList.toggle('sel', r && r.checked); });
  }
  var timer = null;
  function refresh(keepRate) {
    var qs = new URLSearchParams({ city: val('city'), province: val('province'), rate: keepRate ? checked('rate') : '', pm: checked('pm') });
    fetch(quoteUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (ratesBox) {
          ratesBox.innerHTML = '';
          if (!d.rates.length) { var p = document.createElement('p'); p.className = 'muted'; p.textContent = 'No shipping option is available for this address.'; ratesBox.appendChild(p); }
          d.rates.forEach(function (r) {
            var l = document.createElement('label'); l.className = 'opt';
            var i = document.createElement('input'); i.type = 'radio'; i.name = 'rate'; i.value = r.id; i.checked = String(r.id) === String(d.selected);
            l.appendChild(i);
            l.appendChild(document.createTextNode(' ' + r.name + (r.est_days ? ' (' + r.est_days + ')' : '') + ' '));
            var s = document.createElement('strong'); s.textContent = r.cost_fmt; l.appendChild(s);
            ratesBox.appendChild(l);
          });
        }
        var t = d.totals;
        setText('t-subtotal', t.subtotal); setText('t-shipping', t.shipping); setText('t-tax', t.tax); setText('t-total', t.total); setText('t-fee', t.fee);
        var hide = parseFloat(String(t.fee).replace(/[^0-9.]/g, '')) <= 0;
        ['t-fee-label', 't-fee'].forEach(function (id) { var el = document.getElementById(id); if (el) el.hidden = hide; });
      }).catch(function () { /* the server still recalculates everything when the order is placed */ });
  }
  function later(keep) { clearTimeout(timer); timer = setTimeout(function () { refresh(keep); }, 300); }
  ['city', 'province'].forEach(function (n) { var el = form.elements[n]; if (el) { el.addEventListener('input', function () { later(false); }); el.addEventListener('change', function () { later(false); }); } });
  form.addEventListener('change', function (e) {
    if (e.target.name === 'pm') { showPm(); refresh(true); }
    if (e.target.name === 'rate') refresh(true);
  });
  showPm();
  if (val('city') || val('province')) refresh(true);
})();
