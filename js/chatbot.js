/* BEIM Track IA · Asistente Beimy (spec 014).
   Las respuestas se muestran con textContent: nunca se interpreta HTML.
   @spec RF-BOT-01, RNF-BOT-02 */
(function () {
  'use strict';

  var raiz = document.querySelector('[data-chatbot]');
  if (!raiz) return;

  var panel = raiz.querySelector('.beimy-panel');
  var lista = raiz.querySelector('.beimy-mensajes');
  var opciones = raiz.querySelector('.beimy-opciones');
  var form = raiz.querySelector('[data-chatbot-form]');
  var entrada = form.querySelector('input[name="mensaje"]');
  var clave = 'beimy_' + (document.body.getAttribute('data-ranura') || '0');
  var ocupado = false;

  /* Conversación guardada por pestaña/cuenta para no perderla al navegar */
  var estado = { mensajes: [], opciones: [] };
  try { estado = JSON.parse(sessionStorage.getItem(clave)) || estado; } catch (e) { /* almacenamiento no disponible */ }
  var guardar = function () {
    estado.mensajes = estado.mensajes.slice(-30);
    try { sessionStorage.setItem(clave, JSON.stringify(estado)); } catch (e) { /* sin almacenamiento */ }
  };

  var bajar = function () { lista.scrollTop = lista.scrollHeight; };

  var pintarMensaje = function (m) {
    var div = document.createElement('div');
    div.className = 'beimy-msg ' + (m.yo ? 'yo' : 'bot') + (m.ia ? ' ia' : '');
    div.textContent = m.texto;
    lista.appendChild(div);
  };

  var pintarOpciones = function (ops) {
    opciones.textContent = '';
    (ops || []).forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'beimy-opcion';
      b.textContent = o.etiqueta;
      b.addEventListener('click', function () { enviar({ accion: 'opcion', id: o.id }, o.etiqueta); });
      opciones.appendChild(b);
    });
  };

  var agregar = function (m) { estado.mensajes.push(m); pintarMensaje(m); bajar(); guardar(); };

  var escribiendo = function (mostrar) {
    var viejo = lista.querySelector('.beimy-escribiendo-msg');
    if (viejo) viejo.remove();
    if (!mostrar) return;
    var div = document.createElement('div');
    div.className = 'beimy-msg bot beimy-escribiendo-msg';
    div.innerHTML = '<span class="beimy-escribiendo"><i></i><i></i><i></i></span>';
    lista.appendChild(div);
    bajar();
  };

  var boton = form.querySelector('button[type="submit"]');
  var ponerOcupado = function (si) {
    ocupado = si;
    boton.disabled = si;
    entrada.placeholder = si ? 'Beimy está escribiendo…' : 'Escribe tu pregunta…';
  };

  var enviar = function (datos, textoUsuario) {
    if (ocupado) return false;
    ponerOcupado(true);
    if (textoUsuario) agregar({ yo: true, texto: textoUsuario });
    pintarOpciones([]);
    escribiendo(true);

    var cuerpo = new URLSearchParams();
    cuerpo.append('csrf', raiz.getAttribute('data-csrf'));
    Object.keys(datos).forEach(function (k) { cuerpo.append(k, datos[k]); });

    var control = window.AbortController ? new AbortController() : null;
    var reloj = setTimeout(function () { if (control) control.abort(); }, 60000);

    fetch(raiz.getAttribute('data-api'), {
      method: 'POST',
      body: cuerpo,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
      signal: control ? control.signal : undefined
    })
      .then(function (r) { return r.json().catch(function () { return { texto: 'Tu sesión terminó. Recarga la página para continuar.' }; }); })
      .then(function (r) {
        escribiendo(false);
        agregar({ texto: r.texto || 'No entendí la respuesta del servidor.', ia: !!r.ia });
        estado.opciones = r.opciones || [];
        pintarOpciones(estado.opciones);
        guardar();
        requestAnimationFrame(bajar);
      })
      .catch(function () {
        escribiendo(false);
        agregar({ texto: 'La respuesta tardó demasiado o no hubo conexión. Inténtalo de nuevo o usa el menú.' });
        pintarOpciones(estado.opciones);
      })
      .then(function () {
        clearTimeout(reloj);
        ponerOcupado(false);
        entrada.focus();
      });
    return true;
  };

  var abrir = function () {
    raiz.classList.add('is-abierto');
    panel.hidden = false;
    if (!estado.mensajes.length) {
      enviar({ accion: 'menu' });
    } else if (!lista.childElementCount) {
      estado.mensajes.forEach(pintarMensaje);
      pintarOpciones(estado.opciones);
    }
    requestAnimationFrame(bajar);
    setTimeout(function () { entrada.focus(); }, 50);
  };
  var cerrar = function () {
    raiz.classList.remove('is-abierto');
    panel.hidden = true;
  };

  raiz.querySelector('[data-chatbot-abrir]').addEventListener('click', abrir);
  raiz.querySelector('[data-chatbot-cerrar]').addEventListener('click', cerrar);
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !panel.hidden) cerrar(); });

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var texto = entrada.value.trim();
    if (!texto || ocupado) return;   // si Beimy aún responde, el texto se conserva
    entrada.value = '';
    enviar({ accion: 'mensaje', mensaje: texto }, texto);
  });
})();
