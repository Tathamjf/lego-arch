// Botões − / + dos campos de quantidade (data-step="-1" | "1").
// Se o grupo tiver data-autosubmit, envia o formulário ao clicar (usado no carrinho).
document.addEventListener('click', function (e) {
  var b = e.target.closest ? e.target.closest('[data-step]') : null;
  if (!b) { return; }
  var wrap = b.parentNode;
  var input = wrap.querySelector('input');
  if (!input) { return; }
  var step = parseInt(b.getAttribute('data-step'), 10);
  var min = input.min !== '' ? parseInt(input.min, 10) : 0;
  var max = input.max !== '' ? parseInt(input.max, 10) : Infinity;
  var v = (parseInt(input.value, 10) || 0) + step;
  v = Math.min(v, max);
  v = Math.max(v, min);
  var changed = String(v) !== input.value;
  input.value = v;
  if (changed && wrap.hasAttribute('data-autosubmit')) {
    var f = wrap.closest('form');
    if (f) { f.submit(); }
  }
});
