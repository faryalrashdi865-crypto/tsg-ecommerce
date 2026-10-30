/* Admin behaviour: confirm dialogs, variant rows, product type toggle. */
(function () {
  'use strict';
  document.addEventListener('submit', function (e) {
    var f = e.target, msg = f.getAttribute && f.getAttribute('data-confirm');
    var sub = e.submitter && e.submitter.getAttribute('data-confirm');
    if ((msg || sub) && !window.confirm(msg || sub)) e.preventDefault();
  });
  var type = document.getElementById('ptype'), panel = document.getElementById('variants-panel');
  function toggle() {
    var v = type && type.value === 'variable';
    if (panel) panel.style.display = v ? '' : 'none';
    document.querySelectorAll('[data-simple-only]').forEach(function (el) { el.disabled = v; if (el.parentNode) el.parentNode.style.opacity = v ? .4 : 1; });
  }
  if (type) { type.addEventListener('change', toggle); toggle(); }
  var add = document.getElementById('add-variant'), table = document.getElementById('variants');
  if (add && table) {
    add.addEventListener('click', function () {
      var body = table.tBodies[0], last = body.rows[body.rows.length - 1], c = last.cloneNode(true);
      c.querySelectorAll('input').forEach(function (i) { i.value = i.name === 'v_id[]' ? '0' : (i.name === 'v_stock[]' ? '0' : ''); });
      body.appendChild(c);
    });
    table.addEventListener('click', function (e) {
      if (!e.target.hasAttribute('data-remove-row')) return;
      var body = table.tBodies[0];
      if (body.rows.length > 1) e.target.closest('tr').remove();
    });
  }
})();
