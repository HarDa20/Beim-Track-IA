/* BEIM Track · Interacciones de la interfaz (JavaScript sin librerías).
   La validación definitiva siempre ocurre en el servidor. */
(function () {
  'use strict';

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* Menú lateral en celulares ------------------------------------------ */
  function toggleSidebar(abrir) {
    var sb = $('#sidebar'), bd = $('.sidebar-backdrop');
    if (!sb) return;
    sb.classList.toggle('open', abrir);
    if (bd) bd.classList.toggle('open', abrir);
  }
  $$('[data-toggle-sidebar]').forEach(function (b) { b.addEventListener('click', function () { toggleSidebar(true); }); });
  $$('[data-close-sidebar]').forEach(function (b) { b.addEventListener('click', function () { toggleSidebar(false); }); });

  /* Toasts --------------------------------------------------------------- */
  $$('.toast').forEach(function (t, i) {
    var cerrar = function () { t.classList.add('saliendo'); setTimeout(function () { t.remove(); }, 300); };
    var btn = $('.toast-close', t);
    if (btn) btn.addEventListener('click', cerrar);
    if (!t.classList.contains('toast-error')) setTimeout(cerrar, 5000 + i * 600);
  });

  /* Modal de confirmación ------------------------------------------------- */
  var modal = $('#modal-confirmar');
  var pendiente = null;
  function abrirModal(texto, alAceptar) {
    if (!modal) { if (window.confirm(texto)) alAceptar(); return; }
    $('#modal-texto').textContent = texto;
    pendiente = alAceptar;
    modal.hidden = false;
    $('#modal-aceptar').focus();
  }
  function cerrarModal() { if (modal) modal.hidden = true; pendiente = null; }
  if (modal) {
    $$('[data-modal-cerrar]', modal).forEach(function (b) { b.addEventListener('click', cerrarModal); });
    $('#modal-aceptar').addEventListener('click', function () { var f = pendiente; cerrarModal(); if (f) f(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) cerrarModal(); });
  }
  // Formularios con data-confirm: se pide confirmación antes de enviarlos.
  $$('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirmado === '1') return;
      if (!form.checkValidity()) return;
      e.preventDefault();
      abrirModal(form.dataset.confirm, function () {
        form.dataset.confirmado = '1';
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
      });
    });
  });
  // Botones con data-confirm-button (por ejemplo, eliminar borrador).
  $$('button[data-confirm-button]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (btn.dataset.confirmado === '1') return;
      e.preventDefault();
      abrirModal(btn.dataset.confirmButton, function () { btn.dataset.confirmado = '1'; btn.click(); });
    });
  });

  /* Evita envíos dobles ---------------------------------------------------- */
  $$('form').forEach(function (form) {
    // El chat de Beimy envía muchas veces sin recargar: no se bloquea.
    if (form.hasAttribute('data-chatbot-form')) return;
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      setTimeout(function () { $$('button[type=submit], button:not([type])', form).forEach(function (b) { b.disabled = true; }); }, 0);
    });
  });

  // Si el usuario vuelve con el botón Atrás, los botones se reactivan.
  window.addEventListener('pageshow', function () {
    $$('form button[disabled]').forEach(function (b) { b.disabled = false; });
  });

  /* Filas de tabla clicables ------------------------------------------------ */
  $$('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (e) {
      if (e.target.closest('a, button, form, select, input')) return;
      window.location.href = tr.dataset.href;
    });
  });

  /* Pestañas de inicio de sesión / registro -------------------------------- */
  $$('[data-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      $$('[data-tab]').forEach(function (t) { t.classList.toggle('active', t === tab); });
      $$('.tab-panel').forEach(function (p) { p.hidden = p.id !== 'panel-' + tab.dataset.tab; });
    });
  });

  /* Mostrar u ocultar contraseña -------------------------------------------- */
  $$('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentElement.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  /* Filtros que se envían al cambiar una lista -------------------------------- */
  $$('form[data-autosubmit] select, form[data-autosubmit] input[type=checkbox]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* Lista de archivos seleccionados con validación previa -------------------- */
  var EXT = ['pdf', 'ai', 'jpg', 'jpeg', 'png', 'webp'];
  var MAX = 10 * 1024 * 1024;
  $$('input[data-file-list]').forEach(function (input) {
    var zona = input.closest('.dropzone');
    var lista = zona && zona.parentElement.querySelector('.file-preview');
    var pintar = function () {
      if (!lista) return;
      lista.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (f) {
        var ext = f.name.split('.').pop().toLowerCase();
        var ok = EXT.indexOf(ext) !== -1 && f.size <= MAX;
        var li = document.createElement('li');
        if (!ok) li.className = 'bad';
        var nombre = document.createElement('span');
        nombre.textContent = f.name;
        var info = document.createElement('small');
        info.textContent = ok ? (f.size / 1048576).toFixed(2) + ' MB' : (f.size > MAX ? 'Supera 10 MB' : 'Formato no permitido');
        li.appendChild(nombre); li.appendChild(info);
        lista.appendChild(li);
      });
    };
    input.addEventListener('change', pintar);
    if (zona) {
      ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.add('drag'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.remove('drag'); }); });
    }
  });

  /* Formulario de pedido: resumen y costo estimado en vivo ------------------- */
  var form = $('[data-order-form]');
  if (form) {
    var moneda = function (v) { return '$ ' + Math.round(v).toLocaleString('es-CO'); };
    var cantidad = $('[data-cantidad]', form);
    var fecha = $('[data-fecha]', form);
    var costo = $('[data-r-costo]', form);
    var meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var ultimo = null;
    var actualizar = function () {
      var cat = $('input[name=categoria_id]:checked', form);
      var precio = cat ? parseFloat(cat.dataset.precio || '0') : 0;
      var unidad = cat ? cat.dataset.unidad : 'm2';
      var cant = parseFloat((cantidad.value || '0').replace(',', '.')) || 0;
      $$('[data-unidad-label]', form).forEach(function (el) { el.textContent = unidad; });
      $('[data-r-categoria]', form).textContent = cat ? cat.dataset.nombre : '—';
      $('[data-r-cantidad]', form).textContent = cant ? cant.toLocaleString('es-CO') + ' ' + unidad : '—';
      $('[data-r-precio]', form).textContent = cat ? moneda(precio) + ' / ' + unidad : '—';
      if (fecha.value) {
        var p = fecha.value.split('-');
        $('[data-r-fecha]', form).textContent = parseInt(p[2], 10) + ' ' + meses[parseInt(p[1], 10) - 1] + ' ' + p[0];
      } else {
        $('[data-r-fecha]', form).textContent = '—';
      }
      var total = moneda(precio * cant);
      if (total !== ultimo) {
        costo.textContent = total;
        costo.classList.add('bump');
        setTimeout(function () { costo.classList.remove('bump'); }, 180);
        ultimo = total;
      }
    };
    form.addEventListener('input', actualizar);
    form.addEventListener('change', actualizar);
    actualizar();

    // Recepción: cliente registrado o cliente nuevo
    var radios = $$('[data-toggle-cliente]', form);
    var mostrarCliente = function () {
      var sel = $('[data-toggle-cliente]:checked', form);
      var tipo = sel ? sel.value : 'existente';
      $$('[data-cliente]', form).forEach(function (bloque) {
        var visible = bloque.dataset.cliente === tipo;
        bloque.hidden = !visible;
        $$('input, select', bloque).forEach(function (el) {
          if (el.type === 'checkbox') { el.required = visible; return; }
          if (el.name === 'cliente_telefono') return;
          el.required = visible;
        });
      });
    };
    radios.forEach(function (r) { r.addEventListener('change', mostrarCliente); });
    if (radios.length) mostrarCliente();
  }

  /* Usuarios: el área solo aplica a empleados ----------------------------- */
  var rolSel = $('[data-rol-select]');
  if (rolSel) {
    var areaField = $('[data-area-field]');
    var sync = function () { areaField.hidden = rolSel.value !== 'empleado'; };
    rolSel.addEventListener('change', sync);
    sync();
  }
  /* Tema claro/oscuro y color de acento (spec 011) · @spec RF-TEM-01, RF-TEM-02 */
  var guardarCookie = function (nombre, valor) {
    document.cookie = nombre + '=' + valor + '; path=/; max-age=31536000; samesite=lax';
  };
  $$('[data-tema-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var nuevo = document.documentElement.getAttribute('data-tema') === 'claro' ? 'oscuro' : 'claro';
      document.documentElement.setAttribute('data-tema', nuevo);
      guardarCookie('beim_tema', nuevo);
    });
  });
  $$('.theme-dot[data-acento]').forEach(function (d) {
    d.addEventListener('click', function () {
      var acento = d.getAttribute('data-acento');
      document.documentElement.setAttribute('data-acento', acento);
      guardarCookie('beim_acento', acento);
      $$('.theme-dot').forEach(function (x) { x.classList.toggle('is-active', x.getAttribute('data-acento') === acento); });
    });
  });

  /* Lámpara del inicio de sesión (spec 012) · @spec RF-IDV-01 */
  var escena = $('[data-escena]'), cordon = $('[data-cordon]');
  if (escena && cordon) {
    var inicioY = null, tiron = 0, ignorarClic = false;
    var alternar = function () {
      var encender = escena.getAttribute('data-lampara') !== 'encendida';
      escena.setAttribute('data-lampara', encender ? 'encendida' : 'apagada');
      cordon.setAttribute('aria-pressed', encender ? 'true' : 'false');
      cordon.style.setProperty('--tiron', '26px');
      setTimeout(function () { cordon.style.setProperty('--tiron', '0px'); }, 160);
      if (encender) {
        var campo = $('#panel-login:not([hidden]) input[name="correo"]');
        if (campo) setTimeout(function () { campo.focus({ preventScroll: true }); }, 650);
      }
    };
    cordon.addEventListener('pointerdown', function (ev) {
      inicioY = ev.clientY; tiron = 0;
      cordon.setPointerCapture(ev.pointerId);
      cordon.classList.add('is-tirando');
    });
    cordon.addEventListener('pointermove', function (ev) {
      if (inicioY === null) return;
      tiron = Math.max(0, Math.min(70, ev.clientY - inicioY));
      cordon.style.setProperty('--tiron', tiron + 'px');
    });
    var soltar = function () {
      if (inicioY === null) return;
      inicioY = null;
      cordon.classList.remove('is-tirando');
      cordon.style.setProperty('--tiron', '0px');
      if (tiron > 22) { ignorarClic = true; alternar(); }
    };
    cordon.addEventListener('pointerup', soltar);
    cordon.addEventListener('pointercancel', soltar);
    cordon.addEventListener('click', function () {
      if (ignorarClic) { ignorarClic = false; return; }
      alternar();
    });
  }

  /* Foto de perfil: se sube al elegirla (spec 013) ------------------------ */
  $$('input[type=file][data-autosubmit]').forEach(function (inp) {
    inp.addEventListener('change', function () { if (inp.files.length) inp.form.submit(); });
  });

  /* Imprimir (panel gerencial, spec 015) ---------------------------------- */
  $$('[data-imprimir]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });

  /* Visualización 3D del pedido (spec 016) · @spec RF-V3D-02, RF-V3D-03 */
  $$('[data-viz]').forEach(function (viz) {
    var escena = $('[data-viz-escena]', viz);
    var mundo = $('[data-viz-mundo]', viz);
    var rx = -12, ry = -28, zoom = 1, giro = null, arrastre = null, urlLocal = null;
    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var aplicar = function () {
      viz.style.setProperty('--rx', rx + 'deg');
      viz.style.setProperty('--ry', ry + 'deg');
      viz.style.setProperty('--zoom', zoom);
    };
    var detenerGiro = function () {
      if (giro) { cancelAnimationFrame(giro); giro = null; }
      viz.classList.remove('is-girando');
      var b = $('[data-viz-accion="girar"]', viz);
      if (b) b.setAttribute('aria-pressed', 'false');
    };
    var girarSolo = function () {
      viz.classList.add('is-girando');
      var paso = function () { ry += 0.35; aplicar(); giro = requestAnimationFrame(paso); };
      giro = requestAnimationFrame(paso);
    };

    escena.addEventListener('pointerdown', function (ev) {
      detenerGiro();
      arrastre = { x: ev.clientX, y: ev.clientY, rx: rx, ry: ry };
      escena.setPointerCapture(ev.pointerId);
      viz.classList.add('is-arrastrando');
    });
    escena.addEventListener('pointermove', function (ev) {
      if (!arrastre) return;
      ry = arrastre.ry + (ev.clientX - arrastre.x) * 0.45;
      rx = Math.max(-60, Math.min(15, arrastre.rx - (ev.clientY - arrastre.y) * 0.3));
      aplicar();
    });
    var soltar = function () { arrastre = null; viz.classList.remove('is-arrastrando'); };
    escena.addEventListener('pointerup', soltar);
    escena.addEventListener('pointercancel', soltar);
    escena.addEventListener('wheel', function (ev) {
      if (!ev.ctrlKey) return;
      ev.preventDefault();
      zoom = Math.max(0.55, Math.min(1.6, zoom - ev.deltaY * 0.001));
      aplicar();
    }, { passive: false });
    escena.addEventListener('keydown', function (ev) {
      var mapa = { ArrowLeft: [0, -12], ArrowRight: [0, 12], ArrowUp: [-6, 0], ArrowDown: [6, 0] };
      if (!mapa[ev.key]) return;
      ev.preventDefault();
      detenerGiro();
      rx = Math.max(-60, Math.min(15, rx + mapa[ev.key][0]));
      ry += mapa[ev.key][1];
      aplicar();
    });

    $$('[data-viz-accion]', viz).forEach(function (b) {
      b.addEventListener('click', function () {
        var accion = b.getAttribute('data-viz-accion');
        if (accion !== 'girar' && accion !== 'noche') detenerGiro();
        if (accion === 'izq') ry -= 30;
        if (accion === 'der') ry += 30;
        if (accion === 'frente') { rx = -6; ry = 0; zoom = 1; }
        if (accion === 'acercar') zoom = Math.min(1.6, zoom + 0.15);
        if (accion === 'alejar') zoom = Math.max(0.55, zoom - 0.15);
        if (accion === 'noche') {
          var noche = !viz.hasAttribute('data-noche');
          viz.toggleAttribute('data-noche', noche);
          b.setAttribute('aria-pressed', noche ? 'true' : 'false');
        }
        if (accion === 'girar') {
          if (giro) { detenerGiro(); } else { b.setAttribute('aria-pressed', 'true'); girarSolo(); }
        }
        aplicar();
      });
    });

    /* En el formulario: el modelo sigue a la categoría y el diseño al archivo elegido */
    if (viz.hasAttribute('data-viz-formulario')) {
      var form = viz.closest('form');
      $$('input[name="categoria_id"]', form).forEach(function (r) {
        r.addEventListener('change', function () { if (r.checked) viz.setAttribute('data-modelo', r.getAttribute('data-modelo') || 'pendon'); });
      });
      var titulo = $('input[name="tipo_trabajo"]', form);
      if (titulo) {
        titulo.addEventListener('input', function () {
          $$('[data-viz-titulo]', viz).forEach(function (t) { t.textContent = titulo.value.trim() || 'Tu diseño aquí'; });
        });
      }
      var archivos = $('input[type=file][data-file-list]', form);
      if (archivos) {
        archivos.addEventListener('change', function () {
          var lista = Array.prototype.slice.call(archivos.files || []);
          var imagen = lista.filter(function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); })[0];
          if (urlLocal) { URL.revokeObjectURL(urlLocal); urlLocal = null; }
          $('[data-viz-aviso]', viz).hidden = !(lista.length && !imagen);
          if (imagen) {
            urlLocal = URL.createObjectURL(imagen);
            viz.style.setProperty('--arte', 'url("' + urlLocal + '")');
            viz.removeAttribute('data-sin-arte');
          } else {
            viz.style.removeProperty('--arte');
            viz.setAttribute('data-sin-arte', '');
          }
        });
      }
    }

    aplicar();
    if (!reducido) {  /* pequeña vuelta de presentación al cargar */
      ry = -60; aplicar();
      setTimeout(function () { ry = -28; aplicar(); }, 60);
    }
  });

  /* Cerrar el menú de cuentas al hacer clic fuera ------------------------- */
  document.addEventListener('click', function (ev) {
    $$('details.account-menu[open]').forEach(function (d) { if (!d.contains(ev.target)) d.removeAttribute('open'); });
  });
})();
