// assets/script.js

document.addEventListener("DOMContentLoaded", () => {
  // Añadir animación suave a los elementos .card
  document.querySelectorAll('.card, .container').forEach(el=>{
    el.style.opacity = 0;
    el.style.transform = 'translateY(6px)';
    setTimeout(()=> {
      el.style.transition = 'all 360ms ease';
      el.style.opacity = 1;
      el.style.transform = 'translateY(0)';
    }, 80);
  });
});

// función para mostrar mensajes flash (desde PHP imprimir pequeño script que llame a showMessage)
function showMessage(text, type='success') {
  const box = document.createElement('div');
  box.className = 'flash-message';
  box.textContent = text;
  box.style.position = 'fixed';
  box.style.right = '18px';
  box.style.bottom = '18px';
  box.style.padding = '12px 16px';
  box.style.borderRadius = '10px';
  box.style.zIndex = 9999;
  box.style.fontWeight = 700;
  box.style.color = '#fff';
  box.style.boxShadow = '0 6px 18px rgba(12,18,30,0.12)';
  if(type === 'success') box.style.background = '#28a745';
  else if(type === 'error') box.style.background = '#b80000';
  else box.style.background = '#004b8d';
  document.body.appendChild(box);
  setTimeout(()=> box.remove(), 3500);
}

// Confirmación genérica para enlaces con data-confirm
document.addEventListener('click', function(e){
  const t = e.target.closest('[data-confirm]');
  if(!t) return;
  const msg = t.getAttribute('data-confirm') || '¿Estás seguro?';
  if(!confirm(msg)) e.preventDefault();
});
